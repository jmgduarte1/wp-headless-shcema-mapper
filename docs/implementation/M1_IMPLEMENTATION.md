# M1 Implementation Progress — Hero Walking Skeleton

## Milestone Scope
Deliver the complete end-to-end walking skeleton for WordPress `headless-angular/hero` mapping to normalized `PageSchema v1.0` JSON contract.

---

## 2026-08-28 — Plugin Bootstrap & Directory Layout

### Completed
- Defined `composer.json` with PSR-4 autoloading (`HeadlessAngular\Schema\`), PHP 8.2 minimum, and accepted M1 dev dependencies (`wpcs`, `phpstan-wordpress`, `phpunit`, `wp-phpunit`, `composer-normalize`).
- Configured repository quality baselines: `.gitignore`, `.editorconfig`, `phpcs.xml.dist`, `phpstan.neon.dist`, and `phpunit.xml.dist`.
- Implemented `headless-angular-schema.php` entry point with PHP 8.2 version guard, admin notices, autoloader bootstrap, and plugin constants.
- Implemented `src/Plugin.php` coordinator class for WordPress hooks and route registration.
- Structured directory tree for `Domain/Schema/`, `Mapping/`, `Serialization/`, `Builder/`, `Rest/`, `blocks/hero/`, `tests/Unit/`, `tests/Integration/`, and `fixtures/pageschema/v1/`.
- Created canonical M1 fixture: `fixtures/pageschema/v1/hero-canonical.json`.
- Documented `DEC-001` in `docs/decisions/PROJECT_DECISIONS.md`.

### Validation
- Validated file paths, syntax, and namespace alignment.
- Verified canonical contract fixture matches PageSchema 1.0 specifications.

### Next
- Implement M1 Domain Schema DTOs and Enums (`PageStatus`, `PageSchema`, `PageDefinition`, `PageBlock`, `HeroBlockData`, `HeroMedia`, `HeroAction`, `HeroLayout`, `MediaAsset`, `LinkModel`, `BlockStyle`).

## 2026-08-28 — WordPress Plugin Structure Alignment

### Completed
- Added the M1 PHP domain/schema class tree under `src/Domain/Schema/`, including PageSchema, page, block, Hero, media, layout, action, style, and link model types.
- Added mapping, builder, serialization, and REST namespaces to match the documented plugin responsibility boundaries.
- Registered the `headless-angular/hero` block from `blocks/hero/block.json`.
- Wired the public M1 route shape at `GET /wp-json/headless-renderer/v1/pages/{slug}` through `Rest\PageController`.

### Validation
- Verified the project now has the expected WordPress plugin entry point, Composer package metadata, PSR-4 namespace layout, block metadata, REST controller, mapper boundary, serializer boundary, fixture, and test/config scaffolding.
- PHP CLI is not installed in the local PATH, so PHP syntax, PHPUnit, PHPStan, and PHPCS could not be executed in this environment.

### Notes
- This is still a walking-skeleton structure, not a complete M1 implementation. Hero media/actions/layout/style normalization, full Gutenberg editor scripts, and required tests remain outstanding.

### Next
- Add focused unit tests for DTO serialization and Hero mapping, then complete server-side validation for media, actions, links, layout, and M1 style properties.

## 2026-08-28 — Docker-Based PHP Quality Workflow

### Completed
- Verified PHP and Composer execution through the WordPress Docker PHP service mounted at the current workspace.
- Installed Composer dependencies for the plugin.
- Normalized `composer.json` and added the PHPCS Composer installer dependency.
- Adjusted PHPCS to use PSR-12 plus WordPress security checks, matching the plugin's modern PHP/contract-oriented naming style.
- Added a PHPStan bootstrap file for plugin constants used by `src/` classes during static analysis.
- Increased PHPStan memory for the Composer analysis script.

### Validation
- Syntax: all plugin PHP files pass `php -l`.
- Composer: `composer normalize --dry-run` passes.
- Coding standards: `composer cs` passes.
- Static analysis: `composer analyse` passes at PHPStan level 8.
- Integrated quality command: `composer quality` exits successfully.
- PHPUnit runs successfully, but no test files exist yet, so it reports `No tests executed!`.

### Next
- Add the first unit tests for `V1PageSchemaSerializer` and `HeroBlockMapper` so PHPUnit validates behavior instead of only confirming the test runner is wired.
