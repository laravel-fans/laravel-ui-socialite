#!/usr/bin/env bash
STARTED_AT=$(date +%s)

rm -f vendor/orchestra/testbench-core/laravel/migrations/2014_10_12_100000_create_password_resets_table.php
./vendor/bin/phpunit --stop-on-defect --coverage-text tests/
if [ $? -ne 0 ]; then
    exit 1
fi

FINISHED_AT=$(date +%s)
echo 'Time taken: '$(($FINISHED_AT - $STARTED_AT))' seconds'
