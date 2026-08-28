# headless-angular-schema
## WordPress / PHP Project Requirements

**Classification:** Private / Internal  
**Project Type:** WordPress Plugin  
**Plugin Name:** `headless-angular-schema`  
**Minimum PHP:** 8.2  
**Shared Contract:** `PageSchema v1`  
**Initial Milestone:** M1 — Hero Walking Skeleton

---

# 1. Project Responsibility

`headless-angular-schema` is responsible for transforming WordPress/Gutenberg content into normalized contracts consumable by external applications.

The plugin owns:

```text
Gutenberg integration
content extraction
block mapping
normalization
schema construction
serialization
REST endpoints
WordPress-side sanitization
published/private content boundaries
WordPress-side extension hooks
```

The plugin does NOT own:

```text
Angular rendering
Angular Material
Reactive Forms execution
Angular routing
SSR/hydration implementation
browser behavior
consumer application state
```

---

# 2. Mandatory Architectural Rule

The plugin must never expose Gutenberg internals as the consumer contract.

Bad:

```json
{
  "blockName": "core/group",
  "attrs": {}
}
```

Required:

```json
{
  "type": "section",
  "data": {}
}
```

For M1:

```text
headless-angular/hero
      ↓
HeroBlockMapper
      ↓
type = "hero"
```

---

# 3. Technology Requirements

```text
PHP >= 8.2
WordPress plugin architecture
strict_types where appropriate
PSR-4-compatible namespaces
typed properties
readonly DTOs
PHP enums where semantically appropriate
WordPress REST API
Gutenberg Block API
```

Allowed modern PHP features include:

```text
enum
readonly class
constructor property promotion
union types
nullable types
match
```

---

# 4. Initial Project Structure

Recommended:

```text
headless-angular-schema/
├── headless-angular-schema.php
├── composer.json
├── src/
│   ├── Domain/
│   │   └── Schema/
│   │       ├── PageStatus.php
│   │       ├── PageSchema.php
│   │       ├── PageDefinition.php
│   │       ├── PageBlock.php
│   │       ├── HeroBlockData.php
│   │       ├── HeroMedia.php
│   │       ├── HeroAction.php
│   │       ├── HeroLayout.php
│   │       ├── MediaAsset.php
│   │       └── Link/
│   ├── Mapping/
│   │   ├── BlockMapper.php
│   │   ├── BlockMapperRegistry.php
│   │   └── HeroBlockMapper.php
│   ├── Serialization/
│   │   ├── PageSchemaSerializer.php
│   │   └── V1PageSchemaSerializer.php
│   ├── Builder/
│   │   └── PageSchemaBuilder.php
│   └── Rest/
│       └── PageController.php
├── blocks/
│   └── hero/
│       └── block.json
├── tests/
└── fixtures/
    └── pageschema/v1/
```

Exact names may evolve, but responsibility boundaries should remain.

---

# 5. Shared Contract Dependency

This repository should contain a copy/reference of the shared contract documentation:

```text
docs/
├── SHARED_CONTRACT_REQUIREMENTS.md
├── PAGE_SCHEMA_SPECIFICATION.md
└── PAGE_SCHEMA_M1_CONTRACT_DEFINITIONS.md
```

These documents define what PHP must produce.

---

# 6. PHP Domain DTO Requirements

M1 requires typed domain models for:

```text
PageStatus
PageSchema
PageDefinition
PageBlock
HeroBlockData
HeroMedia
HeroAction
HeroLayout
MediaAsset
LinkModel implementations
BlockStyle
```

Domain DTOs should remain typed until the serialization boundary.

Avoid propagating:

```php
array<string, mixed>
```

through the domain model when a typed DTO can represent the concept.

---

# 7. PageSchema Version Ownership

For M1:

```php
PageSchema::VERSION === '1.0'
```

The version should not be freely passed into constructors.

The serializer emits the version owned by the implementation.

---

# 8. Gutenberg Hero Block

The initial custom block should support:

```text
eyebrow
title
subtitle
media
media placement
actions
semantic layout
M1 styles
```

## Media placement

Allowed:

```text
background
start
end
```

## Hero actions

Each action includes:

```text
id
label
link
variant
accessibleLabel?
```

Initial variants:

```text
primary
secondary
tertiary
```

## Layout

Allowed semantic configuration:

```text
contentAlignment: start | center | end
verticalAlignment: start | center | end
contentWidth: narrow | medium | wide | full
```

---

# 9. Gutenberg Editor Responsibility

The editor UI should prevent invalid configuration where practical.

Examples:

- title required;
- media placement constrained to allowed options;
- CTA variant constrained to supported values;
- media accessibility information collected;
- invalid/unsupported style keys not exposed;
- direct arbitrary CSS not accepted.

WordPress editor UX is responsible for collecting declarative intent, not previewing Angular internals exactly.

---

# 10. Block Mapper Contract

Recommended abstraction:

```php
interface BlockMapper
{
    public function supports(array $block): bool;

    public function map(array $block): PageBlock;
}
```

The Hero mapper:

```text
raw Gutenberg block
    ↓
validate attributes
    ↓
normalize values
    ↓
typed Hero DTOs
    ↓
PageBlock(type = "hero")
```

The mapper must not directly emit REST JSON.

---

# 11. Mapper Validation

Hero mapper must validate:

```text
title present and valid
eyebrow optional string
subtitle optional string
media structure
media placement
alt/decorative rules
actions array
action link types
action variants
layout values
M1 style whitelist
safe style values
```

