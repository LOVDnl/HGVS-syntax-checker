<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2011-09-06
 * Modified    : 2025-03-14
 *
 * Copyright   : 2004-2025 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *************/

// HGVS syntax check result expires in a day.
header('Expires: ' . date('r', time() + (24 * 60 * 60)));

// Set error_reporting if necessary. We don't want notices to show. This will do
// fine most of the time.
if (ini_get('error_reporting') == E_ALL) {
    error_reporting(E_ALL ^ E_NOTICE);
}

function lovd_php_htmlspecialchars ($Var)
{
    // Recursively run htmlspecialchars(), even with unknown depth.

    if (is_array($Var)) {
        return array_map('lovd_php_htmlspecialchars', $Var);
    } elseif (!is_string($Var)) {
        return $Var;
    } else {
        return htmlspecialchars($Var ?: '');
    }
}

if (empty($_REQUEST['var'])) {
    die(0);
}

if (!empty($_REQUEST['callVV']) && $_REQUEST['callVV'] == 'true') {
    require '../variant_validator.php';
    $_VV = new LOVD_VV();
    $bVV = true;
} else {
    $bVV = false;
}
require_once '../HGVS.php'; // May have already been included by VV.

header('Content-type: application/json; charset=UTF-8');
@ini_set('default_charset','UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}





// Handling both single submissions and list submissions using the same code.
// This removes duplication, or perhaps worse, differences in implementation.
// Put the variants in an array. Previously, we used the variant descriptions as keys, but we won't do that anymore.
// This is not a normal form; JS combines the values without adding a \r.
$aVariants = preg_split('/\s*\n\s*/', trim($_REQUEST['var']));
$aResponse = [];
foreach ($aVariants as $sVariant) {
    $sVariant = trim($sVariant);
    $HGVS = HGVS::checkVariant($sVariant)->allowMissingReferenceSequence();
    $aVariant = $HGVS->getInfo();

    if (isset($aVariant['errors']['ENOTSUPPORTED'])) {
        // Catch and convert ENOTSUPPORTED.
        // We don't actually know whether this is HGVS compliant or not.
        if ($aVariant['valid']) {
            $aVariant['valid'] = null;
        }
        // The library allows for ENOTSUPPORTED, and flags it as valid.
        $aVariant['messages']['INOTSUPPORTED'] = 'This variant description contains unsupported syntax.' .
            ' Although we aim to support all of the HGVS nomenclature rules,' .
            ' some complex variants are not fully implemented yet in our syntax checker.' .
            ' We invite you to submit your variant description here, so we can have a look: https://github.com/LOVDnl/HGVS-syntax-checker/issues.';
        // And remove the ENOTSUPPORTED.
        unset($aVariant['errors']['ENOTSUPPORTED']);
    }

    if (isset($aVariant['warnings']['WNOTSUPPORTED'])) {
        // Catch and reword WNOTSUPPORTED.
        $aVariant['warnings']['WNOTSUPPORTED'] = str_replace(' for mapping and validation', ' for sequence-level validation', $aVariant['warnings']['WNOTSUPPORTED']);
    }

    if (isset($aVariant['messages']['IREFSEQMISSING'])) {
        // Our version is more informative.
        $aVariant['messages']['IREFSEQMISSING'] = 'Please note that your variant description is missing a reference sequence. ' .
            'Although this is not necessary for our syntax check, a variant description does ' .
            'need a reference sequence to be fully informative and HGVS-compliant.';
    }

    // Don't double-complain about not having a variant when we already complain in a similar way.
    if (isset($aVariant['errors']['EFAIL'])) {
        unset($aVariant['errors']['EVARIANTREQUIRED']);
    } elseif (isset($aVariant['errors']['EVARIANTREQUIRED']) && isset($aVariant['warnings']['WVCF'])) {
        unset($aVariant['errors']['EVARIANTREQUIRED']);
        // If there are no more errors left, fix the corrected values.
        if (!count($aVariant['errors'])) {
            foreach ($aVariant['corrected_values'] as $sCorrection => $nConfidence) {
                $aVariant['corrected_values'][$sCorrection] = ($nConfidence * 10);
            }
        }
    }

    // Do not suggest anything for other reference sequences.
    if ($aVariant['identified_as'] == 'reference_sequence' && $HGVS->ReferenceSequence->getIdentifiedAs() == 'other') {
        $aVariant['corrected_values'] = [];
    }

    $aVariant['VV'] = array();
    if ($bVV && empty($aVariant['messages']['INOTSUPPORTED']) && empty($aVariant['warnings']['WNOTSUPPORTED'])) {

        if (!empty($aVariant['warnings']['WREFERENCENOTSUPPORTED'])) {
            $aVariant['VV']['WNOTSUPPORTED'] = 'This reference sequence type is not currently supported by VariantValidator.';

        } elseif (!$aVariant['valid']) {
            $aVariant['VV']['EFAIL'] = 'Please first correct the variant description to run VariantValidator.';

        } elseif (isset($aVariant['messages']['IREFSEQMISSING'])) {
            $aVariant['VV']['EREFSEQMISSING'] = 'Please provide a reference sequence to run VariantValidator.';
            unset($aVariant['messages']['IREFSEQMISSING']);

        } else {
            // Call VariantValidator. Use the information we have to determine whether this is a genomic variant or not.
            $aVariant['corrected_values'] = [];
            $aVV = ($HGVS->ReferenceSequence->molecule_type == 'chromosome'?
                $_VV->verifyGenomic($sVariant) :
                // Be as strict as possible with the transcripts returned, in case an NG or LRG is submitted.
                $_VV->verifyVariant($sVariant, ['select_transcripts' => 'mane_select'])
            );

            if ($aVV === false) {
                // In theory, this can be our fault as well, because our VV library returns false on internal errors.
                // However, assuming we coded everything well, it's most likely VV's fault.
                $aVariant['VV']['EINTERNAL'] = 'An internal error within VariantValidator occurred when trying to validate your variant.';

            } else {
                if (!empty($aVV['data']['DNA'])) {
                    // We got a variant back, so VV at least understood the variant.
                    $aVariant['corrected_values'] = [$aVV['data']['DNA'] => 1];

                    if ($sVariant != $aVV['data']['DNA']) {
                        $aVariant['valid'] = false;
                        // We don't check for WCORRECTED here, because the VV library accepts some changes
                        //  without setting WCORRECTED. We want to show every difference.
                        // This message will be modified by the interface.
                        $aVariant['VV']['WCORRECTED'] = 'VariantValidator automatically corrected the variant description to {{VARIANT}}.';
                        unset($aVV['warnings']['WCORRECTED']); // In case it exists.
                        unset($aVV['warnings']['WROLLFORWARD']); // In case it exists.
                    }
                }

                if (!$aVV['errors'] && !$aVV['warnings'] && $aVariant['valid']) {
                    $aVariant['VV']['IOK'] = 'The variant description passed the validation by VariantValidator.';

                } else {
                    // Warnings or errors have occurred.
                    // If all we got was a WNOTSUPPORTED, handle it differently. It looked HGVS, VV can't validate, let's accept it.
                    if (empty($aVV['errors']) && array_keys($aVV['warnings']) == array('WNOTSUPPORTED')) {
                        // In principle, this is a bug in the HGVS library,
                        //  because WNOTSUPPORTED should ideally be generated by us, not the VV library.
                        $aVariant['VV']['WNOTSUPPORTED'] = $aVV['warnings']['WNOTSUPPORTED'];

                    } elseif (empty($aVV['warnings']) && array_keys($aVV['errors']) == array('EREFSEQ')) {
                        // The RefSeq threw an error, but that doesn't necessarily mean that it's invalid. VV can't be used.
                        $aVariant['VV']['WREFERENCENOTSUPPORTED'] = "VariantValidator couldn't find the reference sequence used. This does not necessarily mean the variant description is invalid, but we can't validate it to be sure. Please double-check the used reference sequence.";

                    } else {
                        $aVariant['valid'] = false;
                        $aVariant['VV'] = array_merge(
                            $aVariant['VV'],
                            array_map(
                                function ($sValue)
                                {
                                    return 'VariantValidator: ' . htmlspecialchars($sValue);
                                },
                                array_merge($aVV['errors'], $aVV['warnings'])
                            )
                        );
                    }
                }
            }
        }
    }

    // Add the total confidence which is easy for us to calculate. JS will use this to determine the colors.
    $aVariant['corrected_values_confidence'] = array_sum($aVariant['corrected_values']);

    // Finally, escape everything because that is hard to do in Javascript.
    $aVariant['corrected_values'] = array_combine(
        lovd_php_htmlspecialchars(array_keys($aVariant['corrected_values'])),
        array_values($aVariant['corrected_values'])
    );
    $aResponse[] = lovd_php_htmlspecialchars($aVariant);
}

echo json_encode($aResponse);
