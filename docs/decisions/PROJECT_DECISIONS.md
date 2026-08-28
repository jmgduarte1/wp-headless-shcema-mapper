# Project Decisions

## DEC-001 — Plugin Bootstrap, Directory Layout & Namespace Structure

**Date:** 2026-08-28  
**Status:** Accepted

### Context
The `headless-angular-schema` project requires a standardized WordPress plugin bootstrap, directory structure, PSR-4 namespace configuration, and development tooling that supports modern PHP 8.2+ practices while conforming to WordPress conventions.

### Decision
1. **Namespace & Autoloading:** Adopt `HeadlessAngular\Schema\` mapped to `src/` and `HeadlessAngular\Schema\Tests\` mapped to `tests/` via Composer PSR-4. Include a lightweight fallback autoloader in `headless-angular-schema.php` if vendor autoloading is not yet present in runtime environments.
2. **Directory Architecture:** Establish clear layer separation:
   - `src/Domain/Schema/` for strongly typed PHP DTOs and enums.
   - `src/Mapping/` for Gutenberg block mappers and the mapper registry.
   - `src/Serialization/` for PageSchema serializers.
   - `src/Builder/` for PageSchema assembly and construction.
   - `src/Rest/` for WordPress REST API controllers.
   - `blocks/` for Gutenberg block registration definitions (`block.json`).
   - `fixtures/` for PageSchema contract test fixtures.
   - `tests/Unit/` and `tests/Integration/` for PHPUnit testing suites.
3. **Tooling & Quality:** Enforce PHP 8.2 minimum, PHPUnit 11.5, szepeviktor/phpstan-wordpress level 8, and WordPress-Core/Security coding standards.

### Consequences
- Clean separation between WordPress data extraction, normalization, domain models, and external transport serialization.
- Gutenberg internals remain fully encapsulated within the plugin.

