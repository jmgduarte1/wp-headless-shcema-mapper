# M1 Implementation Progress — Native Blocks Walking Skeleton

## Current M1 Baseline

M1 is now defined around editable native Gutenberg blocks, not a proprietary Hero block. The current vertical slice covers page metadata/title, structural containers, nested groups and columns, headings, paragraphs, images, links/buttons, spacers, details/summary disclosures, controlled styles, PageSchema validation, Angular registry rendering, and SSR/hydration boundaries. Composed blocks such as Hero remain deferred.

## Milestone Scope
Deliver the complete end-to-end walking skeleton for WordPress-authored Hero content mapping to normalized `PageSchema v1.0` JSON contract.

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

## 2026-08-28 — M1 Hero Mapping and Contract Tests

### Completed
- Expanded the `headless-angular/hero` block metadata with M1 attributes for media, actions, layout, and style.
- Added editor, editor style, and frontend style assets for the Hero block.
- Implemented Hero mapper normalization for title, eyebrow, subtitle, media placement, accessible media metadata, actions, internal/external/anchor links, layout enums, and the M1 style whitelist.
- Preserved typed DTOs through mapping and serialized only at the `V1PageSchemaSerializer` boundary.
- Added unit tests for Hero mapping, invalid Hero configurations, unsafe links, unsafe styles, optional omission, and canonical fixture serialization.

### Validation
- `composer quality` passes.
- PHPUnit: 7 tests, 19 assertions.
- PHPStan: level 8 passes.
- PHPCS: PSR-12 plus WordPress security checks pass.

### Notes
- REST endpoint registration is wired and covered by static analysis, but full WordPress integration tests against a live `WP_REST_Server` are still pending.

### Next
- Add WordPress integration tests for plugin activation, REST route output, unknown slug 404, and draft boundary 404.

## M2 Implementation Status

M2 extends the plugin with the native block composition contract:

- Native groups, columns, text, images, buttons, spacers, and disclosure blocks are normalized recursively.
- Text blocks expose sanitized inline HTML in `BasicBlockData::html` while retaining plain text.
- Core buttons are normalized as safe link blocks with button layout metadata.
- Child order, column counts, widths, alignment, and supported WordPress styles are preserved.
- Mapper coverage verifies rich text and button normalization.

The remaining M2 follow-up is live WordPress integration coverage.

## M3 and M4 Implementation Status

- M3 style normalization now covers WordPress spacing, typography, colors/gradients, dimensions, borders, alignment, and controlled responsive values.
- M4 exposes normalized media metadata (`srcSet`, loading mode, MIME type, and caption) and a public nested navigation endpoint.
- M4 supports internal, external, anchor, email, and telephone menu links.
- The plugin quality suite passes with PHP 8.2.33.

## 2026-08-28 — WordPress Editor Compatibility Adjustment

### Completed
- Added the missing Gutenberg asset metadata for `blocks/hero/index.js` so WordPress can enqueue the custom Hero editor script with its required `wp-*` dependencies.
- Added support for mapping native WordPress `core/cover` compositions into the M1 `hero` schema:
  - `core/cover` becomes `type: "hero"`.
  - The first nested `core/heading` becomes the Hero title.
  - The first nested `core/paragraph` becomes the Hero subtitle.
  - Nested `core/button` blocks become Hero actions.
  - Cover image URL becomes background Hero media.
  - Cover content position and min-height are normalized into Hero layout/style fields.
- Updated the PageSchema builder to tolerate normal WordPress pages:
  - Unsupported blocks are skipped instead of causing the whole endpoint to fail.
  - Container blocks are traversed recursively so supported blocks can be found inside groups, columns, and other wrappers.

### Validation
- `composer quality` passes.
- PHPUnit: 8 tests, 33 assertions.
- PHPStan: level 8 passes.
- PHPCS: PSR-12 plus WordPress security checks pass.
- The local `home` endpoint returns PageSchema again even when the page also contains unsupported WordPress pattern blocks.

### Notes
- M1 no longer requires content authors to use only a proprietary Hero block. A Hero can be authored with native WordPress blocks using a Cover block containing Heading, Paragraph, and Buttons.
- The custom `headless-angular/hero` block remains available as an explicit contract-first option, but the more natural WordPress editing path is now supported.

## 2026-08-29 — Native Pattern Hero Mapping

### Completed
- Reworked the native WordPress Hero mapper around the real `Test Hero` page pattern at `/?page_id=12`.
- Added support for a Hero authored as a native WordPress composition:
  - outer `core/group` or `core/columns` container;
  - image column using `core/cover`;
  - content column using `core/heading` and one or more `core/paragraph` blocks;
  - optional nested `core/buttons` / `core/button` CTA blocks.
- The mapper now reads text from `innerHTML` when Gutenberg stores content there instead of in attributes.
- The normalized block now emits `element: "section"` so Angular can later choose the correct semantic wrapper.
- The endpoint preserves useful style intent from the WordPress pattern, including margin, content padding, media width, media aspect ratio, overlay color, and overlay opacity.

### Validation
- `composer quality` passes.
- PHPUnit: 9 tests, 49 assertions.
- PHPStan: level 8 passes.
- PHPCS: PSR-12 plus WordPress security checks pass.
- The local endpoint for `test-hero` returns a `hero` schema with title `The Stories Book`, image `book-image-landing.webp`, variant `media-split`, and `element: "section"`.
