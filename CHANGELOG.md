# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2020-01-22
- Fixed empty transaction_id value on payments resulting in errors, when trying to get transaction data from API. (PR #2)
- Fixed API calls made regardless of payment method used for order. (PR #2)
- Fixed multiple payments through OnPay on a single order not being properly supported. (PR #2)


## [1.0.0] - 2019-09-19
Initial release