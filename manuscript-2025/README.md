# LOVD HGVS Library & Variant Description Syntax Checker
Files related to the manuscript "Filling the gap in the validation of DNA variant descriptions;
 the LOVD HGVS variant description syntax checker".





## Figures, scripts, and data files
This directory contains all figures shown in the manuscript, the data files related to the analysis
 of the LOVD HGVS library, Mutalyzer, and VariantValidator, and the scripts to create the data files.



### Figures
| Filename                                           | Description                                                                      |
|----------------------------------------------------|----------------------------------------------------------------------------------|
| [`figures.tex`](figures/figures.tex)               | LaTeX source code for figures 1, 3, and 4. Use LaTeX to geneerate `figures.pdf`. |
| [`figures_02.ori.png`](figures/figures_02.ori.png) | Original version of figure 2.                                                    |
| [`figures_02.png`](figures/figures_02.png)         | Final version (cropped and edited) of figure 2.                                  |
| [`create_figures.sh`](figures/create_figures.sh)   | Script that converts `figures.pdf` into separate JPG files.                      |



### Scripts to build the data files
| Filename                                   | Description                                                                                                       |
|--------------------------------------------|-------------------------------------------------------------------------------------------------------------------|
| [`parse_HGVS-website`](parse_HGVS-website) | Script that parses the HGVS Nomenclature website repository and isolates variant descriptions.                    |
| [`check-HGVS`](check-HGVS)                 | Script that runs the LOVD HGVS library over a variant list.                                                       |
| [`run_mutalyzer`](run_mutalyzer)           | Script that collects the Mutalyzer results using their API.                                                       |
| [`run_pipeline.sh`](run_pipeline.sh)       | Pipeline script that runs `parse_HGVS-website`, `check-HGVS`, and `run_mutalyzer` to generate all the data files. |
