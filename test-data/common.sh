#!/bin/bash

function mktemp_out {
    local IN=$1
    local EXT=$2

    local OUT=$(mktemp -u -t "ter-$IN-XXXXXX")
    if [ $? -ne 0 ]; then
	echo "Error creating temp file!" 1>&2
	exit 1
    fi
    echo "$OUT$EXT"
}
