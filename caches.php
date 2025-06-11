<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2025-06-06
 * Modified    : 2025-06-11
 *
 * Copyright   : 2004-2025 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *************/

require_once 'HGVS.php';
require_once 'variant_validator.php';

class caches
{
    // Class that handles the caches. Should be used statically.
    private static array $mapping_cache = [];
    private static array $NC_cache = [];
    private static int $nNewMappingsSinceWrite = 0;
    private static int $nNewNCsSinceWrite = 0;
    private static string $sFileMapping = __DIR__ . '/cache/mapping.txt';
    private static string $sFileNC = __DIR__ . '/cache/NC-variants.txt';
    private static $oVV;

    public static function buildCaches ($sVariant, $sBuild = false)
    {
        // Adds the given NC variant to both the NC and the mapping caches.
        if ((!self::$NC_cache && !self::loadCorrectedNCs()) || (!self::$mapping_cache && !self::loadMappings())) {
            return null;
        }

        if (!$sBuild) {
            // If no build is given, we should be able to find it.
            $sBuild = self::getBuildByNC($sVariant);
        }

        $aVVTranscriptOptions = ['mane_select', 'mane', 'select', 'all', 'raw'];
        $aVVOptions = [
            'map_to_transcripts' => true, // Get the mapping data.
            'predict_protein' => true,    // Also predict the protein changes.
            'lift_over' => true,          // And, the lift-over, please.
            'select_transcripts' => array_shift($aVVTranscriptOptions), // Start by just checking the MANE Select transcript.
        ];

        // We assume that the variant is valid. If we validate it here and change it,
        //  the input of this function won't be usable for getCorrectedNC().
        // Check if we need to call VV.
        if (self::hasCorrectedNC($sVariant)) {
            $sVariantCorrected = self::getCorrectedNC($sVariant);
            if (self::hasMapping($sVariantCorrected, $sBuild)) {
                // Nothing to do.
                return 1; // Evaluates to true, indicates that addition was not needed.
            }
        }
        // Initiate VV if not already done so.
        if (!self::$oVV) {
            self::$oVV = new LOVD_VV();
        }

        // Call VV with the defaults and collect all information.
        $aVV = self::$oVV->verifyGenomic($sVariant, $aVVOptions);
        if (!$aVV) {
            return 0; // Evaluates to false, indicates a VV error.
        }

        // Store the data in the caches.
        $sVariantCorrected = $aVV['data']['DNA'];
        if (!self::setCorrectedNC($sVariant, $sVariantCorrected)) {
            return false;
        }
        // Also store the fix itself as valid input, in case we don't have it.
        self::setCorrectedNC($sVariantCorrected, $sVariantCorrected);

        // If we didn't get transcript mappings, try again with broader transcript settings.
        while (!count($aVV['data']['transcript_mappings']) && count($aVVTranscriptOptions)) {
            $aVVOptions['select_transcripts'] = array_shift($aVVTranscriptOptions);
            $aVV = self::$oVV->verifyGenomic($sVariant, $aVVOptions);
        }

        // Check the returned data.
        if ((empty($aVV['data']['genomic_mappings']['hg19']) || count($aVV['data']['genomic_mappings']['hg19']) != 1)
            || (empty($aVV['data']['genomic_mappings']['hg38']) || count($aVV['data']['genomic_mappings']['hg38']) != 1)) {
            return 0; // Evaluates to false, indicates a VV error.
        }

        // Rewrite the keys for more efficient storage.
        foreach ($aVV['data']['transcript_mappings'] as $sTranscript => $aMapping) {
            $aVV['data']['transcript_mappings'][$sTranscript] = [
                'c' => $aMapping['DNA'],
                'r' => $aMapping['RNA'],
                'p' => $aMapping['protein'],
                'NP' => $aMapping['NP'], // FIXME: I think we should later remove the NP data; it is transcript-specific so it should go to our transcript information cache.
            ];
        }

        // We have at least one transcript mapping, and the liftover worked.
        $b = self::setMapping(
            $aVV['data']['genomic_mappings']['hg19'][0],
            $aVV['data']['genomic_mappings']['hg38'][0],
            'VV',
            $aVV['data']['transcript_mappings']
        );

        return $b;
    }





