<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2025-04-16
 * Modified    : 2026-06-05
 *
 * Copyright   : 2004-2026 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *************/

if (isset($_SERVER['HTTP_HOST'])) {
    // We're being run through a browser.
    mb_internal_encoding('UTF-8');
    header('Content-type: text/plain; charset=UTF-8');
}





// 1) Check if we can write here at all.
chdir(dirname(__FILE__));
if (!is_writable('./')) {
    echo "The cache dir is not writable.\n";
    exit(1);
}





// 2) Update the gene cache, if needed.
$sCacheFile = 'genes.json';
$sSource = 'https://lovd.nl/mirrors/hgnc/symbol_to_ID.txt';
$bUpdateCache = (!file_exists($sCacheFile) || filemtime($sCacheFile) < strtotime('-6 days'));

if (!$bUpdateCache) {
    echo "Gene cache not yet expired.\n";
} else {
    echo "Gene cache expired, attempting to refresh...\n";
    $aData = file($sSource, FILE_IGNORE_NEW_LINES);
    if (!$aData) {
        echo "Could not load the remote data.\n";
        exit(2);
    }
    $aCache = [
        'genes' => [],
        'IDs' => [],
    ];
    foreach ($aData as $sLine) {
        // Explode the data into gene and HGNC ID.
        list($sSymbol, $nID) = array_pad(explode("\t", $sLine), 2, 0);
        $nID = (int) $nID;

        // Also filter the data a bit.
        if (!preg_match('/^[A-Z][A-Za-z0-9#@-]*$/', $sSymbol)) {
            // This gene symbol doesn't look like a gene symbol. Ignore.
        } else {
            // What is left, store.
            if (!isset($aCache['IDs'][$nID])) {
                // This is the first time we've seen this HGNC ID. We will store the symbol as the official symbol.
                $aCache['IDs'][$nID] = $sSymbol;
            }

            // Store the lowercase gene symbol with a reference to the HGNC ID.
            $sSymbol = strtolower($sSymbol);
            if (!isset($aCache['genes'][$sSymbol])) {
                // This is the first time we've seen this symbol. We'll link this HGNC ID to it.
                $aCache['genes'][$sSymbol] = $nID;
            }
        }
    }

    // Now store the file.
    if (!file_put_contents($sCacheFile, json_encode($aCache))) {
        echo "Could not save the gene data.\n";
        exit(3);
    } else {
        echo 'Successfully stored ' . count($aCache['genes']) . ' symbols, ' . count($aCache['IDs']) . " unique genes.\n";
    }
}





// 2) Update the transcript cache, if needed.
$sCacheFile = 'transcripts.json';
$sSource = 'https://lovd.nl/mirrors/ncbi/transcripts.json';
$bUpdateCache = (!file_exists($sCacheFile) || filemtime($sCacheFile) < strtotime('-30 days'));

if (!$bUpdateCache) {
    echo "Transcript cache not yet expired.\n";
} else {
    echo "Transcript cache expired, attempting to refresh...\n";
    $sData = file_get_contents($sSource);
    if (!$sData) {
        echo "Could not load the remote data.\n";
        exit(4);
    }
    $aData = json_decode($sData, true);
    if (!$aData) {
        echo "Could not parse the remote data.\n";
        exit(5);
    }

    // Now store the file.
    if (!file_put_contents($sCacheFile, $sData)) {
        echo "Could not save the transcript data.\n";
        exit(6);
    } else {
        echo 'Successfully stored ' . count($aData['transcripts']) . " transcripts.\n";
    }
}





// 3) Load the cytoband data.
$sCacheFile = 'cytobands.json';
$bUpdateCache = (!file_exists($sCacheFile));

if (!$bUpdateCache) {
    echo "Cytoband cache already downloaded.\n";
} else {
    $aData = [];
    foreach (['hg19', 'hg38'] as $sBuild) {
        $sSource = "https://hgdownload.soe.ucsc.edu/goldenPath/{$sBuild}/database/cytoBand.txt.gz";
        $aFile = gzfile($sSource, FILE_IGNORE_NEW_LINES);
        if (!$aFile) {
            echo "Could not load the remote data for $sBuild.\n";
            exit(7);
        }

        foreach ($aFile as $sLine) {
            list($sChr, $nStart, $nEnd, $sBand) = explode("\t", $sLine);
            $sChr = substr($sChr, 3); // Remove 'chr' from "chr1".
            $nStart ++; // UCSC uses 0-based half-open intervals. Fix that, and also convert this to int.

            // Ignore non-relevant "chromosomes".
            if (!preg_match('/^([XYM]|[0-9]{1,2})$/', $sChr)) {
                continue;
            }

            $aData[$sBuild][$sChr][$sBand] = [$nStart, (int) $nEnd];
        }
        ksort($aData[$sBuild]);
    }

    // For some reason, hg19 does not contain the values for chrM.
    $aData['hg19']['M'] = $aData['hg38']['M'];

    // Now store the file.
    if (!file_put_contents($sCacheFile, json_encode($aData))) {
        echo "Could not save the cytoband data.\n";
        exit(8);
    } else {
        echo "Successfully stored cytoband data.\n";
    }
}
