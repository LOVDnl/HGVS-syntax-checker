<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2011-09-06
 * Modified    : 2025-03-07
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
require '../HGVS.php';

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
