#!/bin/bash

if [ -z "$TE_HOME" ]; then
    echo "TE_HOME not defined!"
    exit 1
fi

. "$TE_HOME"/etc/te.conf
. "$TE_HOME"/etc/te.d/env
. "$TE_HOME"/test-data/common.sh

TEST_WORK_DIR=$(mktemp -d "$TE_WORK_DIR/te-task-test-XXXXXX")
if [ $? -ne 0 ]; then
    echo "Error creating test work dir!"
    exit 1
fi
export TMPDIR=$TEST_WORK_DIR

EXITCODE=0

for CHECK in "$TE_HOME"/test-data/test_*; do
    "$CHECK"
    RET=$?
    if [ $RET -ne 0 ]; then
        EXITCODE=$RET
    fi
done

rm -Rf "$TEST_WORK_DIR"

exit $EXITCODE
