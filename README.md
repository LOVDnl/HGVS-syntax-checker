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
        "library_version": "2025-02-12",
        "HGVS_nomenclature_versions": {
            "input": {
                "minimum": "15.11",
                "maximum": "21.1.1"
            },
            "output": "21.1.1"
        }
    }
]
```

Note that, unlike the API, the defaults apply.
The HGVS class will also successfully validate reference sequences,
 VCF descriptions, genome builds, and variant identifiers.
If you only wish to consider variants as valid input, check the `identified_as` field, or use a PHP wrapper (see below).

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
//     "library_version" => "2025-02-13",
//     "HGVS_nomenclature_versions" => [
//         "input" => [
//             "minimum" => "15.11",
//             "maximum" => "21.1.1"
//         ],
//         "output" => "21.1.1"
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

// Make sure that we only consider variant input as valid.
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
