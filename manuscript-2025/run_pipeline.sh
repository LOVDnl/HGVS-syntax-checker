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



# Do we have meld installed?
MELD=$(which meld | wc -l);

# Process the data.
IN=$OUT;
OUT="$PREFIX.C.validated.txt";
if [ ! -f $OUT ];
then
    ./check-HGVS $IN > $OUT;
else
    OUTNEW=$(echo $OUT | sed 's/.txt/.new.txt/');
    ./check-HGVS $IN > $OUTNEW;

    # If there is no change, there is nothing to do.
    if [ "$(diff -q $OUT $OUTNEW | wc -l)" -eq "0" ];
    then
        rm $OUTNEW;
        echo "There is no difference in the validation, so skipping the rest of the steps.";
        exit 1;
    fi;

    echo "Differences detected in $OUT.";
    if [ $MELD -gt 0 ];
    then
        meld $OUT $OUTNEW;
    else
        diff -u $OUT $OUTNEW | less -SM;
    fi
fi;



# Isolate variants that show a difference in result.
IN=$OUT;

# The worst category first; example variants that I don't recognize.
OUT="$PREFIX.D.diff.true-valid-but-not-recognized.txt";
if [ ! -f $OUT ];
then
    grep -E "(^input|\svalid\sinvalid)" $IN > $OUT;
else
    OUTNEW=$(echo $OUT | sed 's/.txt/.new.txt/');
    grep -E "(^input|\svalid\sinvalid)" $IN > $OUTNEW;

    if [ "$(diff -q $OUT $OUTNEW | wc -l)" -eq "0" ];
    then
        echo "No differences detected in $OUT.";
        rm $OUTNEW;
    else
        echo "Differences detected in $OUT.";
        if [ $MELD -gt 0 ];
        then
            meld $OUT $OUTNEW;
        else
            diff -u $OUT $OUTNEW | less -SM;
        fi
    fi;
fi;

# Then, invalid variants that I think are valid.
OUT="$PREFIX.D.diff.true-invalid-but-considered-valid.txt";
if [ ! -f $OUT ];
then
    grep -E "(^input|\sinvalid\!?\svalid)" $IN > $OUT;
else
    OUTNEW=$(echo $OUT | sed 's/.txt/.new.txt/');
    grep -E "(^input|\sinvalid\!?\svalid)" $IN > $OUTNEW;

    if [ "$(diff -q $OUT $OUTNEW | wc -l)" -eq "0" ];
    then
        echo "No differences detected in $OUT.";
        rm $OUTNEW;
    else
        echo "Differences detected in $OUT.";
        if [ $MELD -gt 0 ];
        then
            meld $OUT $OUTNEW;
        else
            diff -u $OUT $OUTNEW | less -SM;
        fi
    fi;
fi;
