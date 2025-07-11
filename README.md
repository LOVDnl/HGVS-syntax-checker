# LOVD HGVS Library & Variant Description Syntax Checker
The LOVD HGVS library is a standalone, sequence-agnostic validation library for HGVS-compliant variant descriptions,
 designed to detect and correct syntax errors, formatting issues, and common user mistakes.
It powers the [LOVD HGVS Variant Description Syntax Checker](https://LOVD.nl/HGVS), a tool capable of validating,
 correcting, and standardizing variant descriptions even without a reference sequence.





## Main features
- **Comprehensive HGVS Syntax Validation.**
  Recognizes valid variant descriptions and detects syntax errors according to HGVS nomenclature guidelines.
  The LOVD HGVS library supports more of the HGVS nomenclature than any other validation tool,
   and has been trained to understand all kinds of invalid input.
  Unlike sequence-level validation tools, using reference sequences is not necessary with the LOVD HGVS library.
  However, if a reference sequence is available, after the variant description syntax has been validated, a tool like
   [Mutalyzer](https://mutalyzer.nl) or [VariantValidator](https://variantvalidator.org) is required for full validation
   of the variant on sequence level.
- **Automated corrections with confidence scores.**
  Provides suggested corrections for invalid descriptions, ranked by likelihood.
  When multiple interpretations are possible, the library will provide multiple possible corrections.
- **Supports multiple variant formats.**
  Accepts legacy variant descriptions, VCF and similar formats such as SPDI,
   and converts them into HGVS-compliant syntax.
- **Handles formatting issues.**
  Detects and corrects misformatted characters (e.g., en- and em-dashes where hyphens are needed) and spacing issues,
   commonly introduced by the formatting of variant descriptions in PDFs.
- **Lightweight & easy to integrate.**
  Can be used as a PHP library or as a standalone command-line tool,
   making it easy to integrate into existing pipelines, databases, or web applications.
- **API and online interface available for low-volume requests.**
  An [API](https://api.lovd.nl/) and the [LOVD HGVS Variant Description Syntax Checker](https://LOVD.nl/HGVS)
   are available for low-volume requests and small batch requests.
  The online Syntax Checker also allows for sequence-level validation of DNA or RNA variants
   with the [VariantValidator](https://variantvalidator.org) software.





## Who should use this?
- Developers working on genomic variant databases, analysis pipelines,
   or reporting tools that require HGVS-compliant variant descriptions.
- Bioinformaticians & Researchers looking for a reliable tool to validate and correct variant descriptions
   extracted from databases or publications.
- Diagnostic & Clinical Genetics laboratories that need automated HGVS validation
   as part of variant interpretation workflows.





## Getting Started
### The online LOVD HGVS Variant Description Syntax Checker
For low-volume requests and no requirement for downloads or programming,
 feel free to use the online [LOVD HGVS Variant Description Syntax Checker](https://LOVD.nl/HGVS).
The interface should be self-explanatory.
Use the "Check a list of variants" button to check a list of variants.
The interface allows you to create a download of your results, too;
 use the "Download this result" for a tab-delimited file.

The online interface only considers variant descriptions as valid and allows reference sequences to be missing.
For pro users, this means that `requireVariant()` and `allowMissingReferenceSequence()` are activated.



### The API
For direct programmatic access to the basic features, use the [free API](https://api.lovd.nl/).
The LOVD API includes the LOVD HGVS library starting from version 2.
To access the LOVD HGVS library, use the "checkHGVS" API endpoint.
When using the API web interface, make sure "v2 (2025-02-19)" is selected in the top bar of the page.
When calling the API directly, use "v2" in the URL.
See our full [API documentation](https://github.com/LOVDnl/api.lovd.nl/) for how to use the API.

No registration is needed, but to keep the service available to all,
 please keep the number of requests at a maximum of five variants per second, or one batch request per second.

The API only considers variant descriptions as valid and allows reference sequences to be missing.
For pro users, this means that `requireVariant()` and `allowMissingReferenceSequence()` are activated.



### The LOVD HGVS library
#### As a standalone tool run directly on the system (e.g., from Bash or Python).
If you have PHP-cli installed, you can run the library directly from the command-line:

```bash
php -f HGVS.php "NM_004006.3:c.157C>T"
```

The output is basically the same as the API's output, minus the API wrapper:

```json
[
  {
    "input": "NM_004006.3:c.157C>T",
    "identified_as": "full_variant_DNA",
    "identified_as_formatted": "full variant (DNA)",
    "valid": true,
    "messages": [],
    "warnings": [],
    "errors": [],
    "data": {
      "position_start": 157,
      "position_end": 157,
      "position_start_intron": 0,
      "position_end_intron": 0,
      "range": false,
      "type": ">"
    },
    "corrected_values": {
      "NM_004006.3:c.157C>T": 1
    }
  }
]
```

Multiple input values can be passed:

```bash
php -f HGVS.php "NM_004006.3:c.157C>T" "NC_000015.9:g.40699840C>T"
```

To retrieve information about the library, pass `versions` or `getVersions`, like:

```bash
php -f HGVS.php versions
php -f HGVS.php getVersions
```

This returns:

```json
[
    {
        "library_date": "2025-07-08",
        "library_version": "0.5.0",
        "HGVS_nomenclature_versions": {
            "input": {
                "minimum": "15.11",
                "maximum": "21.1.3"
            },
            "output": "21.1.3"
        },
        "caches": {
          "genes": "2025-05-01"
        }
    }
]
```

Note that, unlike the API, the defaults apply.
The HGVS class will also successfully validate reference sequences,
 VCF descriptions, genes, genome builds, and variant identifiers.
If you only wish to consider variants as valid input, check the `identified_as` field, or use a PHP wrapper (see below).

If you wish to enforce a gene or reference sequence check,
 use `gene:` or `refseq:` as prefixes to your input, respectively, like:

```bash
php -f HGVS.php gene:IVD
```

This returns:

```json
[
    {
        "input": "IVD",
        "identified_as": "gene_symbol",
        "identified_as_formatted": "gene symbol",
        "valid": true,
        "messages": [],
        "warnings": [],
        "errors": [],
        "data": {
            "hgnc_id": 6186
        },
        "corrected_values": {
            "IVD": 1
        }
    }
]
```

This speeds up the check since only some of the valid patterns are checked.
Also, it improves the output when the input is invalid.
E.g., when enforcing a gene check,
 invalid input will provide an error mentioning that no gene symbol or identifier could be recognized.

#### From within a PHP application
When already coding in PHP, it's easy to just include the `HGVS.php` library file and start using it.
Using this method, you'll have full access to all features the library can offer.

```php
<?php
// Include the HGVS.php file.
require 'path/to/HGVS.php';

// Check all version info.
$aVersions = HGVS::getVersions();
// [
//     "library_date" => "2025-03-26",
//     "library_version" => "0.4.2",
//     "HGVS_nomenclature_versions" => [
//         "input" => [
//             "minimum" => "15.11",
//             "maximum" => "21.1.2"
//         ],
//         "output" => "21.1.2"
//     ]
// ]

// Check some input. Note that, by default, the LOVD HGVS library searches for variants
//  (with or without reference sequences), reference sequences, VCF strings, genome builds, and variant identifiers.
// Use the default HGVS class for all options. 
$HGVS = HGVS::check('NM_004006.3'); // Returns an object.

// Returns True if the library matched something (not necessarily a variant).
var_dump($HGVS->hasMatched()); // True.

// Returns True if the library matched something and considers the input as valid.
var_dump($HGVS->isValid()); // True, because this is a valid reference sequence.

// To quickly check if something is a variant description (not necessarily a valid description), use:
var_dump($HGVS->isAVariant()); // False.

// As an alternative, make sure that we only consider variant input as valid:
$HGVS->requireVariant();
var_dump($HGVS->isValid()); // Now, it returns False.

// This can be combined like:
var_dump(HGVS::check('NM_004006.3')->requireVariant()->isValid()); // False.
// Or, shorter:
var_dump(HGVS::checkVariant('NM_004006.3')->isValid()); // False.

// If you're not interested in variants, you can also directly use other classes, like so:
var_dump(HGVS_ReferenceSequence::check('NM_004006.3')->isValid()); // True.
var_dump(HGVS_Genome::check('GRCh38')->isValid()); // True. We recognize hg18, hg19, hg38, GRCh36, GRCh37, and GRCh38.
var_dump(HGVS_VariantIdentifier::check('rs123456')->isValid()); // True. Note that, also this, is just a syntax check.



// Now, try a variant description without a reference sequence.
$HGVS = HGVS::checkVariant('c.157C>T');

// Returns True if the library matched something (still, not necessarily a variant).
var_dump($HGVS->hasMatched()); // True.

// Returns True if the library matched a variant and considers the input as valid.
var_dump($HGVS->isValid()); // False, because a reference sequence is required according to the HGVS nomenclature.

// No longer consider not having a reference sequence as invalid.
$HGVS->allowMissingReferenceSequence();
var_dump($HGVS->isValid()); // Now, it returns True.

// Return all information from the library about the given input.
var_dump($HGVS->getInfo());
// [
//     "input" => "c.157C>T",
//     "identified_as" => "variant_DNA",
//     "identified_as_formatted" => "variant (DNA)",
//     "valid" => true,
//     "messages" => [
//         "IREFSEQMISSING" => "This variant is missing a reference sequence."
//     ],
//     "warnings" => [],
//     "errors" => [],
//     "data" => [
//         "position_start" => 157,
//         "position_end" => 157,
//         "position_start_intron" => 0,
//         "position_end_intron" => 0,
//         "range" => false,
//         "type" => ">"
//     ],
//     "corrected_values" => [
//         "c.157C>T" => 1
//     ]
// ]

// If you want to have a subset of the above, the method getInfo() is internally defined as:
// return array_merge(
//     [
//         'input' => $this->getInput(),
//         'identified_as' => $this->getIdentifiedAs(),
//         'identified_as_formatted' => $this->getIdentifiedAsFormatted(),
//         'valid' => $this->isValid(),
//     ],
//     $this->getMessagesByGroup(),
//     [
//         'data' => $this->getData(),
//         'corrected_values' => $this->getCorrectedValues(),
//     ]
// );

// The above can again be combined like so:
var_dump(HGVS::checkVariant('c.157C>T')->allowMissingReferenceSequence()->getInfo());



// Meant for databases, where sometimes filling in a reference sequence is not allowed.
$HGVS->requireMissingReferenceSequence();

// Can be combined like so:
var_dump(HGVS::checkVariant('c.157C>T')->requireMissingReferenceSequence()->isValid()); // True.
var_dump(HGVS::checkVariant('NM_004006.3:c.157C>T')->isValid()); // True.
var_dump(HGVS::checkVariant('NM_004006.3:c.157C>T')->requireMissingReferenceSequence()->isValid()); // False.
```

The LOVD HGVS library offers much, much more.
Check the source code for a full list of all useful methods and variables within classes.
A few more have been highlighted below.

```php
// Get properties of this object, to get more information about how a variant was parsed.
$HGVS = HGVS::checkVariant('c.157C>T');
var_dump($HGVS->getProperties());
// [
//     "Variant"
// ]

// Using the property names, you can dive deeply into the parsed variant.
var_dump($HGVS->Variant->getProperties());
// [
//     "DNAPrefix",
//     "Dot",
//     "DNAVariantBody"
// ]



// We haven't documented each class in detail, so feel free to use var_dump()
//  to explore an object's structure. Two examples of the possibilities:
// 1) Is a variant intronic?
var_dump($HGVS->Variant->DNAVariantBody->DNAPositions->intronic); // False.
// 2) Get an array of HGVS_DNAInsSuffixComplexComponent objects for a complex insertion:
$aComponents = HGVS::check("c.419_420ins[T;450_470;AGGG]")->Variant->DNAVariantBody
               ->DNAVariantType->DNAInsSuffix->DNAInsSuffixComplex->getComponents();



// Useful for parsing text; could the given input be incomplete?
// 1) False, because this reference sequence is complete.
var_dump(HGVS_ReferenceSequence::check('NM_004006.3')->isPossiblyIncomplete());
// 2) True, because now, we're not just checking for a reference sequence,
//     and this could be just the start of a variant description.
var_dump(HGVS::check('NM_004006.3')->isPossiblyIncomplete());



// With debugging mode, the class will output a large amount of text with
//  which you can follow the logic used to match your input.
// Turn on output buffering if you wish to collect this output into a variable.
$HGVS = HGVS::debug('c.157C>T');
```





## Features that require configuration
### Gene symbol recognition
The LOVD HGVS library contains a feature that recognizes and corrects gene symbols.
In order for this to work, the library relies on a local copy of the official gene symbol list
 from the HUGO Gene Nomenclature Committee (HGNC).
To obtain this copy, an update script needs to be run on a regular basis; for instance, once a month.
The update script is located in the `cache` directory.
You can invoke it like:

```bash
php -f cache/update.php
```

This can be automated by using, for instance, cron jobs.
Make sure that the user who runs the script has write access to the `cache` directory.
