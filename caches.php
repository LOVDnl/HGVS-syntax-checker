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
}
