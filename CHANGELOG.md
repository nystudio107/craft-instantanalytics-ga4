# Instant Analytics GA4 Changelog

## 4.0.1 - 2024.01.11
### Changed
* Updated the buildchain to use Vite 5

### Fixed
* Fixed an error where it was impossible to add a Commerce Product list impression without providing a list name. ([#25](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/25))
* Fixed an error where it was impossible to send more than 25 GA4 events. ([#24](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/24))
* Fixed an issue where a purchasable can be `null` if the product variant has been deleted in the backend while still being in basket ([#28](https://github.com/nystudio107/craft-instantanalytics-ga4/pull/28))

## 4.0.0 - 2023.10.31
### Fixed
* Add currency to all Commerce GA4 events, but better. ([#20](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/20))

## 4.0.0-beta.5 - 2023.10.23
## Added
* Add currency to all Commerce GA4 events. ([#20](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/20))

## 4.0.0-beta.4 - 2023.07.21
### Fixed
* Fixed an error where Google client id and GA session id would get mixed up.

## 4.0.0-beta.3 - 2023.07.04
## Added
* Added the `sessionDuration` setting that can be changed via config.php.
* Added the `instantanalytics.beginCheckout(cart)` action.
* Added the `sendUserId` setting that can be changed via config.php
* Added the `iaInsertGtag` template hook.

### Changed
* `addCommerceProductImpression()` no longer supports list index and list name. ([#6](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/6))
* Instant Analytics GA4 now supports sending user id for logged in users automatically.

### Fixed
* Fixed an issue where other plugin status was checked too early.
# Correctly parse and send the GA session data.

## 4.0.0-beta.2 - 2023.06.25
### Added
* It is now possible to select Entry fields for Commerce Product Category and Brand fields. ([#2](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/2))
* Added `addCommerceProductDetailView()` as a deprecated method. ([#6](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/6))

### Fixed
* No longer automatic send page views for requests that are AJAX (XHR's) ([#3](https://github.com/nystudio107/craft-instantanalytics-ga4/issues/3)) 
* Fixed a bug where sending a product view impression for a product might trigger an error.
* Fixed a PHP error when sending a product impression event for a Variant.

### Changed
* ClientId is now always generated, if missing and the setting `requireGaCookieClientId` has been removed.

## 4.0.0-beta.1 - 2023.06.13
### Added
* Initial beta release
