<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2020-03-09
 * Modified    : 2025-03-10
 *
 * Copyright   : 2004-2025 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *************/

class LOVD_VV
{
    // This class defines the LOVD VV object, handling all Variant Validator calls.

    public $sURL = 'https://rest.variantvalidator.org/'; // The URL of the VV endpoint.
    public $aResponse = array( // The standard response body.
        'data' => array(),
        'messages' => array(),
        'warnings' => array(),
        'errors' => array(),
    );





    function __construct ($sURL = '')
    {
        // Initiates the VV object. Nothing much to do except for filling in the URL.

        if ($sURL) {
            // We don't test given URLs, that would take too much time.
            $this->sURL = rtrim($sURL, '/') . '/';
        }
        // __construct() should return void.
    }





    private function callVV ($sMethod, $aArgs = array())
    {
        // Wrapper function to call VV's JSON webservice.
        // Because we have a wrapper, we can implement CURL, which is much faster on repeated calls.
        global $_CONF;

        // Build URL, regardless of how we'll connect to it.
        $sURL = $this->sURL . $sMethod . '/' . implode('/', array_map('rawurlencode', $aArgs)) . '?content-type=application%2Fjson';
        $sJSONResponse = '';

        if (function_exists('curl_init')) {
            // Initialize curl connection.
            static $hCurl;

            if (!$hCurl) {
                $hCurl = curl_init();
                curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true); // Return the result as a string.
                curl_setopt($hCurl, CURLOPT_FOLLOWLOCATION, true); // Make sure we follow redirects.
                // Set a version so that VV can recognize us.
                curl_setopt($hCurl, CURLOPT_USERAGENT, 'LOVD/VV:' . HGVS::getVersions()['library_version']);

                // Set proxy, if we are used from within LOVD and LOVD requires a proxy.
                if (!empty($_CONF['proxy_host'])) {
                    curl_setopt($hCurl, CURLOPT_PROXY, $_CONF['proxy_host'] . ':' . $_CONF['proxy_port']);
                    if (!empty($_CONF['proxy_username']) || !empty($_CONF['proxy_password'])) {
                        curl_setopt($hCurl, CURLOPT_PROXYUSERPWD, $_CONF['proxy_username'] . ':' . $_CONF['proxy_password']);
                    }
                }
            }

            curl_setopt($hCurl, CURLOPT_URL, $sURL);
            $sJSONResponse = curl_exec($hCurl);

        } elseif (function_exists('lovd_php_file')) {
            // Backup method, no curl installed. We'll try LOVD's file() implementation, which also handles proxies.
            $aJSONResponse = lovd_php_file($sURL);
            if ($aJSONResponse !== false) {
                $sJSONResponse = implode("\n", $aJSONResponse);
            }

        } else {
            // Last fallback. Requires fopen wrappers.
            $aJSONResponse = file($sURL);
            if ($aJSONResponse !== false) {
                $sJSONResponse = implode("\n", $aJSONResponse);
            }
        }



