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
    "$TE_HOME"/lib/engines/tika2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_odt2doc {
    TESTIN="$TE_HOME"/test-data/test.odt
    TESTOUT=`mktemp_out test.odt .doc`

    echo
    echo "* Checking conversion from ODT to MS-Word..."
    "$TE_HOME"/lib/engines/ooo2doc "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "  Error: Conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_doc2pdf {
    TESTIN="$TE_HOME"/test-data/test.doc
    TESTOUT=`mktemp_out test.doc .pdf`

    echo
    echo "* Checking conversion from MS-Word 97 to PDF..."
    "$TE_HOME"/lib/engines/ooo2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "  Error: Conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_docx2pdf {
    TESTIN="$TE_HOME"/test-data/test.docx
    TESTOUT=`mktemp_out test.docx .pdf`

    echo
    echo "* Checking conversion from MS-Word 2007 Open XML to PDF..."
    "$TE_HOME"/lib/engines/ooo2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "  Error: Conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_doc2pdfa {
    TESTIN="$TE_HOME"/test-data/test.doc
    TESTOUT=`mktemp_out test.doc .pdfa`

    echo
    echo "* Checking conversion from MS-Word 97 to PDF/A-1..."
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

function check_docx2pdfa {
    TESTIN="$TE_HOME"/test-data/test.docx
    TESTOUT=`mktemp_out test.docx .pdfa`

    echo
    echo "* Checking conversion from MS-Word 2007 Open XML to PDF/A-1..."
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

function check_doc2txt {
    TESTIN="$TE_HOME"/test-data/test.doc
    TESTOUT=`mktemp_out test.doc .txt`

    echo
    echo "* Checking conversion from MS-Word 97 to TXT..."
    "$TE_HOME"/lib/engines/tika2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_docx2txt {
    TESTIN="$TE_HOME"/test-data/test.docx
    TESTOUT=`mktemp_out test.docx .txt`

    echo
    echo "* Checking conversion from MS-Word 2007 Open XML to TXT..."
    "$TE_HOME"/lib/engines/tika2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_ppt2pdf {
    TESTIN="$TE_HOME"/test-data/test.ppt
    TESTOUT=`mktemp_out test.ppt .pdf`

    echo
    echo "* Checking conversion from MS-PowerPoint 97 to PDF..."
    "$TE_HOME"/lib/engines/ooo2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_pptx2pdf {
    TESTIN="$TE_HOME"/test-data/test.pptx
    TESTOUT=`mktemp_out test.ppt .pdf`

    echo
    echo "* Checking conversion from MS-PowerPoint 2007 Open XML to PDF..."
    "$TE_HOME"/lib/engines/ooo2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_ppt2txt {
    TESTIN="$TE_HOME"/test-data/test.ppt
    TESTOUT=`mktemp_out test.ppt .txt`

    echo
    echo "* Checking conversion from MS-PowerPoint 97 to TXT..."
    "$TE_HOME"/lib/engines/tika2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_pptx2txt {
    TESTIN="$TE_HOME"/test-data/test.pptx
    TESTOUT=`mktemp_out test.pptx .txt`

    echo
    echo "* Checking conversion from MS-PowerPoint 2007 Open XML to TXT..."
    "$TE_HOME"/lib/engines/tika2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_xls2pdf {
    TESTIN="$TE_HOME"/test-data/test.3sheet.97.xls
    TESTOUT=`mktemp_out test.3sheet.97.xls .pdf`

    echo
    echo "* Checking conversion from MS-Excel 97 to PDF..."
    "$TE_HOME"/lib/engines/ooo2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_xlsx2pdf {
    TESTIN="$TE_HOME"/test-data/test.3sheet.xlsx
    TESTOUT=`mktemp_out test.3sheet.xlsx .pdf`

    echo
    echo "* Checking conversion from MS-Excel 2007 Open XML to PDF..."
    "$TE_HOME"/lib/engines/ooo2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_xls2txt {
    TESTIN="$TE_HOME"/test-data/test.3sheet.97.xls
    TESTOUT=`mktemp_out test.3sheet.97.xls .txt`

    echo
    echo "* Checking conversion from MS-Excel 97 to TXT..."
    "$TE_HOME"/lib/engines/tika2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_xlsx2txt {
    TESTIN="$TE_HOME"/test-data/test.3sheet.xlsx
    TESTOUT=`mktemp_out test.3sheet.xlsx .txt`

    echo
    echo "* Checking conversion from MS-Excel 2007 Open XML to TXT..."
    "$TE_HOME"/lib/engines/tika2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
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

    "$TE_HOME"/lib/engines/tika2txt "$TESTIN" "$TESTOUT"
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
    "$TE_HOME"/lib/engines/tika2txt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_txtlat12pdf {
    TESTIN="$TE_HOME"/test-data/test.lat1.txt
    TESTOUT=`mktemp_out test.lat1.txt .pdf`

    echo
    echo "* Checking conversion from Latin1 TXT to PDF..."
    "$TE_HOME"/lib/engines/txt2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_txtutf82pdf {
    TESTIN="$TE_HOME"/test-data/test.utf8.txt
    TESTOUT=`mktemp_out test.utf8.txt .pdf`

    echo
    echo "* Checking conversion from UTF-8 TXT to PDF..."
    "$TE_HOME"/lib/engines/txt2pdf "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_mergeodt {
    TESTIN="$TE_HOME"/test-data/test.zip
    TESTOUT=`mktemp_out test.mergeodt.zip .odt`

    echo 
    echo "* Checking conversion from ZIP to ODT..."
    "$TE_HOME"/lib/engines/zip2star odt "$TESTIN" "$TESTOUT"
    SIZE=`stat -c %s "$TESTOUT" 2> /dev/null`

    if [ ! -f "$TESTOUT" -o "$SIZE" = "0" ]; then
	echo "Error: conversion of '$TESTIN' to '$TESTOUT' failed!"
	return 1
    fi
    echo "  Ok: '$TESTOUT' ($SIZE bytes)"
    return 0
}

function check_mergepdfa {
    TESTIN="$TE_HOME"/test-data/test.zip
    TESTOUT=`mktemp_out test.mergepdfa.zip .pdfa`

    echo 
    echo "* Checking conversion from ZIP to PDF/A..."
    "$TE_HOME"/lib/engines/zip2star pdfa "$TESTIN" "$TESTOUT"
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


EXITCODE=0
for CHECK in \
    \
    check_odt2pdf \
    check_odt2pdfa \
    check_odt2txt \
    check_odt2doc \
    \
    check_doc2pdf \
    check_docx2pdf \
    check_doc2pdfa \
    check_docx2pdfa \
    check_doc2txt \
    check_docx2txt \
    \
    check_ppt2pdf \
    check_pptx2pdf \
    check_ppt2txt \
    check_pptx2txt \
    \
    check_xls2pdf \
    check_xlsx2pdf \
    check_xls2txt \
    check_xlsx2txt \
    \
    check_html2odt \
    check_html2pdf \
    check_html2pdfa \
    check_html2txt \
    \
    check_pdf2txt \
    check_txtlat12pdf \
    check_txtutf82pdf \
    \
    check_mergeodt \
    check_mergepdfa \
    \
    ; do

    "$CHECK"
    RET=$?
    if [ $RET -ne 0 ]; then
	EXITCODE=1
    fi
done

exit $EXITCODE