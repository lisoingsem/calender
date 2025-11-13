# Contributing to `calendar`

Thank you for helping build the universal calendar library. This document explains the standards, tooling, and workflow that keep contributions consistent and easy to review.

## Code of Conduct

Participation in this project is governed by the [Contributor Covenant](CODE_OF_CONDUCT.md). By contributing, you agree to abide by these community expectations.

## Development Environment

1. Fork the repository and clone your fork locally.
2. Install dependencies:

   ```bash
   composer install
   ```

3. Run the static analysis and test suites to verify your environment:

   ```bash
   composer analyse
   composer test
   ```

## Branch Naming & Commits

- Use descriptive feature branches, e.g. `feat/add-th-holidays`.
- Follow [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/):
  - `feat(country): add khmer national holidays`
  - `fix(calendar): adjust khmer lunar conversion`
  - `docs: update readme roadmap`

## Coding Standards

- This project follows **PSR-12**. Run the formatter before pushing:

  ```bash
  composer lint
  ```

- PHPStan level 6 is enforced:

  ```bash
  composer analyse
  ```

- Tests must accompany all new features and bug fixes:

  ```bash
  composer test
  ```

## Adding Calendars

1. Create a class in `src/Calendars/` implementing `Lisoing\Calendar\Contracts\CalendarInterface`.
2. Register it in `CalendarServiceProvider::register` or via a service provider hook.
3. Document configuration or usage changes in the README.
4. Add unit/feature tests covering conversions and edge cases.

## Adding Country Holiday Providers

1. Add a provider under `src/Holidays/Countries/{ISO}/`.
2. Extend `Lisoing\Calendar\Holidays\AbstractHolidayProvider` for consistent translation handling.
3. Update `config/calendar.php` with the new mapping if auto-registration is desired.
4. Provide translations in `resources/lang/{locale}/holidays.php`.
5. Add feature tests under `tests/Feature/Holidays/`.

## Adding Translations

1. Copy `resources/lang/en/holidays.php` to the target locale (e.g. `resources/lang/km/holidays.php`).
2. Translate holiday names while keeping keys identical.
3. Run tests to ensure translation coverage remains intact.

## Documentation

- Update the README with any new features or configuration options.
- Expand `docs/` with guides, examples, and branding assets when needed.
- Keep `CHANGELOG.md` current by adding entries under the `Unreleased` section.

## Workflow Summary

1. Fork → clone → create branch.
2. Implement changes + update docs/tests.
3. Run `composer lint`, `composer analyse`, and `composer test`.
4. Commit using Conventional Commits.
5. Push and open a pull request, completing the PR template.
6. Engage in review discussions; update your branch as needed.
7. Celebrate when the PR merges 🎉

Thank you for making `calendar` more inclusive and powerful for developers worldwide!