        if ($sJSONResponse) {
            $aJSONResponse = @json_decode($sJSONResponse, true);
            if ($aJSONResponse !== false) {
                return $aJSONResponse;
            }
        }
        // Something went wrong...
        return false;
    }





    public function getTranscriptsByID ($sSymbol)
    {
        // Returns the available transcripts for the given gene or transcript.
        // When a transcript has been passed, it returns only that transcript (any version).

        $bTranscript = preg_match('/^[NX][MR]_[0-9.]+$/', $sSymbol);
        // For now, let's remove the version to just match anything.
        // VV's output does not depend on this, but our checks further down do.
        if ($bTranscript) {
            $sSymbol = strstr($sSymbol . '.', '.', true);
        }

        // FIXME: We're not ready to use the v2 of the endpoint. Issues:
        //        - Genome builds have to be sent has NCBI IDs (GRCh37, not hg19).
        //        - When filtering for a certain transcript, the endpoint is not significantly faster and you can't filter for, e.g., "NM_002225".
        //          That will return nothing, you NEED to specify a version.
        //          That makes that addition quite useless for us, so the only thing left is that you can pass on a build.
        //          That is not very relevant at the moment when I'm rebuilding this class, so I'm leaving it.
        $aJSON = $this->callVV('VariantValidator/tools/gene2transcripts', array(
            'id' => $sSymbol,
        ));
        if ($aJSON && is_array($aJSON) && count($aJSON) == 1 && isset($aJSON[0])) {
            // Handle https://github.com/openvar/variantValidator/issues/579.
            // The output was suddenly a list instead of the expected object.
            $aJSON = current($aJSON);
        }
        if (!$aJSON || empty($aJSON['transcripts'])) {
            // Failure.
            // OK, but... what if we were working on chrM? And VV doesn't support these yet?
            if ($aJSON && isset($aJSON['current_symbol']) && substr($aJSON['current_symbol'], 0, 3) == 'MT-') {
                // Collect all NCs and builds for chrM.
                $aNCs = [];
                foreach (HGVS_Genome::getBuilds() as $sBuild) {
                    $sNC = HGVS_ReferenceSequence::check($sBuild . ':chrM')->getCorrectedValue();
                    if (isset($aNCs[$sNC])) {
                        $aNCs[$sNC][] = $sBuild;
                    } else {
                        $aNCs[$sNC] = [$sBuild];
                    }
                }
                $aData = [];
                foreach ($aNCs as $sNC => $aBuilds) {
                    $aData[$sNC . '(' . $aJSON['current_symbol'] . ')'] = [
                        'name' => 'transcript variant 1',
                        'id_ncbi_protein' => '',
                        'genomic_positions' => array_combine(
                            $aBuilds,
                            array_map(
                                function ($sBuild)
                                {
                                    return [
                                        'M' => [
                                            'start' => null,
                                            'end' => null,
                                        ]
                                    ];
                                }, $aBuilds)),
                        'transcript_positions' => [
                            'cds_start' => null,
                            'cds_length' => null,
                            'length' => null,
                        ],
                        'select' => false,
                    ];
                }

                return array_merge(
                    $this->aResponse,
                    [
                        'data' => $aData,
                    ]
                );
            }
            return array_merge($this->aResponse, ['errors' => 'No transcripts found.']);
        }

        $aData = $this->aResponse;
        foreach ($aJSON['transcripts'] as $aTranscript) {
            // If we requested a single transcript, show only those.
            if ($bTranscript && strpos($aTranscript['reference'], $sSymbol . '.') === false) {
                continue;
            }

            // Clean name.
            $sName = preg_replace(
                array(
                    '/^Homo sapiens\s+/', // Remove species name.
                    '/^' . preg_quote($aJSON['current_name'], '/') . '\s+/', // The current gene name.
                    '/.*\(' . preg_quote($aJSON['current_symbol'], '/') . '\),\s+/', // The current symbol.
                    '/, mRNA\b/', // mRNA suffix.
                    '/, non-coding RNA$/', // non-coding RNA suffix, replaced to " (non-coding)".
                    '/; nuclear gene for mitochondrial product$/', // suffix given to a certain class of genes.
                ), array('', '', '', '', ' (non-coding)', ''), $aTranscript['description']);

            // Figure out the genomic positions, which are given to us using the NCs.
            $aGenomicPositions = array_fill_keys(HGVS_Genome::getBuilds(), []);
            foreach ($aTranscript['genomic_spans'] as $sRefSeq => $aMapping) {
                $aNCInfo = HGVS_Chromosome::getInfoByNC($sRefSeq);
                if ($aNCInfo) {
                    $sBuild = $aNCInfo['build'];
                    $sChromosome = $aNCInfo['chr'];
                    $aGenomicPositions[$sBuild][$sChromosome] = array(
                        'start' => ($aMapping['orientation'] == 1?
                            $aMapping['start_position'] : $aMapping['end_position']),
                        'end' => ($aMapping['orientation'] == 1?
                            $aMapping['end_position'] : $aMapping['start_position']),
                    );
                }
            }

            $aData['data'][$aTranscript['reference']] = array(
                'gene_symbol' => $aJSON['current_symbol'],
                'gene_hgnc' => substr(strstr($aJSON['hgnc'] ?? '', ':'), 1),
                'name' => $sName,
                'id_ncbi_protein' => $aTranscript['translation'],
                'genomic_positions' => $aGenomicPositions,
                'transcript_positions' => array(
                    'cds_start' => $aTranscript['coding_start'],
                    'cds_length' => (!$aTranscript['coding_end']? NULL : ($aTranscript['coding_end'] - $aTranscript['coding_start'] + 1)),
                    'length' => $aTranscript['length'],
                ),
                'select' => ($aTranscript['annotations']['db_xref']['select'] ?? false),
            );
        }

        ksort($aData['data'], SORT_NATURAL);
        return $aData;
    }





    public function test ()
    {
        // Tests the VV endpoint.

        $aJSON = $this->callVV('hello');
        if (!$aJSON) {
            // Failure.
            return false;
        }

        if (isset($aJSON['status']) && $aJSON['status'] == 'hello_world') {
            // All good.
            return true;
        } else {
            // Something JSON, but perhaps another format?
            return 0;
        }
    }
}
