<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2011-09-06
 * Modified    : 2025-03-05
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

header('Content-type: application/json; charset=UTF-8');
@ini_set('default_charset','UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
