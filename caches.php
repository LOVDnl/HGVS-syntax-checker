<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2025-06-06
 * Modified    : 2025-06-10
 *
 * Copyright   : 2004-2025 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *************/

require_once 'HGVS.php';

class caches
{
    // Class that handles the caches. Should be used statically.
    private static array $mapping_cache = [];
    private static array $NC_cache = [];
    private static string $sFileMapping = __DIR__ . '/cache/mapping.txt';
    private static string $sFileNC = __DIR__ . '/cache/NC-variants.txt';

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





    private static function loadCorrectedNCs ()
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





    private static function loadMappings ()
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

        return true;
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
        return ($b !== false);
    }
}
