#!/bin/bash

if [ -z "$TE_HOME" ]; then
    echo "TE_HOME not defined!"
    exit 1
fi

. "$TE_HOME"/etc/te.conf
. "$TE_HOME"/etc/te.d/env
. "$TE_HOME"/test-data/common.sh

if [ -n "$REQUEST_DIRECTORY" ]; then
    export TMPDIR=$REQUEST_DIRECTORY
fi

EXITCODE=0

for CHECK in "$TE_HOME"/test-data/test_*; do
    "$CHECK"
    RET=$?
    if [ $RET -ne 0 ]; then
        EXITCODE=$RET
    fi
done

exit $EXITCODE