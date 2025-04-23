#!/bin/bash

# Check: have at least one argument.
if [[ -z $1 ]]
then
  echo "Usage: $0 <pdf_file>";
  exit 1
fi

if [[ ! -f "${1}" ]]
then
  echo "File not found!";
  exit 2;
fi

if [ "$(which pdftk | wc -l)" -eq "0" ];
then
    echo "This script requires the tool 'pdftk' to be present. Please install it.";
    exit 1;
fi

if [ "$(which convert | wc -l)" -eq "0" ];
then
    echo "This script requires the tool 'convert' (from ImageMagick) to be present. Please install it.";
    exit 1;
fi

# If multi-page PDFs are converted straight to JPGs, the colors can change.
# I don't know why, but it's best to first burst the file.
pdftk "${1}" burst output figures_%02d.pdf;
if [ "$?" -eq "0" ];
then
    rm doc_data.txt;
    rm figures_02.pdf;
else
    echo "Failed to burst the PDF into separate pages.";
    exit 1;
fi;

# Convert PDF to JPGs.
for file in figures_??.pdf;
do
  convert -density 500 $file -background white -alpha remove -chop 0%x10% -trim +repage -bordercolor white -border 50x50 "$(echo $file | sed 's/pdf/jpg/')";
  echo -n ".";
done;
echo ""; # Echo a newline.
