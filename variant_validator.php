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
