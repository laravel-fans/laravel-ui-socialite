#!/usr/bin/env bash
STARTED_AT=$(date +%s)

./vendor/bin/phpunit --stop-on-defect --coverage-text tests/
if [ $? -ne 0 ]; then
    exit 1
fi

FINISHED_AT=$(date +%s)
echo 'Time taken: '$(($FINISHED_AT - $STARTED_AT))' seconds'
