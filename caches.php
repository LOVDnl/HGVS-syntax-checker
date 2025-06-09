<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2025-06-06
 * Modified    : 2025-06-09
 *
 * Copyright   : 2004-2025 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *************/

class caches
{
    // Class that handles the caches. Should be used statically.
    private static array $NC_cache = [];
    private static string $sFileNC = __DIR__ . '/cache/NC-variants.txt';

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





    public static function setCorrectedNC ($sInput, $sCorrected)
    {
        // Adds data to the NC cache.
        if (!self::$NC_cache && !self::loadCorrectedNCs()) {
            return null;
        }

        self::$NC_cache[$sInput] = $sCorrected;
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
