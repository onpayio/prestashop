# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.5] - 2020-10-21
- Locked Composer version used to 1.x to ensure PHP 5.6 compatibility
- - Tested compability down to Prestashop 1.7.0.1

## [1.0.5] - 2020-10-21
- Implemented usage of PHP-scoper to make unique namespaces for all composer dependencies, which solves any issues with overlap of dependencies from other modules and prestashop core.
- Updated dependencies, PHP SDK and onpayio oauth2 dependency
- Implemented paymentinfo for paymentwindow, setting available values

## [1.0.4] - 2020-06-18
- Fix compatibility issue with composer dependencies when using php 5.6
- Update composer dependencies to latest versions

## [1.0.3] - 2020-05-06
- Fixed when trying to translate strings, some strings ended up weird places in regards to translation domain. (PR #7)
- Fixed some legacy hook templates never used in this module, presented translation options never seen. (PR #7)
- Added function that allows to automatically determine onpay payment window language by frontoffice language currently selected by user. (PR #7)

## [1.0.2] - 2020-03-04
- Updated version of OnPay php SDK to the latest version. 1.0.5

## [1.0.1] - 2020-01-22
- Fixed empty transaction_id value on payments resulting in errors, when trying to get transaction data from API. (PR #2)
- Fixed API calls made regardless of payment method used for order. (PR #2)
- Fixed multiple payments through OnPay on a single order not being properly supported. (PR #2)

## [1.0.0] - 2019-09-19
Initial release