#!/usr/bin/env bash

# Get composer
EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

# Run composer
php composer-setup.php
rm composer-setup.php

# Remove vendor directory
rm -rf vendor

# Run composer install
php composer.phar install

# Remove composer
rm composer.phar

# Zip contents of folder to onpay folder in a zip file
rm onpay.zip
rsync -Rr ./* ./onpay
zip -r onpay.zip ./onpay

# Clean up
rm -rf onpay