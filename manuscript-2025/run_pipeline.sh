#!/bin/bash
# Created  2024-12-30
# Modified 2025-01-10

# Extract the data.
PREFIX="HGVS_list.$(date +%Y-%m-%d)";
OUT="$PREFIX.A.raw.txt";
if [ ! -f "$OUT" ];
then
    ./parse_HGVS-website /www/git/hgvs-nomenclature/ > $OUT;
fi;



# Clean the data (this is a manual step; debugging information and false positives are still in there).
IN=$OUT;
OUT="$PREFIX.B.cleaned.txt";
if [ ! -f "$OUT" ];
then
    cat $IN | grep -v ^Parsing > $OUT;

    echo "Please manually clean the data in $OUT, and press any key when done.";
    read;
fi;
