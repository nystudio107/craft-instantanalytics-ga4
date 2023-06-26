# Instant Analytics GA4 Changelog

## 4.0.0-beta.3 - UNRELEASED
## Changed
* `addCommerceProductImpression()` no longer supports list index and list name.

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
