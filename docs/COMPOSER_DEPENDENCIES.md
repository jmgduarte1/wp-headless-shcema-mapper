# Composer Dependencies
## headless-angular-schema

**Status:** Accepted for M1  
**Scope:** WordPress / PHP repository  
**Minimum PHP:** 8.2  
**Current WordPress baseline:** 7.1

---

# 1. Dependency Policy

Composer is the standard dependency and autoloading mechanism for this repository.

The plugin should remain lightweight and portable. Runtime dependencies must therefore be added only when they solve a concrete problem better than WordPress/PHP capabilities already available.

M1 deliberately starts with:

```text
Runtime dependencies:
- none beyond PHP/WordPress

Development dependencies:
- WordPress Coding Standards
- PHPStan WordPress extension
- PHPUnit
- WordPress PHPUnit test library
- composer-normalize
```

Full application frameworks such as Laravel or Symfony Framework are not part of the plugin architecture.

Individual Composer packages or Symfony components may be introduced later only through an explicit dependency decision.

---

# 2. Accepted M1 Development Dependencies

## 2.1 wp-coding-standards/wpcs

Purpose:

```text
WordPress coding standards
PHP_CodeSniffer rules
WordPress-specific code quality checks
```

Recommended constraint:

```json
"wp-coding-standards/wpcs": "^3.4"
```

Use for:

```bash
vendor/bin/phpcs
vendor/bin/phpcbf
```

The project should define a repository-level `phpcs.xml.dist` rather than relying on command-line defaults.

---

## 2.2 szepeviktor/phpstan-wordpress

Purpose:

```text
static analysis
WordPress function/class awareness
WordPress stubs
WordPress-specific PHPStan extensions
```

Recommended constraint:

```json
"szepeviktor/phpstan-wordpress": "^2.0"
```

This package already depends on:

```text
phpstan/phpstan
php-stubs/wordpress-stubs
```

Do not add those packages as direct dependencies unless a future requirement needs independent version control.

Use:

```bash
vendor/bin/phpstan analyze
```

The project should define `phpstan.neon.dist`.

Start with a practical strictness level and increase it deliberately as the codebase stabilizes.

---

## 2.3 phpunit/phpunit

Purpose:

```text
unit tests
contract tests
serializer tests
mapper tests
integration test runner
```

The plugin minimum is PHP 8.2.

PHPUnit 13 requires PHP 8.4+, and PHPUnit 12 requires PHP 8.3+. Therefore the M1 toolchain uses PHPUnit 11, which supports PHP 8.2.

Recommended constraint:

```json
"phpunit/phpunit": "^11.5"
```

Use:

```bash
vendor/bin/phpunit
```

This choice exists to ensure the test suite can run on the minimum supported PHP version.

Re-evaluate the PHPUnit major when the plugin minimum PHP version changes.

---

## 2.4 wp-phpunit/wp-phpunit

Purpose:

```text
WordPress core PHPUnit library
WordPress-aware integration tests
REST API tests
plugin lifecycle tests
```

Current WordPress baseline:

```text
WordPress 7.1
```

Recommended initial constraint:

```json
"wp-phpunit/wp-phpunit": "^7.1"
```

The version should track the WordPress version under test.

When the compatibility matrix expands, CI should test additional supported WordPress versions with the corresponding `wp-phpunit` versions rather than treating one package version as universal.

---

## 2.5 ergebnis/composer-normalize

Purpose:

```text
consistent composer.json formatting
deterministic dependency metadata
CI validation of composer.json normalization
```

Recommended constraint:

```json
"ergebnis/composer-normalize": "^2.52"
```

Use:

```bash
composer normalize
composer normalize --dry-run
```

This is a Composer plugin and must be explicitly allowed in Composer configuration.

It is development-only and has no effect on the deployed plugin runtime.

---

# 3. Proposed composer.json Baseline

