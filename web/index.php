<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2021-12-03
 * Modified    : 2025-03-05
 *
 * Copyright   : 2004-2025 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *************/

header('Content-type: text/html; charset=UTF-8');
@ini_set('default_charset','UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <!-- Bootstrap Font Icon CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.2/font/bootstrap-icons.css" rel="stylesheet">

    <title>LOVD HGVS variant description syntax checker</title>
</head>
<body class="bg-light">

<div class="container">
    <main>
        <div class="py-5 text-center">
            <h1>LOVD HGVS variant description syntax checker</h1>
            <p class="lead">
                Validate the syntax of variant descriptions according to the rules of the HGVS nomenclature.
                Our tool checks your variant description and, when invalid, tries to correct your description into a valid HGVS-compliant description.
            </p>
            <p>
                This tool supports a wide range of non-compliant notations as well as alternative formats like VCF and SPDI.
                Currently, we support 98.5% of all documented DNA-level nomenclature, and some RNA and protein descriptions.
                We aim to reach 100% support for DNA, RNA, and protein-level variant descriptions.
                A manuscript is in preparation.
            </p>
            <p class="text-secondary">
                To also validate your variant description on the sequence level, please select the VariantValidator option below the input field.
                This feature requires you to include a reference sequence in your descriptions.
            </p>
        </div>

        <ul class="nav nav-tabs" id="hgvsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="single-variant" data-bs-toggle="tab" data-bs-target="#single" type="button" role="tab" aria-controls="single" aria-selected="true">
                    Check a single variant
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="mutiple-variants" data-bs-toggle="tab" data-bs-target="#multiple" type="button" role="tab" aria-controls="multiple" aria-selected="false">
                    Check a list of variants
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="https://api.lovd.nl/" target="_blank">API</a>
            </li>
        </ul>

        <div class="tab-content" id="hgvsTabsContent">
                <div class="py-3 tab-pane fade show active" id="single" role="tabpanel">
                    <FORM onsubmit="showResponse('singleVariant'); return false;" action="">
                        <div class="py-2">
                            <input type="text" class="form-control" id="singleVariant" name="singleVariant" placeholder="NM_002225.3:c.157C>T" value="">
                        </div>
                        <div class="py-2">
                            <input type="checkbox" class="form-check-input" id="singleVariantUseVV" name="singleVariantUseVV">
                            <label class="form-check-label mx-2" for="singleVariantUseVV">Also use VariantValidator.org to validate this variant on the sequence level (slower)</label>
                        </div>
                        <div class="py-2 d-flex justify-content-between">
                            <div>
                                <button class="btn btn-primary" type="submit" id="singleVariantButton">Validate this variant description</button>
                            </div>
                            <div>
                                <button class="btn btn-primary d-none" id="singleVariantDownloadButton">Download this result</button>
                            </div>
                        </div>
                    </FORM>
                    <DIV class="py-2" id="singleVariantResponse"></DIV>
                </div>
                <div class="py-3 tab-pane fade" id="multiple" role="tabpanel">
                    <FORM onsubmit="showResponse('multipleVariants'); return false;" action="">
                        <div class="py-2">
                            <textarea class="form-control" id="multipleVariants" name="multipleVariants" placeholder="NM_002225.3:c.157C>T
NC_000015.9:g.40699840C>T" rows="5"></textarea>
                        </div>
                        <div class="py-2">
                            <input type="checkbox" class="form-check-input" id="multipleVariantsUseVV" name="multipleVariantsUseVV">
                            <label class="form-check-label mx-2" for="multipleVariantsUseVV">Besides checking the syntax, also use VariantValidator.org to validate these variants on the sequence level (slower)</label>
                        </div>
                        <div class="py-2 d-flex justify-content-between">
                            <div>
                                <button class="btn btn-primary" type="submit" id="multipleVariantsButton">Validate these variant descriptions</button>
                            </div>
                            <div>
                                <button class="btn btn-primary d-none" id="multipleVariantsDownloadButton">Download these results</button>
                            </div>
                        </div>
                    </FORM>
                    <DIV class="py-2" id="multipleVariantsResponse"></DIV>
                </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

<SCRIPT type="text/javascript">
    // Disable buttons when there is nothing to submit.
    $("#hgvsTabsContent").find(
        "input[type=text], textarea"
    ).keyup(
        function ()
        {
            if ($(this).val() == '') {
                $(this).parents('form').find('button').prop('disabled', true);
            } else {
                $(this).parents('form').find('button').prop('disabled', false);
            }
        }
    ).keyup();

    // Set handlers for buttons. Do this once, because every definition of .click() will just add up, not overwrite.
    // Disable buttons when clicked, indicate the process is loading.
    $("#hgvsTabsContent").find("button[type='submit']").click(
        function ()
        {
            // Disable the button and show it's busy.
            $(this).prop('disabled', true).append('\n&nbsp;\n<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            // Empty previous result.
            $("#" + this.id.replace('Button', '') + "Response").html("");
            // Remove download button, in case it's shown.
            $("#" + this.id.replace('Button', '') + "DownloadButton").addClass("d-none");
            $(this).parents("form").submit();
            return false;
        }
    );
    $("#hgvsTabsContent").find("button[id$='DownloadButton']").click(
        function ()
        {
            $(this).prop('disabled', true).append('\n&nbsp;\n<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            downloadResponse(this.id.replace('DownloadButton', ''));
            return false; // Don't submit the form.
        }
    );



    function showResponse (sMethod)
    {
        // This function sends the data over to the ajax script, formats, and displays the response.
        var oCard = null;

        if (typeof sMethod == 'string' && sMethod.length > 0 && $("#" + sMethod).length == 1) {
            // We received a string linking to the input form.
            var sInput = $("#" + sMethod).val();
            var bCallVV = $("#" + sMethod + "UseVV").is(":checked");

        } else {
            // We received nothing, a faulty object, or a string that doesn't lead us to the input field.
            alert("showResponse() called with an incorrect method.");
            return false;
        }

        $.getJSON(
            "ajax.php?var=" + encodeURIComponent(sInput) + "&callVV=" + bCallVV,
            function (data)
            {
                // If we get here, the JSON was already parsed, and we know it was successful.
                // We should have received an array with variants.

                // Loop through the results.
                $.each(
                    data,
                    function (i, aVariant)
                    {
                        var sVariant = aVariant.input;
                        // Style used, icon used?
                        var sStyle = (aVariant.color == 'green'? 'success' : (aVariant.color == 'orange'? 'warning' : 'danger'));
                        var sIcon = (aVariant.valid == null? 'question' : (aVariant.color == 'orange'? 'exclamation' : (aVariant.valid? 'check' : 'x'))) + '-circle-fill';
                    }
                );

                return false;
            }
        ).fail(
            function()
            {
                alert("Error checking variant, please try again later. If the problem persists, please contact us at Ivo@LOVD.nl and send us the input you used.");
            }
        );
        return false;
    }
</SCRIPT>

</body>
</html>