Missing required data must fail normalization deliberately.

The mapper must not invent content silently.

---

# 12. Serialization

Serialization is a dedicated boundary.

Required flow:

```text
typed PageSchema
      ↓
V1PageSchemaSerializer
      ↓
array
      ↓
WP_REST_Response
      ↓
WordPress JSON encoding
```

Do not scatter JSON-building logic through mappers/controllers.

Optional absent values are omitted rather than emitted as `null`.

---

# 13. REST API

M1 endpoint:

```text
GET /wp-json/headless-renderer/v1/pages/{slug}
```

Optional:

```text
?locale=en-CA
```

Requirements:

- published content only;
- normalize page ID as string;
- output PageSchema 1.0;
- unknown page → 404;
- unpublished/draft through public endpoint → 404;
- invalid request → 400;
- normalization/internal failure → controlled 500.

The public endpoint must not reveal unnecessary WordPress metadata.

---

# 14. Security Requirements

WordPress-side security must include:

- sanitize rich/user-entered values appropriate to their semantic type;
- validate URLs;
- restrict link protocols;
- restrict Hero media placement;
- restrict CTA variants;
- restrict layout enum values;
- restrict style property names;
- validate style values;
- never serialize executable JavaScript;
- never serialize event handlers;
- never expose credentials;
- never expose drafts through public endpoint;
- avoid exposing internal exception details to public clients.

---

# 15. Accessibility-Producing Requirements

The plugin does not render the final HTML, but it must produce enough metadata for Angular to render accessibly.

M1:

- meaningful images require `alt`;
- decorative images explicitly use `decorative: true`;
- CTA may provide `accessibleLabel`;
- CMS should not allow an inaccessible media state where practical.

Target consumer behavior is WCAG 2.1 AA.

---

# 16. WordPress Testing Requirements

M1 tests:

```text
Hero mapper unit tests
PageSchema builder tests
serializer tests
REST endpoint tests
security/sanitization tests
contract fixture tests
draft/publication boundary tests
```

Must verify:

- canonical fixture shape;
- schemaVersion exactly `1.0`;
- page ID serialized as string;
- PageStatus serialized correctly;
- absent optionals omitted;
- invalid Hero title fails;
- invalid media fails;
- invalid link fails;
- unsupported styles rejected/dropped according to policy;
- drafts return 404 publicly.

---

# 17. M1 WordPress Backlog

## Bootstrap

- [ ] Create private repository.
- [ ] Create plugin entry point.
- [ ] Add Composer/PSR-4 setup.
- [ ] Set PHP requirement to `>=8.2`.
- [ ] Add test/lint tooling.
- [ ] Add shared contract docs.

## Domain

- [ ] `PageStatus`.
- [ ] `PageSchema`.
- [ ] `PageDefinition`.
- [ ] `PageBlock`.
- [ ] Hero DTOs.
- [ ] Media DTO.
- [ ] Link DTOs.
- [ ] M1 style DTO/policy.

## Gutenberg

- [ ] Register Hero block.
- [ ] Editor fields.
- [ ] Media selector.
- [ ] Media placement.
- [ ] CTA editor.
- [ ] Hero layout controls.
- [ ] M1 style controls.

## Mapping

- [ ] Mapper interface.
- [ ] Mapper registry.
- [ ] Hero mapper.
- [ ] Normalization rules.

## Serialization/API

- [ ] V1 serializer.
- [ ] PageSchema builder.
- [ ] Page REST controller.
- [ ] Locale input.
- [ ] publication-state enforcement.

## Tests

- [ ] Valid fixture.
- [ ] Invalid fixtures.
- [ ] Mapper tests.
- [ ] REST tests.
- [ ] Security tests.

---

# 18. WordPress M1 Definition of Done

The PHP project is M1-complete when:

1. Hero can be configured in Gutenberg.
2. Plugin parses and maps it into typed DTOs.
3. Serializer outputs canonical PageSchema JSON.
4. Endpoint exposes only published content.
5. Contract fixtures pass.
6. Invalid Hero configuration fails safely.
7. No Angular-specific implementation detail exists in the plugin.
8. JSON is ready for an independent Angular client to consume.

---


---

# Composer and Dependency Requirements

Composer is required for PSR-4 autoloading and development tooling.

M1 runtime dependency policy:

```text
No third-party runtime packages are required initially.
```

Accepted M1 development packages:

```text
wp-coding-standards/wpcs        ^3.4
szepeviktor/phpstan-wordpress   ^2.0
phpunit/phpunit                 ^11.5
wp-phpunit/wp-phpunit           ^7.1
ergebnis/composer-normalize     ^2.52
```

Detailed rationale, constraints, Composer scripts, and deferred package decisions are documented in:

```text
docs/COMPOSER_DEPENDENCIES.md
```

Rules:

- use Composer packages when they solve a concrete problem or materially improve development quality;
- prefer focused packages over full frameworks;
- do not introduce Laravel or Symfony Framework into the plugin without an explicit architectural decision;
- individual Symfony components or other runtime libraries may be adopted later when justified;
- runtime dependencies must preserve PHP 8.2 compatibility and plugin portability;
- significant dependency changes must be documented.


# 19. Deferred WordPress Requirements

Not part of M1:

```text
recursive nested block mapping
SEO adapters
NavigationSchema endpoints
form Gutenberg block
async validator endpoints
preview workflow
private content mode
cache invalidation webhooks
WPML adapter
Polylang adapter
third-party block adapters
third-party form adapters
```

These remain architectural requirements for later milestones.
