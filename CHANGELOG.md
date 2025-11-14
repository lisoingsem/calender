# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Additional lunar calendars (Chinese, Thai, Hijri)
- Expanded country holiday datasets
- Online documentation portal and API explorer

## [0.1.0] - 2025-01-XX

### Added
- Initial package release with Gregorian and Khmer lunisolar calendar support
- Cambodian holiday provider with English and Khmer translations
- Laravel service provider with auto-discovery
- Calendar and Holidays facades (Spatie-style API)
- Country helper classes (`Lisoing\Countries\Cambodia`)
- Calendar conversion utilities (`Calendar::toLunar()`, `Calendar::toSolar()`)
- Holiday lookup API (`Holidays::for()`, `Holidays::isHoliday()`, etc.)
- Translation system for calendar components and holidays
- PHPUnit, Laravel Pint, and PHPStan development tooling
- Comprehensive documentation: README, CONTRIBUTING, CODE_OF_CONDUCT, SECURITY, SUPPORT

