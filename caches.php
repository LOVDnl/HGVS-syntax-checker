<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2025-06-06
 * Modified    : 2026-07-24
 *
 * Copyright   : 2004-2026 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *************/

namespace LOVD\HGVS;
require_once 'HGVS.php';
require_once 'variant_validator.php';

class Caches
{
    // Class that handles the caches. Should be used statically.
    private static array $mapping_cache = [
        'hg19' => [], // Variant to key.
        'hg38' => [], // Variant to key.
        'mappings' => [], // Key to mapping info.
    ];
    private static array $NC_cache = [];
    private static array $aVV = [
        'url' => '',
        'auth_token' => '',
        'transcript_options' => [
            // We're storing the strings as keys so that if the keys are ever renamed, we can always easily add aliases.
            // Internally, we store the integers to try and keep the file size down a bit and to simplify $a < $b.
            'mane_select' => 1,
            'mane' => 2,
            'select' => 3,
            'all' => 4,
            'raw' => 5,
        ]
    ];
    private static int $nNewMappingsSinceWrite = 0;
    private static int $nNewNCsSinceWrite = 0;
    private static string $sFileMapping = __DIR__ . '/cache/mapping.txt';
    private static string $sFileNC = __DIR__ . '/cache/NC-variants.txt';
    private static $oVV;

    public static function buildCaches ($sVariant, $sBuild = false, $sMethod = null)
    {
        // Adds the given NC variant to both the NC and the mapping caches.
        // Return values:
        // null  : Internal failure with the cache files.
        // true  : Success.
        // 1     : Addition was not needed, variant already known (includes previously known errors, e.g., EREF).
        // 0     : Internal failure with VV. Failure may be non-permanent.
        // false : Internal failure when adding data to the cache. Failure is likely permanent.
        if ((!self::$NC_cache && !self::loadCorrectedNCs()) || (empty(self::$mapping_cache['mappings']) && !self::loadMappings())) {
            return null;
        }

        if (!$sBuild) {
            // If no build is given, we should be able to find it.
            $sBuild = self::getBuildByNC(strstr($sVariant, ':', true));
        }

        $aVVTranscriptOptions = array_keys(self::$aVV['transcript_options']); // mane_select, mane, select, all, raw.
        if ($sMethod && isset(self::$aVV['transcript_options'][$sMethod])) {
            // Start elsewhere, not with 'mane_select'.
            while ($sMethod != $aVVTranscriptOptions[0]) {
                // Remove this method, we requested something else.
                array_shift($aVVTranscriptOptions);
            }
        }
        $aVVOptions = [
            'map_to_transcripts' => true, // Get the mapping data.
            'predict_protein' => true,    // Also predict the protein changes.
            'lift_over' => true,          // And, the lift-over, please.
            'select_transcripts' => array_shift($aVVTranscriptOptions),
        ];

        // We assume that the variant is valid. If we validate it here and change it,
        //  the input of this function won't be usable for getCorrectedNC().
        // Check if we need to call VV.
        if (self::hasErrors($sVariant)) {
            // We've seen this variant before, and it has errors. There's nothing to do for us now.
            return 1;

        } elseif (self::hasCorrectedNC($sVariant)) {
            $sVariantCorrected = self::getCorrectedNC($sVariant);
            $aMapping = self::getMapping($sVariantCorrected, $sBuild);
            if ($aMapping) {
                // We have a mapping already. Are we asking for more?
                if (($aMapping['VV']['s'] ?? 1) >= (self::$aVV['transcript_options'][$aVVOptions['select_transcripts']] ?? 1)) {
                    // Nothing to do.
                    return 1; // Evaluates to true, indicates that addition was not needed.
                }
            }
        }
        // Initiate VV if not already done so.
        if (!self::$oVV) {
            self::$oVV = new VV(self::$aVV['url'], self::$aVV['auth_token']);
        }

        // Call VV with the defaults and collect all information.
        $aVV = self::$oVV->verifyGenomic($sVariant, $aVVOptions);
        if (!$aVV || empty($aVV['data']['DNA'])) {
            // If there are errors, store this.
            if (!empty($aVV['errors'])) {
                // There is a problem with the variant. Store the errors, so we won't repeat this request.
                return self::setCorrectedNC($sVariant, json_encode($aVV['errors']));
            }
            return 0; // Evaluates to false, indicates a VV or input error.
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
            // Also store the select method used for this mapping.
            $aVV['data']['transcript_mappings'] + ['s' => (self::$aVV['transcript_options'][$aVVOptions['select_transcripts']] ?? 1)],
        );

        return $b; // Returns true or false.
    }





