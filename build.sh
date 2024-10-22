#!/usr/bin/env bash

# Get composer
EXPECTED_SIGNATURE="566a6d1cf4be1cc3ac882d2a2a13817ffae54e60f5aa7c9137434810a5809ffc"
php -r "copy('https://getcomposer.org/download/2.5.5/composer.phar', 'composer.phar');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha256', 'composer.phar');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

# Remove vendor directory
rm -rf vendor
rm -rf build

# Run composer install
php composer.phar install

# Require and run php-scoper
php composer.phar global require humbug/php-scoper
php composer.phar global require prestashop/autoindex
COMPOSER_BIN_DIR="$(composer global config bin-dir --absolute)"
COMPOSER_DIR="$(composer global config --absolute)"
"$COMPOSER_BIN_DIR"/php-scoper add-prefix

# Dump composer autoload for build folder
php composer.phar dump-autoload --working-dir build --classmap-authoritative

# Search and replace to set the prefix of the Composer Namespace properly, 
# Since Composer skips this when dumping with --classmap-authoritative
sed -i -e "s|'Composer\\\\\\\\InstalledVersions'|'PrestashopOnpay\\\\\\\\Composer\\\\\\\\InstalledVersions'|g" build/vendor/composer/autoload_classmap.php build/vendor/composer/autoload_static.php 

# Remove composer
rm composer.phar

# Remove existing build zip file
rm onpay.zip

# Rsync contents of folder to new directory that we will use for the build
rsync -Rr ./* ./onpay
cp .htaccess ./onpay/.htaccess

# Remove directories and files from newly created directory, that we won't need in final build
rm -rf ./onpay/vendor
rm ./onpay/build.sh
rm ./onpay/composer.json
rm ./onpay/composer.lock
rm ./onpay/scoper.inc.php

# Replace require file with build version
rm ./onpay/require.php
mv ./onpay/require_build.php ./onpay/require.php

# Add out index template to the assets folder for autoindex
mv "$COMPOSER_BIN_DIR"/../prestashop/autoindex/assets/index.php "$COMPOSER_BIN_DIR"/../prestashop/autoindex/assets/index.php.bak
cp ./index.php "$COMPOSER_BIN_DIR"/../prestashop/autoindex/assets/index.php

# Add auto index files to build directory
"$COMPOSER_BIN_DIR"/autoindex prestashop:add:index ./onpay

# Reset autoindex template to default
rm "$COMPOSER_BIN_DIR"/../prestashop/autoindex/assets/index.php
mv "$COMPOSER_BIN_DIR"/../prestashop/autoindex/assets/index.php.bak "$COMPOSER_BIN_DIR"/../prestashop/autoindex/assets/index.php

# Zip contents of newly created directory
zip -r onpay.zip ./onpay

# Clean up
rm -rf onpay
rm -rf build