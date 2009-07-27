#!/bin/bash

if [ -z "$TE_HOME" ]; then
    echo "TE_HOME not defined!"
    exit 1
fi

. "$TE_HOME"/etc/te.conf
. "$TE_HOME"/etc/te.d/env

function mktemp_out {
    IN=$1
    EXT=$2

    OUT=`mktemp -u -t "$IN"XXXXXX`
    if [ $? -ne 0 ]; then
	echo "Error creating temp file!" 1>&2
	exit 1
    fi
    OUT_EXT="$OUT$EXT"
    echo "$OUT_EXT"
}

function check_odt2pdf {
    TESTIN="$TE_HOME"/test-data/test.odt
    TESTOUT=`mktemp_out test.odt .pdf`

    echo
    echo "* Checking conversion from ODT to PDF..."
    "$TE_HOME"/lib/engines/ooo2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "  Error: Conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_odt2pdfa {
    TESTIN="$TE_HOME"/test-data/test.odt
    TESTOUT=`mktemp_out test.odt .pdfa`

    echo
    echo "* Checking conversion from ODT to PDF/A-1..."
    "$TE_HOME"/lib/engines/ooo2pdfa "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "  Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi

    grep "\/GTS_PDFA1\>" "$TESTOUT" 1> /dev/null 2>&1
    RET=$?
    if [ $RET -ne 0 ]; then
	echo "  Warning: '$TESTOUT' ($SIZE bytes) does not seems to be a PDF/A-1 file!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_odt2txt {
    TESTIN="$TE_HOME"/test-data/test.odt
    TESTOUT=`mktemp_out test.odt .txt`

    echo
    echo "* Checking conversion from ODT to TXT..."
    "$TE_HOME"/lib/engines/ooo2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTING to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_html2odt {
    TESTIN="$TE_HOME"/test-data/test.html
    TESTOUT=`mktemp_out test.html .odt`

    echo
    echo "* Checking conversion from HTML to ODT..."
    "$TE_HOME"/lib/engines/html2odt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_html2pdf {
    TESTIN="$TE_HOME"/test-data/test.html
    TESTOUT=`mktemp_out test.html .pdf`

    echo
    echo "* Checking conversion from HTML to PDF..."
    "$TE_HOME"/lib/engines/html2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_html2pdfa {
    TESTIN="$TE_HOME"/test-data/test.html
    TESTOUT=`mktemp_out test.html .pdfa`

    echo
    echo "* Checking conversion from HTML to PDF/A-1..."
    "$TE_HOME"/lib/engines/html2pdfa "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi

    grep "\/GTS_PDFA1\>" "$TESTOUT" 1> /dev/null 2>&1
    RET=$?
    if [ $RET -ne 0 ]; then
	echo "  Warning: '$TESTOUT' ($SIZE bytes) does not seems to be a PDF/A-1 file!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_html2txt {
    TESTIN="$TE_HOME"/test-data/test.html
    TESTOUT=`mktemp_out test.html .txt`

    echo
    echo "* Checking conversion from HTML to TXT..."

    "$TE_HOME"/lib/engines/html2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_pdf2txt {
    TESTIN="$TE_HOME"/test-data/test.pdf
    TESTOUT=`mktemp_out test.pdf .txt`

    echo
    echo "* Checking conversion from PDF to TXT..."
    "$TE_HOME"/lib/engines/pdf2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_txt2pdf {
    TESTIN="$TE_HOME"/test-data/test.txt
    TESTOUT=`mktemp_out test.txt .pdf`

    echo
    echo "* Checking conversion from TXT to PDF..."
    "$TE_HOME"/lib/engines/txt2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

EXITCODE=0
for CHECK in \
    \
    check_odt2pdf \
    check_odt2pdfa \
    check_odt2txt \
    \
    check_html2odt \
    check_html2pdf \
    check_html2pdfa \
    check_html2txt \
    \
    check_pdf2txt \
    check_txt2pdf \
    ; do

    "$CHECK"
    RET=$?
    if [ $RET -ne 0 ]; then
	EXITCODE=1
    fi
done

exit $EXITCODE