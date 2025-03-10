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
