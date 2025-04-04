<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2021-12-03
 * Modified    : 2025-03-26
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
require '../HGVS.php'; // Just for the footer, but better load it here.
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
    <style type="text/css">
        .bg-warning {
            background-color: var(--bs-orange) !important;
        }
        .border-warning {
            border-color: var(--bs-orange) !important;
        }
        .list-group-item-warning {
            color:#83410b;
            background-color:#fdd2af;
        }
        footer {
            color: var(--bs-alert-color);
            background-color: var(--bs-alert-bg);
            border-top: var(--bs-alert-border);
        }
    </style>

    <title>LOVD HGVS variant description syntax checker</title>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

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
            <li class="nav-item" role="presentation">
                <a class="nav-link" href="https://github.com/LOVDnl/HGVS-syntax-checker/" target="_blank">
                    <i class="bi bi-github"></i>
                    GitHub
                </a>
            </li>
        </ul>

        <div class="tab-content" id="hgvsTabsContent">
                <div class="py-3 tab-pane fade show active" id="single" role="tabpanel">
                    <FORM onsubmit="showResponse('singleVariant'); return false;" action="">
                        <div class="py-2">
                            <input type="text" class="form-control" id="singleVariant" name="singleVariant" placeholder="NM_002225.3:c.157C>T" value="<?= (empty($_GET['singleVariant']) || !is_string($_GET['singleVariant'])? '' : htmlspecialchars($_GET['singleVariant'])); ?>">
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
NC_000015.9:g.40699840C>T" rows="5"><?= (empty($_GET['multipleVariants']) || !is_string($_GET['multipleVariants'])? '' : htmlspecialchars(trim($_GET['multipleVariants']))); ?></textarea>
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

<footer class="mt-auto small alert-primary p-2 d-flex d-inline-flex justify-content-between">
    <div>
        <b>LOVD HGVS variant description syntax checker</b>
    </div>
    <div>
        LOVD/HGVS library version:
        <?php
        $aVersions = HGVS::getVersions();
        echo ($aVersions['library_version'] . ' (' . $aVersions['library_date'] . ')' ?? 'unknown');
        ?>
    </div>
    <div>
        &copy; 2024 – 2025 the LOVD team
    </div>