    public static function getBuildByNC ($sNC)
    {
        // Gets the genome build for a certain input if we can.
        $HGVS = HGVS_ReferenceSequence::check($sNC);
        if ($HGVS->getIdentifiedAs() == 'refseq_genomic') {
            $aInfo = HGVS_Chromosome::getInfoByNC($HGVS->getValue());
            return ($aInfo['build'] ?? false);
        }

        return false;
    }





    public static function getCorrectedNC ($sNC)
    {
        // Gets the corrected NC for a certain input.
        if (!self::$NC_cache && !self::loadCorrectedNCs()) {
            return null;
        }

        return (self::$NC_cache[$sNC] ?? false);
    }





    public static function getMapping ($sNC, $sBuild = false)
    {
        // Checks if we have the mapping for a certain input.
        if (!self::$mapping_cache && !self::loadMappings()) {
            return null;
        }

        if (!$sBuild) {
            // If no build is given, we should be able to find it.
            $sBuild = self::getBuildByNC($sNC);
        }

        $nKey = (self::$mapping_cache[$sBuild][$sNC] ?? false);
        if ($nKey !== false) {
            return (self::$mapping_cache['mappings'][$nKey] ?? false);
        }
        return false;
    }





    public static function hasCorrectedNC ($sNC)
    {
        // Checks if we have the corrected NC for a certain input.
        if (!self::$NC_cache && !self::loadCorrectedNCs()) {
            return null;
        }

        return isset(self::$NC_cache[$sNC]);
    }





    public static function hasMapping ($sNC, $sBuild = false)
    {
        // Checks if we have the mapping for a certain input.
        if (!self::$mapping_cache && !self::loadMappings()) {
            return null;
        }

        if (!$sBuild) {
            // If no build is given, we should be able to find it.
            $sBuild = self::getBuildByNC($sNC);
        }

        if ($sBuild && isset(self::$mapping_cache[$sBuild])) {
            return isset(self::$mapping_cache[$sBuild][$sNC]);
        }

        return false;
    }





    public static function loadCorrectedNCs ()
    {
        // Loads the NC cache when the data is requested.
        $aFile = @file(self::$sFileNC, FILE_IGNORE_NEW_LINES);
        if ($aFile !== false) {
            $aCache = [];
            foreach ($aFile as $sLine) {
                $aLine = explode("\t", $sLine, 2);
                $aCache[$aLine[0]] = $aLine[1];
            }
            self::$NC_cache = $aCache;
            return true;
        }

        return false;
    }





    public static function loadMappings ()
    {
        // Loads the mapping cache when the data is requested.
        $aFile = @file(self::$sFileMapping, FILE_IGNORE_NEW_LINES);
        if ($aFile !== false) {
            $aCache = [
                'hg19' => [], // Variant to key.
                'hg38' => [], // Variant to key.
                'mappings' => [], // Key to mapping info.
            ];
            $iMapping = 0;
            foreach ($aFile as $sLine) {
                $aLine = explode("\t", $sLine, 3);
                $aCache['hg19'][$aLine[0]] = $iMapping;
                $aCache['hg38'][$aLine[1]] = $iMapping;
                $aCache['mappings'][$iMapping] = json_decode($aLine[2], true);
                $iMapping++;
            }
            self::$mapping_cache = $aCache;
            return true;
        }

        return false;
    }





    public static function setCorrectedNC ($sInput, $sCorrected)
    {
        // Adds data to the NC cache.
        if (!self::$NC_cache && !self::loadCorrectedNCs()) {
            return null;
        }

        self::$NC_cache[$sInput] = $sCorrected;
        self::$nNewNCsSinceWrite ++;

        if (self::$nNewNCsSinceWrite >= 25) {
            self::writeCorrectedNCs();
        }
        return true;
    }