    public static function getBuildByNC ($sNC)
    {
        // Gets the genome build for a certain input if we can.
        static $aResults = []; // Store the results, as some pipelines use this a lot.
        if (isset($aResults[$sNC])) {
            return $aResults[$sNC];
        }

        $HGVS = HGVS_ReferenceSequence::check($sNC);
        if ($HGVS->getIdentifiedAs() == 'refseq_genomic') {
            $aInfo = HGVS_Chromosome::getInfoByNC($HGVS->getValue());
            $aResults[$sNC] = ($aInfo['build'] ?? false);
            return $aResults[$sNC];
        }

        $aResults[$sNC] = false; // Cache this, too.
        return false;
    }





    public static function getCorrectedNC ($sVariant)
    {
        // Gets the corrected NC for a certain input.
        if (!self::$NC_cache && !self::loadCorrectedNCs()) {
            return null;
        }

        if (isset(self::$NC_cache[$sVariant]) && self::$NC_cache[$sVariant][0] != '{') {
            return self::$NC_cache[$sVariant];
        }
        return false;
    }





    public static function getErrors ($sVariant)
    {
        // Gets the errors for a certain input.
        if (!self::$NC_cache && !self::loadCorrectedNCs()) {
            return null;
        }

        if (isset(self::$NC_cache[$sVariant]) && self::$NC_cache[$sVariant][0] == '{') {
            return json_decode(self::$NC_cache[$sVariant], true);
        }
        return false;
    }





    public static function getLiftOver ($sVariant, $sBuild = false)
    {
        // Gets the liftover data (from the mapping file or the liftover file) for a certain input.
        if (empty(self::$mapping_cache['mappings']) && !self::loadMappings()) {
            return null;
        }

        if (!$sBuild) {
            // If no build is given, we should be able to find it.
            $sBuild = self::getBuildByNC(strstr($sVariant, ':', true));
        }

        // Prepare the output.
        $aReturn = [
            $sBuild => $sVariant,
        ];

        // Look for the liftover data in the mapping cache.
        $nKey = (self::$mapping_cache[$sBuild][$sVariant] ?? false);
        if ($nKey !== false) {
            // Loop through all other builds to collect the liftover data.
            foreach (array_diff(array_keys(self::$mapping_cache), [$sBuild, 'mappings']) as $sBuild) {
                $aReturn[$sBuild] = array_search($nKey, self::$mapping_cache[$sBuild]);
            }

            $aReturn = array_filter($aReturn); // Remove empty entries.
            ksort($aReturn);
            return $aReturn;
        }
        return false;
    }





    public static function getMapping ($sVariant, $sBuild = false)
    {
        // Checks if we have the mapping for a certain input.
        if (empty(self::$mapping_cache['mappings']) && !self::loadMappings()) {
            return null;
        }

        if (!$sBuild) {
            // If no build is given, we should be able to find it.
            $sBuild = self::getBuildByNC(strstr($sVariant, ':', true));
        }

        $nKey = (self::$mapping_cache[$sBuild][$sVariant] ?? false);
        if ($nKey !== false) {
            return (self::$mapping_cache['mappings'][$nKey] ?? false);
        }
        return false;
    }





