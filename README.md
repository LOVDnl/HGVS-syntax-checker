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
