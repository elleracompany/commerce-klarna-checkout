# Release Notes for Klarna Checkout for Craft Commerce

## 1.1.13 - 2020-06-10

### Updated
- Fixed deprecated functions (PR #40 by @matternow)

## 1.1.12 - 2020-06-10

### Bugfix
- Fixed a typecast error on capture amount (Issue #38 / ESU-15)

## 1.1.11 - 2020-05-28

### Bugfix
- Added `allow_separate_shipping_address` in klarna order (Issue #33)

## 1.1.10 - 2020-05-22

### Bugfix
- Fixed division by zero shipping with discount issue (Issue #35)

## 1.1.9 - 2020-05-04

### Bugfix
- Fixed type cast error (Issue #31)

## 1.1.8 - 2020-03-25

### Bugfix
- Now using paymentCurrency instead of order currency in Klarna order

## 1.1.7 - 2020-03-03

### Updated
- Made compatible with Commerce ^3.0.0


## 1.1.6 - 2020-01-31

### Fixed
- Added TAX to the capture request so settlements will have the correct values.

## 1.1.5 - 2019-10-07

### Fixed
- License and composer type fixed

## 1.1.4 - 2019-10-04

### Fixed
- Rounding error when using discount codes (PR #22 from  Rizzet)

## 1.1.3 - 2019-09-16

### Updated
- Store Location in error message (#14)
- Fixed github-link in readme (#17)
- Updated Plugin icon with new Klarna-logo (#20)


## 1.1.2 - 2019-05-03

### Updated
- README.md
### Enhancements
- Added extended logging on exceptions.
### Added
- Function to render Klarna Order Complete HTML.

## 1.1.1 - 2019-05-02

### Updated
- Added support for Empty address on order creation.

## 1.1.0 - 2019-03-28

### Updated
- Added support for Commerce Lite

## 1.0.7 - 2019-03-27

### Enhancements
- Added more information to error messages when Klarna is not accepting the order
- Added storeUrl function to accurately send correct urls to Klarna
- Fixed bug where orders without shipping would fail

## 1.0.6 - 2019-03-13

### Bugfix
- The plugin should now correctly calculate tax for both lines and shipping (Included or not) when configured correctly. All tax on products must be set with "Line item price" and tax for shipping must be separate and set to "Order total shipping cost"

## 1.0.5 - 2019-02-28

### Bugfix
- Added taxable shipping

## 1.0.4 - 2019-02-28

### Updated
- Fixed typos

## 1.0.3 - 2019-02-28

### Added
- Added support for Shipping cost

## 1.0.2 - 2019-02-27

### Updated
- README.md

## 1.0.1 - 2019-02-27

### Updated
- README.md

## 1.0.0 - 2019-02-27

### Added
- Initial Plugin Release