    public static function hasCorrectedNC ($sVariant)
    {
        // Checks if we have the corrected NC for a certain input.
        if (!self::$NC_cache && !self::loadCorrectedNCs()) {
            return null;
        }

        return (isset(self::$NC_cache[$sVariant]) && self::$NC_cache[$sVariant][0] != '{');
    }





    public static function hasErrors ($sVariant)
    {
        // Checks if we have errors for a certain input.
        if (!self::$NC_cache && !self::loadCorrectedNCs()) {
            return null;
        }

        return (isset(self::$NC_cache[$sVariant]) && self::$NC_cache[$sVariant][0] == '{');
    }





    public static function hasMapping ($sVariant, $sBuild = false)
    {
        // Checks if we have the mapping for a certain input.
        if (empty(self::$mapping_cache['mappings']) && !self::loadMappings()) {
            return null;
        }

        if (!$sBuild) {
            // If no build is given, we should be able to find it.
            $sBuild = self::getBuildByNC(strstr($sVariant, ':', true));
        }

        if ($sBuild && isset(self::$mapping_cache[$sBuild])) {
            return isset(self::$mapping_cache[$sBuild][$sVariant]);
        }

        return false;
    }





    public static function loadCorrectedNCs (string $sFile = ''): bool
    {
        // Loads the NC cache when the data is requested.
        if (!$sFile) {
            $sFile = self::$sFileNC;
        }
        $aFile = @file($sFile, FILE_IGNORE_NEW_LINES);
        if ($aFile !== false) {
            // If we're dealing with an old file, we should handle things a bit differently.
            $bOldFormat = ($aFile[0][0] == '#');
            if ($bOldFormat) {
                // Remove the header.
                array_shift($aFile);
            }

            foreach ($aFile as $sLine) {
                $aLine = explode("\t", $sLine, 2);
                if (empty($aLine[1]) || ($bOldFormat && !substr($aLine[1], 0, 3) != 'NC_')) {
                    // Skip empty answers or errors when reading the old format.
                    continue;
                } elseif (isset(self::$NC_cache[$aLine[0]])) {
                    if (self::$NC_cache[$aLine[0]] == $aLine[1]) {
                        // We have seen this variant before, and we already have this answer stored. Nothing to do.
                        continue;
                    }
                    // We have seen this variant already, but we have a different value here.
                    // This is a conflict we should let people know about.
                    throw new \Exception("Conflict while merging cache files for {$aLine[0]}");
                }
                self::$NC_cache[$aLine[0]] = $aLine[1];
            }
            return true;
        }

        return false;
    }





    public static function loadMappings ()
    {
        // Loads the mapping cache when the data is requested.
        $aFile = @file(self::$sFileMapping, FILE_IGNORE_NEW_LINES);
        if ($aFile !== false) {
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
        if (empty(self::$mapping_cache['mappings']) && !self::loadMappings()) {
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
            // Note that $sTranscript can be "s" as well, where we store the select method.
            if (isset(self::$mapping_cache['mappings'][$nKey][$sMethod][$sTranscript])) {
                // Transcript (or select method) already exists.
                if ($sTranscript == 's') {
                    // This is a special value; if we have it already, store the highest value.
                    // We use this to indicate the select method used for this mapping (mane_select, etc).
                    // These values are stored as integers.
                    self::$mapping_cache['mappings'][$nKey][$sMethod][$sTranscript] = max(
                        self::$mapping_cache['mappings'][$nKey][$sMethod][$sTranscript],
                        $aMapping
                    );
                    continue;
                }

                // Just a normal transcript; overwrite.
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





    public static function setVVOptions (array $aOptions): void
    {
        // Overwrite VV options (URL, auth token) with new values.
        foreach ($aOptions as $sKey => $sValue) {
            self::$aVV[$sKey] = $sValue;
        }

        // If we have an object already, destroy it.
        if (self::$oVV) {
            self::$oVV = false;
        }
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
        if (empty(self::$mapping_cache['mappings'])) {
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

register_shutdown_function(['LOVD\HGVS\Caches', 'shutdown']);