```json
{
  "name": "headless-angular/headless-angular-schema",
  "description": "WordPress plugin that maps Gutenberg content to a normalized PageSchema contract.",
  "type": "wordpress-plugin",
  "require": {
    "php": ">=8.2"
  },
  "require-dev": {
    "ergebnis/composer-normalize": "^2.52",
    "phpunit/phpunit": "^11.5",
    "szepeviktor/phpstan-wordpress": "^2.0",
    "wp-coding-standards/wpcs": "^3.4",
    "wp-phpunit/wp-phpunit": "^7.1"
  },
  "autoload": {
    "psr-4": {
      "HeadlessAngular\\Schema\\": "src/"
    }
  },
  "config": {
    "allow-plugins": {
      "dealerdirect/phpcodesniffer-composer-installer": true,
      "ergebnis/composer-normalize": true
    },
    "sort-packages": true
  },
  "scripts": {
    "test": "phpunit",
    "analyse": "phpstan analyse",
    "cs": "phpcs",
    "cs:fix": "phpcbf",
    "composer:check": "composer normalize --dry-run",
    "quality": [
      "@composer:check",
      "@cs",
      "@analyse",
      "@test"
    ]
  }
}
```

The exact Composer package name/namespace remains subject to repository naming decisions.

Do not copy a version constraint blindly if Composer reports a real compatibility conflict during bootstrap. Resolve the conflict deliberately and document the final selected version.

---

# 4. Why There Are No M1 Runtime Packages

M1 needs:

```text
DTOs
mapper registry
Hero mapper
serializer
PageSchema builder
REST controller
validation/sanitization
```

PHP 8.2 and WordPress already provide sufficient primitives for these responsibilities.

Adding libraries such as:

```text
symfony/validator
symfony/serializer
symfony/cache
ramsey/uuid
webmozart/assert
```

would currently increase:

```text
dependency surface
plugin package size
version conflict risk
maintenance work
WordPress portability concerns
```

without eliminating enough complexity to justify them.

---

# 5. Packages Evaluated but Deferred

## Symfony Validator

Potential future use:

```text
larger validation rule sets
reusable constraint objects
complex nested validation
```

Decision for M1:

```text
DEFER
```

Hero validation is small enough to remain explicit and domain-focused.

---

## Symfony Serializer

Potential future use:

```text
generic DTO normalization
multiple transport formats
large model graphs
```

Decision for M1:

```text
DEFER
```

PageSchema serialization is an explicit versioned contract. A dedicated `V1PageSchemaSerializer` provides greater visibility and control over exactly what leaves WordPress.

---

## Brain Monkey

Potential use:

```text
mocking WordPress functions/hooks in isolated unit tests
```

Decision for M1:

```text
DEFER
```

The initial domain code can be tested as plain PHP, while WordPress-dependent behavior can use `wp-phpunit`.

Add Brain Monkey later only if isolated WordPress API mocking clearly improves the test suite.

---

## PHPCompatibilityWP

Potential use:

```text
cross-version PHP compatibility analysis in WordPress projects
```

Decision for M1:

```text
DEFER
```

The project has a deliberate PHP 8.2 minimum and should run CI on PHP 8.2. This directly detects accidental use of syntax/runtime features above the supported minimum.

Re-evaluate PHPCompatibilityWP when the release compatibility matrix becomes broader or package distribution requirements justify the additional ruleset.

---

# 6. Composer Dependency Rules for Agents

Before adding any runtime dependency:

1. Identify the concrete problem being solved.
2. Check whether PHP 8.2 or WordPress already provides a suitable capability.
3. Evaluate PHP 8.2 compatibility.
4. Evaluate WordPress compatibility and plugin portability.
5. Evaluate maintenance activity and security posture.
6. Prefer a small focused package over a framework.
7. Keep WordPress-facing dependencies isolated behind project abstractions where practical.
8. Record material dependency decisions in `docs/decisions/PROJECT_DECISIONS.md`.
9. Update this document when an accepted dependency is added or removed.
10. Never add dependencies merely to reduce a small amount of straightforward project code.

Development dependencies may be added more freely, but still require a clear quality/testing/tooling purpose.

---

# 7. CI Expectations

M1 CI should eventually execute at least:

```bash
composer validate --strict
composer normalize --dry-run
vendor/bin/phpcs
vendor/bin/phpstan analyze
vendor/bin/phpunit
```

At least one CI job must execute the applicable suite on:

```text
PHP 8.2
```

because PHP 8.2 is the plugin minimum.

WordPress integration testing should start against:

```text
WordPress 7.1
```

and later expand according to the project's supported WordPress compatibility policy.

---

# 8. Dependency Review Rule

Composer dependencies are not permanent architectural entitlements.

At milestone boundaries, review:

```text
Is the package still used?
Does PHP/WordPress now provide the capability directly?
Is maintenance healthy?
Does it still support our PHP/WordPress matrix?
Can a runtime dependency become development-only?
```

Remove dependencies that no longer provide enough value.
