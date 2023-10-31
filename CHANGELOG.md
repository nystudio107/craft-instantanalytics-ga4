# Instant Analytics GA4 Changelog

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
