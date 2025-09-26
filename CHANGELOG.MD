# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.4.1

### Fixed

- Issue creating multiple returns for single transaction
- Preventing non-digits from being entered into BLIK code input

## 2.4.0

### Added

- Feature: new block added to success page, for BLIK payments will show message "confirm in bank payment app" and allow to retry payment if BLIK payment fails

### Changed

- updated payment icons

## 2.3.2

### Added

- Feature: based on number of days in setting scheduled tasks cancel orders with overdue payment

## 2.3.1

### Fixed

- Resolves DI compilation issue on Magento 2.4.8+

## 2.3.0

### Changed

- Streamlined IPN processing
- Moved to Tpay OpenAPI SDK 2.0

## 2.2.2

### Fixed

- Streamlined payment processing when there are no paywall redirects

## 2.2.1

### Fixed

- Cache in multistore settings could store data from improper context

## 2.2.0

### Added

- Proper use of CSP nonceProvider in checkout for PCI DSS 4.0 compliance

### Fixed

- Origin Card API not respecting Sandbox setting
- Origin API not logging
- Improved caching of Open API token and group/channels list
- Changed API selection logic to prioritize Open API

## 2.1.7

### Added

- Module version bump to be adequate to github tag

## 2.1.6

### Added

- Blik one click payment variant

## 2.1.5

### Fixed

- Remove unnecessary tos input

## [2.1.4]

### Fixed

- Language improvements

## [2.1.3]


### Added

- Adding minimum amount validation

### Fixed

- Performance improvements

## [2.1.1]

### Fixed

- CSP configuration
- Partial refund

## [2.1.0]

### Added

- Adding version information in the Tpay configuration view

## [2.0.9]

### Fixed

- Fixed refund scope

## [2.0.8]

### Fixed

- Fixed admin scope config

## [2.0.7]

### Added

- Added regulations in english

### Fixed

- Fixed card notification

## [2.0.6]

### Fixed

- Fixed currency validation

## [2.0.5]

### Added

- Readme file

---

### Fixed

- Fixed channels and groups collision

## [2.0.4]

### Fixed

- Casting in additional data
- Regulations checkbox

## [2.0.3]

### Fixed

- Fixed logging

## [2.0.2]

### Fixed

- Performance improvements

## [2.0.1]

### Added

- Changes required for tpay-com/tpay-magento2-graphql

## [2.0.0]

### Added

- New version introducing (details below)
- Card payment in PLN and other currencies
- Tpay OpenApi (PLN) and Tpay OriginApi (PLN and card sales in other currencies) support
- Generic payment methods which can be displayed in checkout
- Code refactored to PSR-12 standards
- Bumped versions of Tpay libraries
- Sandbox Support
- Add changelog
