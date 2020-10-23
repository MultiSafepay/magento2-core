# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Added MultiSafepay refund_id to the refund transaction in the Magento Payment.
- Added missing gift card logos
- Added http-factory-guzzle package as a dependency for PSR-17 factories
- Added event for adding or changing the transaction request data before it is sent to MultiSafepay

### Fixed
- Added support for all the street lines (Before only street line 1 and 2 were supported)
- Added dependencies in module.xml and composer.json

### Removed
- Removed custom factories to be replaced by http-factory-guzzle factories

### Changed
- Rebrand Klarna to their latest standards
- Upgraded the PHP-SDK to version 4

## [1.0.0] - 2020-09-02
### Added
- First public release
