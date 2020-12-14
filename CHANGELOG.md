# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Changed
- Added retrieval of icons from local directory for Givacard, Wellness gift card and Winkel Cheque

## [2.1.0] - 2020-12-10
### Added
- Added in3 payment method
- Added support for preselected default payment method in the checkout

### Fixed
- IP Address is now filtered, preventing error when retrieving 2 ip addresses from the customer.
- Fix error 'Type "" is not a known type.' when placing a backend order with a non-MultiSafepay payment method.

## [2.0.1] - 2020-11-27
### Fixed
- Fixed instantiation error of fileDriver interface in backend and after placing a transaction
- Disabled Billing Suite payment methods for admin backend orders

## [2.0.0] - 2020-11-11
### Added
- Added support for order currency
- Added Good4Fun gift card
- Added support for custom transaction description and custom refund description
- Added MultiSafepay refund_id to the refund transaction in the Magento Payment.
- Added http-factory-guzzle package as a dependency for PSR-17 factories
- Added an event for adding or changing the transaction request data before it is sent to MultiSafepay
- Added support for Magento 2.4.1 and PHP 7.4
- Added delivery details to every transaction request
- Added user-agent to every transaction request

### Fixed
- Trying to refund with 0 amount will now throw an error
- Disable Second Chance for admin backend orders
- Added support for all the street lines (Before only street line 1 and 2 were supported)
- Added dependencies in module.xml and composer.json

### Removed
- Removed the use of pending_payment order status. For new orders, the 'new' order state will be used.
- Removed version constant. Versions are now retrieved from the composer.json file
- Removed custom factories to be replaced by http-factory-guzzle factories

### Changed
- Rebrand Klarna to the latest standards
- Upgraded the PHP-SDK to version 4

## [1.0.0] - 2020-09-02
### Added
- First public release
