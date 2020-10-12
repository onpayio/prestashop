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
rm -rf build

# Run composer install
php composer.phar install

# Require and run php-scoper
php composer.phar global require humbug/php-scoper
COMPOSER_BIN_DIR="$(composer global config bin-dir --absolute)"
"$COMPOSER_BIN_DIR"/php-scoper add-prefix

# Dump composer autoload for build folder
php composer.phar dump-autoload --working-dir build --classmap-authoritative

# Remove composer
rm composer.phar

# Remove existing build zip file
rm onpay.zip

# Rsync contents of folder to new directory that we will use for the build
rsync -Rr ./* ./onpay

# Remove directories and files from newly created directory, that we won't need in final build
rm -rf ./onpay/vendor
rm ./onpay/build.sh
rm ./onpay/composer.json
rm ./onpay/composer.lock

# Replace require file with build version
rm ./onpay/require.php
mv ./onpay/require_build.php ./onpay/require.php

# Zip contents of newly created directory
zip -r onpay.zip ./onpay

# Clean up
rm -rf onpay
rm -rf build