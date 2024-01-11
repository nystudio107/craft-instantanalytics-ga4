# Instant Analytics GA4 Changelog

## 3.0.1 - UNRELEASED
### Changed
* Updated the buildchain to use Vite 5

### Fixed
* Fixed an error where it was impossible to add a Commerce Product list impression without providing a list name. ([#25](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/25))
* Fixed an error where it was impossible to send more than 25 GA4 events. ([#24](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/24))
* Fixed an issue where a purchasable can be `null` if the product variant has been deleted in the backend while still being in basket ([#28](https://github.com/nystudio107/craft-instantanalytics-ga4/pull/28))

## 3.0.0 - 2023.10.31
### Added
* Add currency to all Commerce GA4 events, but better. ([#20](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/20))

## 3.0.0-beta.4 - 2023.10.23
### Added
* Add currency to all Commerce GA4 events. ([#20](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/20))

### Fixed
* Fixed a bug where it was impossible to set API Secret programmatically. ([#16](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/16))

## 3.0.0-beta.3 - 2023.07.21
### Fixed
* Fixed an error where Google client id and GA session id would get mixed up.

## 3.0.0-beta.2 - 2023.07.04
## Added
* Added the `sessionDuration` setting that can be changed via config.php.
* Added the `instantanalytics.beginCheckout(cart)` action.
* Added the `sendUserId` setting that can be changed via config.php
* Added the `iaInsertGtag` template hook.

## Changed
* `addCommerceProductImpression()` no longer supports list index and list name. ([#6](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/6))
* Instant Analytics GA4 now supports sending user id for logged in users automatically.

## Fixed
* Fixed Craft 3.6.x compatibility issue. ([#11](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/11))
* Fixed an issue where other plugin status was checked too early.
* Correctly parse and send the GA session data.

## 3.0.0-beta.1 - 2023.06.25
### Added
* Initial beta release