    public static function setMapping ($sDNA19, $sDNA38, $sMethod, $aMappings)
    {
        // Adds data to the mapping cache.
        if (!self::$mapping_cache && !self::loadMappings()) {
            return null;
        }

        // Does the variant already exist?
        if (isset(self::$mapping_cache['hg19'][$sDNA19])) {
            $nKey = self::$mapping_cache['hg19'][$sDNA19];
        } elseif (isset(self::$mapping_cache['hg38'][$sDNA38])) {
            $nKey = self::$mapping_cache['hg38'][$sDNA38];
        } else {
            $nKey = array_key_last(self::$mapping_cache['mappings']) + 1;
        }

        // Just overwrite it all.
        self::$mapping_cache['hg19'][$sDNA19] = $nKey;
        self::$mapping_cache['hg38'][$sDNA38] = $nKey;

        // If we have no mappings, just store that.
        if (!$aMappings && !isset(self::$mapping_cache['mappings'][$nKey][$sMethod])) {
            self::$mapping_cache['mappings'][$nKey][$sMethod] = [];
            return true;
        }

        // If we did receive data, double-check if we already have this mapping.
        // If so, we need to overwrite the mapping, not merge the data, duplicating everything.
        foreach ($aMappings as $sTranscript => $aMapping) {
            if (isset(self::$mapping_cache['mappings'][$nKey][$sMethod][$sTranscript])) {
                // Overwrite.
                self::$mapping_cache['mappings'][$nKey][$sMethod][$sTranscript] = $aMapping;
            } else {
                // Append.
                self::$mapping_cache['mappings'][$nKey] = array_merge_recursive(
                    (self::$mapping_cache['mappings'][$nKey] ?? []),
                    [
                        $sMethod => [
                            $sTranscript => $aMapping,
                        ]
                    ]
                );
            }
        }
        ksort(self::$mapping_cache['mappings'][$nKey][$sMethod], SORT_NATURAL);
        ksort(self::$mapping_cache['mappings'][$nKey]);
        self::$nNewMappingsSinceWrite ++;

        if (self::$nNewMappingsSinceWrite >= 25) {
            self::writeMappings();
        }

        return true;
    }





    public static function shutdown ()
    {
        // Make sure the data gets written when the script ends for whatever reason.
        self::writeCorrectedNCs();
        self::writeMappings();
    }





    public static function writeCorrectedNCs ()
    {
        // Writes the data to the cache file.
        if (!self::$NC_cache) {
            return false;
        }

        ksort(self::$NC_cache, SORT_NATURAL);
        $b = @file_put_contents(
            self::$sFileNC,
            implode(
                "\n",
                array_map(
                    function ($sInput, $sCorrected)
                    {
                        return $sInput . "\t" . $sCorrected;
                    },
                    array_keys(self::$NC_cache),
                    array_values(self::$NC_cache)
                )
            )
        );
        if ($b !== false) {
            self::$nNewNCsSinceWrite = 0;
            return true;
        }

        return false;
    }





    public static function writeMappings ()
    {
        // Writes the data to the cache file.
        if (!self::$mapping_cache) {
            return false;
        }

        // To speed things up...
        $aDNA19 = array_flip(self::$mapping_cache['hg19']);
        $aDNA38 = array_flip(self::$mapping_cache['hg38']);

        // Collect and then sort the data.
        $aData = array_map(
            function ($nKey, $aMapping) use ($aDNA19, $aDNA38)
            {
                return implode(
                    "\t",
                    [
                        ($aDNA19[$nKey] ?? ''),
                        ($aDNA38[$nKey] ?? ''),
                        json_encode($aMapping)
                    ]
                );
            },
            array_keys(self::$mapping_cache['mappings']),
            array_values(self::$mapping_cache['mappings'])
        );
        sort($aData, SORT_NATURAL);

        // Store the file.
        $b = @file_put_contents(
            self::$sFileMapping,
            implode("\n", $aData)
        );
        if ($b !== false) {
            self::$nNewMappingsSinceWrite = 0;
            return true;
        }

        return false;
    }
}

register_shutdown_function(['caches', 'shutdown']);
