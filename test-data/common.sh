#!/bin/bash

function mktemp_out {
    IN=$1
    EXT=$2

    OUT=`mktemp -u -t ter-"$IN"XXXXXX`
    if [ $? -ne 0 ]; then
	echo "Error creating temp file!" 1>&2
	exit 1
    fi
    OUT_EXT="$OUT$EXT"
    echo "$OUT_EXT"
}