</footer>

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

        if (typeof sMethod == 'object' && 'object' in sMethod && 'variant' in sMethod) {
            // An object has been passed to us. This happens when we need to replace a card.
            oCard = sMethod.object;
            var sInput = sMethod.variant;
            sMethod = $(oCard).parent().attr("id").replace("Response", "");
            var bCallVV = $("#" + sMethod + "UseVV").is(":checked");

        } else if (typeof sMethod == 'string' && sMethod.length > 0 && $("#" + sMethod).length == 1) {
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

                        // Start building up the body (a list of messages).
                        var aMessages = [];

                        if (aVariant.valid) {
                            aVariant.data_status = 'success';
                            aVariant.bootstrap_class = 'success';
                            aVariant.icon = 'check-circle-fill';
                            aMessages.push({'style': aVariant.bootstrap_class, 'icon': aVariant.icon, 'data': 'OK', 'body':
                                    'This variant description\'s syntax is valid.'});
                            if (!bCallVV) {
                                if ('WNOTSUPPORTED' in aVariant.warnings) {
                                    aMessages.push({'style': aVariant.bootstrap_class, 'icon': 'info-circle-fill', 'data': 'Note', 'body':
                                            'This variant has not been validated on the sequence level.' +
                                            ' However, this variant description is not currently supported for sequence-level validation.'});
                                    delete aVariant.warnings['WNOTSUPPORTED'];
                                } else {
                                    aMessages.push({'style': 'secondary', 'icon': 'exclamation-circle-fill', 'data': 'Note', 'body':
                                            'This variant has not been validated on the sequence level.' +
                                            ' For sequence-level validation, please select the VariantValidator option.'});
                                }
                            }

                        } else if (aVariant.valid == null) {
                            aVariant.data_status = 'unsupported';
                            aVariant.bootstrap_class = 'warning';
                            aVariant.icon = 'question-circle-fill';
                            aMessages.push({'style': 'secondary', 'icon': 'exclamation-circle-fill', 'data': 'Note', 'body':
                                aVariant.messages.INOTSUPPORTED});

                        } else if (aVariant.corrected_values_confidence > 0.75) {
                            aVariant.data_status = 'warning';
                            aVariant.bootstrap_class = 'warning';
                            aVariant.icon = 'exclamation-circle-fill';
                            aMessages.push({'style': aVariant.bootstrap_class, 'icon': aVariant.icon, 'data': 'Error', 'body':
                                'This variant description is invalid, but can be corrected with a high confidence (' +
                                    Math.round(aVariant.corrected_values_confidence * 100) + '%).'});

                        } else {
                            // Errors end up here, and warnings with very low confidence.
                            aVariant.data_status = 'error';
                            aVariant.bootstrap_class = 'danger';
                            aVariant.icon = 'x-circle-fill';
                        }

                        // Add errors. As errors can be both an array or an object, let's use jQuery.
                        $.each(
                            aVariant.errors,
                            function (sCode, sError)
                            {
                                var sClassName = 'danger';
                                var sIcon = 'x-circle-fill';
                                var sData = 'Error';
                                aMessages.push({'style': sClassName, 'icon': sIcon, 'data': sData, 'body': sError});
                            }
                        );

                        // Add warnings. As warnings can be both an array or an object, let's use jQuery.
                        $.each(
                            aVariant.warnings,
                            function (sCode, sWarning)
                            {
                                var sClassName = 'warning';
                                var sIcon = 'exclamation-circle-fill';
                                if (sCode == 'WNOTSUPPORTED') {
                                    sClassName = 'secondary';
                                } else if (sCode == 'WPOSITIONSCORRECTED') {
                                    // Ignore this one, because we never auto-fix here.
                                    return;
                                }
                                aMessages.push({'style': sClassName, 'icon': sIcon, 'data': 'Warning', 'body': sWarning});
                            }
                        );

                        // If not VV, but we fixed the variant, mention this.
                        if (!aVariant.valid && !("WCORRECTED" in aVariant.VV) && Object.keys(aVariant.corrected_values).length) {
                            // Not valid but not corrected by VV (VV not run or VV doesn't know).
                            var sMessage = '';
                            if (Object.keys(aVariant.corrected_values).length > 1) {
                                // Multiple corrections are possible.
                                if (aVariant.corrected_values_confidence > 0.5) {
                                    sMessage = 'There are multiple possible corrections.';
                                    for (sCorrection of Object.keys(aVariant.corrected_values)) {
                                        nConfidence = Math.round(aVariant.corrected_values[sCorrection] * 100);
                                        sMessage += ' We are ' + nConfidence +
                                            '% confident that the correct description is <b>' + sCorrection + '</b>.';
                                    }
                                } else {
                                    sMessage = 'There are multiple possible corrections: <b>';
                                    aCorrections = Object.keys(aVariant.corrected_values);
                                    sLastCorrection = aCorrections.pop();
                                    sMessage += aCorrections.join('</b>, <b>') + '</b> or <b>' + sLastCorrection + '</b>.';
                                }

                            } else if (Object.keys(aVariant.corrected_values)[0] != sVariant) {
                                // There is a single correction, and this one is different from the input.
                                sCorrection = Object.keys(aVariant.corrected_values)[0];
                                nConfidence = Math.round(aVariant.corrected_values[sCorrection] * 100);
                                if (nConfidence == 100) {
                                    sMessage = 'The correct variant description is <b>' + sCorrection + '</b>.';
                                } else if (nConfidence > 10) {
                                    sMessage = 'We are ' + nConfidence +
                                        '% confident that the correct description is <b>' + sCorrection + '</b>.';
                                } else {
                                    sMessage = 'It is possible that an improved description is <b>' + sCorrection + '</b>.';
                                }
                            }

                            if (sMessage) {
                                // This message will be edited later.
                                aMessages.push({'style': 'warning', 'icon': 'arrow-right-circle-fill', 'data': 'Correction', 'body': sMessage});
                            }
                        }

                        // Add informative messages, if there are any. As messages can be both an array or an object, let's use jQuery.
                        $.each(
                            aVariant.messages,
                            function (sCode, sWarning)
                            {
                                aMessages.push({'style': 'secondary', 'icon': 'info-circle-fill', 'data': 'Note', 'body': sWarning});
                            }
                        );

                        // Add VV's output, if present. As this can be both an array or an object, let's use jQuery.
                        $.each(
                            aVariant.VV,
                            function (sCode, sMessage)
                            {
                                var sClassName = 'danger';
                                var sIcon = 'x-circle-fill';
                                var sData = 'VariantValidator';
                                if (sCode == 'EFAIL') {
                                    sIcon = 'arrow-right-circle-fill';
                                } else if (sCode == 'EREFSEQMISSING') {
                                    sClassName = 'secondary';
                                    sIcon = 'info-circle-fill';
                                } else if (sCode == 'WCORRECTED') {
                                    sClassName = 'warning';
                                    sIcon = 'arrow-right-circle-fill';
                                    sMessage = sMessage.replace("{{VARIANT}}", '<b>' + Object.keys(aVariant.corrected_values)[0] + '</b>');
                                    sData = 'Correction';
                                } else if (sCode == 'WNOTSUPPORTED') {
                                    sClassName = 'secondary';
                                    sIcon = 'info-circle-fill';
                                } else if (sCode == 'WREFERENCENOTSUPPORTED') {
                                    sClassName = 'warning';
                                    sIcon = 'exclamation-circle-fill';
                                } else if (sCode == 'IOK') {
                                    sClassName = 'success';
                                    sIcon = 'check-circle-fill';
                                }
                                aMessages.push({'style': sClassName, 'icon': sIcon, 'data': sData, 'body': sMessage});
                            }
                        );

                        var sBody = '<ul class="list-group list-group-flush">';
                        aMessages.forEach(
                            function (aMessage)
                            {
                                sBody +=
                                    '<li class="list-group-item list-group-item-' + aMessage.style + ' d-flex" data-type="' + aMessage.data + '">' +
                                    '<i class="bi bi-' + aMessage.icon + ' me-2"></i><div>' +
                                    aMessage.body +
                                    '</div></li>\n';
                            }
                        );
                        sBody += '</ul>';

                        // Add the card to the response field, or replace a card if that is requested.
                        var sCard =
                            '<div class="card w-100 mb-3 border-' + aVariant.bootstrap_class + ' bg-' + aVariant.bootstrap_class + '" data-status="' + aVariant.data_status + '">\n' +
                              '<div class="card-header text-white d-flex justify-content-between">\n' +
                                '<div><h5 class="card-title mb-0"><i class="bi bi-' + aVariant.icon + ' me-1"></i> <b>' + sVariant + '</b></h5></div>\n' +
                                '<div><i class="bi bi-caret-down-fill ps-5"></i></div>\n' +
                              '</div>\n'
                              + sBody + '\n' +
                            '</div>';

                        if (oCard) {
                            $(oCard).replaceWith(sCard);
                        } else {
                            $("#" + sMethod + "Response").append('\n' + sCard);
                        }
                    }
                );

                // Collect and show the stats.
                aCards = $("#" + sMethod + "Response div.card");
                var nVariants = aCards.length;
                var nVariantsSuccess = aCards.filter("[data-status='success']").length;
                var nVariantsNotSupported = aCards.filter("[data-status='unsupported']").length;
                var nVariantsWarning = aCards.filter("[data-status='warning']").length;
                var nVariantsError = aCards.filter("[data-status='error']").length;
                var sAlert =
                    '<div class="alert alert-primary" role="alert">\n' +
                    (sMethod == 'singleVariant' && nVariants == 1? '' :
                        '<div><i class="bi bi-clipboard2-check me-1"></i>' + nVariants + ' variant' + (nVariants == 1? '' : 's') + ' received.</div>\n') +
                    (!nVariantsSuccess? '' :
                        '<div><i class="bi bi-check-circle-fill me-1"></i>' + nVariantsSuccess + ' variant' + (nVariantsSuccess == 1? '' : 's') + ' validated successfully.</div>\n') +
                    (!nVariantsNotSupported? '' :
                        '<div><i class="bi bi-question-circle-fill me-1"></i>' + nVariantsNotSupported + ' variant' + (nVariantsNotSupported == 1? ' is' : 's are') + ' not supported.</div>\n') +
                    (!nVariantsWarning? '' :
                        '<div><i class="bi bi-dash-circle-fill me-1"></i>' + nVariantsWarning + ' variant' + (nVariantsWarning == 1? '' : 's') + ' can be fixed.</div>\n') +
                    (!nVariantsError? '' :
                        '<div><i class="bi bi-exclamation-circle-fill me-1"></i>' + nVariantsError + ' variant' + (nVariantsError == 1? '' : 's') + ' failed to validate.</div>\n') +
                    '</div>';

                // If alert is already present, replace it. Otherwise, add it.
                if ($("#" + sMethod + "Response div.alert").length) {
                    $("#" + sMethod + "Response div.alert").replaceWith(sAlert);
                } else {
                    $("#" + sMethod + "Response").prepend('\n' + sAlert);
                }

                // Reset the submit button.
                $("#" + sMethod + "Button").find("span").remove();
                $("#" + sMethod + "Button").html(
                    $("#" + sMethod + "Button").html().replace(/&nbsp;/g, "").trim()
                ).prop("disabled", false);

                // Enable the download button.
                $("#" + sMethod + "DownloadButton").removeClass("d-none");

                // Add links to suggested corrections, but only if they don't have links already.
                $.each(
                    $(aCards).filter("[data-status='warning'],[data-status='error']").not(':has("a")'),
                    function (index, aCard)
                    {
                        // Add links for entries that can be corrected.
                        var sOriVariant = $(aCard).find("h5").text().trim();
                        $(aCard).find("ul i.bi-arrow-right-circle-fill + div").find("b").each(
                            function (i, oB)
                            {
                                var sNewVariant = $(oB).text().trim();
                                $(oB).html('<a href="#" class="link-dark">' + sNewVariant + '<i class="bi bi-pencil-square ms-1"></i></a>');
                                $(oB).find("a").click(
                                    function ()
                                    {
                                        // Replace the variant in the input.
                                        $("#" + sMethod).val(
                                            $("#" + sMethod).val().replace(
                                                // Note that the variant should be escaped before use within a regex.
                                                // JS doesn't have a standard function for it. Borrowing something from:
                                                //  https://stackoverflow.com/a/3561711.
                                                new RegExp('(^|\n)' + sOriVariant.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + '($|\n)'),
                                                '$1' + sNewVariant + '$2')
                                        );
                                        // Show that we're working here. Leaving bootstrap a bit for pure CSS to overlap the borders of the card as well.
                                        // Just pure Bootstrap (classes start-0 top-0 end-0 bottom-0) will not overlap the border of the card, which is a bit ugly.
                                        $(aCard).append(
                                            '<div class="position-absolute d-flex" style="z-index: 10; background-color: rgba(255, 255, 255, 0.5); width: 102%; height: 102%; left: -1%; top: -1%;">' +
                                            '<div class="w-100 d-flex justify-content-center align-items-center"><div class="spinner-border" role="status"></div></div>' +
                                            '</div>');
                                        // Reset this card only. Call showResponse() with an object,
                                        //  so it understands it's just one card that needs to be replaced.
                                        showResponse({
                                            object: aCard,
                                            variant: sNewVariant
                                        });
                                        return false;
                                    }
                                );
                            }
                        );
                    }
                );

                // Allow cards to close/open, but only if they don't have a handler already.
                // (OK, there's no real way of finding out with a simple selector, so we cheat using data attributes)
                $("#" + sMethod + "Response div.card-header").not("[data-onclick-set]").click(
                    function ()
                    {
                        if ($(this).find("i[class*='bi-caret']").hasClass("bi-caret-down-fill")) {
                            // Hide.
                            $(this).parents("div.card").children("ul").hide();
                            $(this).find("i[class*='bi-caret']").removeClass("bi-caret-down-fill").addClass("bi-caret-left-fill");
                        } else {
                            // Show.
                            $(this).parents("div.card").children("ul").show();
                            $(this).find("i[class*='bi-caret']").removeClass("bi-caret-left-fill").addClass("bi-caret-down-fill");
                        }
                    }
                ).attr("data-onclick-set", true);

                return false;
            }
        ).fail(
            function(jqXHR, textStatus, errorThrown)
            {
                // First, check if maybe the list was too long.
                if (jqXHR.status == 414) {
                    // Request-URI Too Long.
                    alert("The server indicated the list of variants is too long. Please try sending fewer variants per batch.");
                } else {
                    alert("Error checking variant, please try again later. If the problem persists, please contact us at Ivo@LOVD.nl and send us the input you used.");
                }

                // Reset the submit button.
                $("#" + sMethod + "Button").find("span").remove();
                $("#" + sMethod + "Button").html(
                    $("#" + sMethod + "Button").html().replace(/&nbsp;/g, "").trim()
                ).prop("disabled", false);
            }
        );
        return false;
    }



    function downloadResponse (sMethod)
    {
        // Download the result or results into a tab-delimited file.
        if (sMethod == undefined || $("#" + sMethod + "Response") == null) {
            alert("downloadResponse() called with an incorrect method.");
            return false;
        }

        var aCards = $("#" + sMethod + "Response div.card");
        var fileContent = '';

        // Loop through cards and convert them into tab-delimited data.
        $.each(
            aCards,
            function (index, aCard)
            {
                // Collect the body first.
                var sBody = '';
                $(aCard).find("li.list-group-item").each(
                    function ()
                    {
                        // Awkward way of escaping double quotes, but common for spreadsheet users.
                        sBody += $(this).data("type") + ": " + $(this).text().replace(/"/g, '""') + " ";
                    }
                );
                fileContent +=
                    '"' + $(aCard).children("div.card-header").text().trim() + '"\t' +
                    '"' + $(aCard).data("status") + '"\t' +
                    '"' + $(aCard).find("li.list-group-item-warning b").map(function() { return $(this).text(); }).get().join(" or ") + '"\t' +
                    '"' + sBody.trim() + '"\r\n';
            }
        );
        // Use base64 encoding so that Firefox will also create a valid file (was missing newlines and tabs).
        fileContent =
            "data:text/tab-seperated-values;base64," + btoa(
            '"Input"\t"Status"\t"Suggested correction(s)"\t"Messages"\r\n' +
            fileContent + '\r\n');

        var link = document.createElement("a");
        link.setAttribute("href", fileContent);
        var d = new Date();
        // Offset the timezone.
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        link.setAttribute("download", "LOVD_checkHGVS_" + d.toISOString().slice(0, 19) + ".txt");
        document.body.appendChild(link);
        link.click();

        // Reset button.
        $("#" + sMethod + "DownloadButton").find("span").remove();
        $("#" + sMethod + "DownloadButton").html(
            $("#" + sMethod + "DownloadButton").html().replace(/&nbsp;/g, "").trim()
        ).prop("disabled", false);

        // Clean up.
        link.remove();
    }



    // Auto-submit, when requested.
    <?php
        if (!empty($_GET['singleVariant']) && is_string($_GET['singleVariant'])) {
            print('$("#singleVariantButton").click();' . "\n");
        } elseif (!empty($_GET['multipleVariants']) && is_string($_GET['multipleVariants'])) {
            print('$("#mutiple-variants").click();
    $("#multipleVariantsButton").click();' . "\n");
        }
    ?>
</SCRIPT>

</body>
</html>
