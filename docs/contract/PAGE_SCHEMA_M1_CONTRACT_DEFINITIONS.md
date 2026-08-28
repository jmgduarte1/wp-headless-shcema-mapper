# PageSchema M1 Contract Definitions
## Private Implementation Contract — TypeScript / PHP 8.2

**Status:** Frozen for M1 implementation — Revision 1  
**Classification:** Private / Internal  
**Project:** Headless Angular Renderer  
**Schema Version:** `1.0`  
**Minimum PHP Version:** `8.2`  
**Date:** 2026-08-27

---

# 1. Purpose

This document freezes the minimum PageSchema contract required for Milestone M1 (Walking Skeleton).

M1 proves the complete pipeline:

```text
Gutenberg Hero
    ↓
WordPress Hero Mapper
    ↓
PHP DTOs
    ↓
JSON serialization
    ↓
REST endpoint
    ↓
Angular runtime validation
    ↓
TypeScript model
    ↓
Component Registry
    ↓
HeroComponent
    ↓
SSR
    ↓
Hydration
```

No additional PageSchema capabilities should be added to M1 unless the end-to-end implementation proves they are necessary.

---

# 2. Frozen M1 Scope

M1 implements:

```text
PageStatus
PageSchema
PageDefinition
PageBlock
HeroBlockData
HeroBlock
HeroMedia
HeroAction
HeroLayout
MediaAsset
LinkModel
BlockStyle
ResponsiveStyleValue
```

The Hero is intentionally realistic rather than a title/subtitle-only placeholder. It validates reusable media, link, semantic-layout, style, accessibility, SSR, and hydration boundaries in the first vertical slice.

Explicitly deferred:

```text
children / recursive composition
SEO
page-level theme
navigation endpoint
forms
animations
visibility
tracking
data sources
custom blocks
advanced responsive media
advanced style properties outside the M1 whitelist
```

Those remain part of the broader PageSchema v1 architecture but are not required for the walking skeleton.

M1 also freezes these implementation decisions:

- Hero CTAs are represented only by `actions[]`.
- `children` is a future field for M2. If a block includes `children` during M1, Angular ignores that field and does not pass it to the renderer.
- `generatedAt` is not part of the frozen M1 payload. The M1 validator rejects it until the contract is deliberately expanded.
- M1 style validation accepts only `minHeight`, `padding`, `backgroundColor`, `color`, `fontFamily`, `fontSize`, and `letterSpacing`.
- Angular validation is implemented as a lightweight internal layer behind `PageSchemaValidator`.
- The implementation targets the latest stable Angular and WordPress versions at the time development begins.

---

# 3. Contractual JSON

The canonical M1 payload is:

```json
{
  "schemaVersion": "1.0",
  "locale": "en-CA",
  "page": {
    "id": "42",
    "slug": "home",
    "title": "Home",
    "status": "published",
    "blocks": [
      {
        "id": "hero-main",
        "type": "hero",
        "data": {
          "eyebrow": "Welcome",
          "title": "Software Engineer",
          "subtitle": "Building maintainable digital experiences",
          "media": {
            "placement": "end",
            "image": {
              "src": "https://cms.example.com/hero.webp",
              "alt": "Software engineer",
              "width": 800,
              "height": 800
            }
          },
          "actions": [
            {
              "id": "view-work",
              "label": "View work",
              "variant": "primary",
              "link": { "type": "internal", "path": "/work" }
            }
          ],
          "layout": {
            "contentAlignment": "start",
            "verticalAlignment": "center",
            "contentWidth": "wide"
          }
        },
        "style": {
          "variant": "primary",
          "properties": {
            "minHeight": "70vh",
            "padding": {
              "mobile": "32px 20px",
              "desktop": "80px 64px"
            }
          }
        }
      }
    ]
  }
}
```

The JSON contract is the boundary between WordPress and Angular.

Neither side may depend on implementation details from the other side.

---

# 4. TypeScript Definitions

Recommended file structure:

```text
projects/renderer/src/lib/schema/
├── page-status.ts
├── page-schema.ts
├── page-definition.ts
├── page-block.ts
├── hero-block.ts
└── index.ts
```

## 4.1 PageStatus

```typescript
export type PageStatus =
  | 'published'
  | 'preview'
  | 'private';
```

For M1, the public REST endpoint will only return:

```typescript
'published'
```

`preview` and `private` are defined now to avoid changing the base type later, but their endpoint behavior is deferred.

---

## 4.2 PageBlock

```typescript
export interface PageBlock<TData = unknown> {
  id: string;
  type: string;
  data?: TData;
  style?: BlockStyle;
}
```

Rules:

- `id` is required.
- `type` is required.
- `data` is optional at the generic level.
- Concrete core blocks may make `data` mandatory.
- M1 does not implement `children`; if present, it is treated as a future field and ignored by the M1 Angular validator/normalizer.

---

## 4.3 HeroBlockData and Hero Primitives

```typescript
export interface HeroBlockData {
  eyebrow?: string;
  title: string;
  subtitle?: string;
  media?: HeroMedia;
  actions?: HeroAction[];
  layout?: HeroLayout;
}

export interface HeroMedia {
  image: MediaAsset;
  placement: 'background' | 'start' | 'end';
}

export interface HeroAction {
  id: string;
  label: string;
  link: LinkModel;
  variant?: 'primary' | 'secondary' | 'tertiary';
  accessibleLabel?: string;
}

export interface HeroLayout {
  contentAlignment?: 'start' | 'center' | 'end';
  verticalAlignment?: 'start' | 'center' | 'end';
  contentWidth?: 'narrow' | 'medium' | 'wide' | 'full';
}
```

`title` is required. Media placement is explicit. CTAs are an array rather than fixed primary/secondary fields. Layout describes semantic intent rather than arbitrary CSS.

### M1 MediaAsset

```typescript
export interface MediaAsset {
  src: string;
  alt?: string;
  decorative?: boolean;
  width?: number;
  height?: number;
}
```

An image must provide meaningful `alt` text or `decorative: true`.

WordPress may store and use a media attachment identifier such as `media.id` for editor state, normalization, and diagnostics, but `media.id` is not serialized into `MediaAsset` in the M1 PageSchema payload.

### M1 LinkModel

```typescript
export type LinkModel =
  | { type: 'internal'; path: string }
  | { type: 'external'; url: string; target?: '_self' | '_blank'; rel?: string[] }
  | { type: 'anchor'; anchor: string };
```

### M1 BlockStyle

```typescript
export interface BlockStyle {
  variant?: string;
  properties?: M1StyleProperties;
}

export interface M1StyleProperties {
  minHeight?: ResponsiveStyleValue;
  padding?: ResponsiveStyleValue;
  backgroundColor?: ResponsiveStyleValue;
  color?: ResponsiveStyleValue;
  fontFamily?: ResponsiveStyleValue;
  fontSize?: ResponsiveStyleValue;
  letterSpacing?: ResponsiveStyleValue;
}

export type ResponsiveStyleValue =
  | string
  | number
  | Partial<Record<'mobile' | 'tablet' | 'desktop', string | number>>;
```

The whitelist is deliberately small in M1. Every value must pass the renderer's style safety policy.

---

## 4.4 HeroBlock

```typescript
import { PageBlock } from './page-block';
import { HeroBlockData } from './hero-block-data';

export interface HeroBlock extends PageBlock<HeroBlockData> {
  type: 'hero';
  data: HeroBlockData;
}
```

The literal `type: 'hero'` allows the block type to become a discriminator as additional block types are introduced.

---

## 4.5 M1Block

For M1:

```typescript
import { HeroBlock } from './hero-block';

export type M1Block = HeroBlock;
```

This intentionally looks trivial now.

Later it can evolve into:

```typescript
export type CorePageBlock =
  | HeroBlock
  | RichTextBlock
  | ImageBlock
  | SectionBlock;
```

without changing `PageSchema`.

---

## 4.6 PageDefinition

```typescript
import { PageStatus } from './page-status';
import { M1Block } from './m1-block';

export interface PageDefinition {
  id: string;
  slug: string;
  title: string;
  status: PageStatus;
  blocks: M1Block[];
}
```

For M1, using `M1Block[]` instead of `PageBlock[]` gives stronger runtime/application typing.

When extensibility is introduced, the internal model can evolve without changing the transport contract.

---

## 4.7 PageSchema

```typescript
import { PageDefinition } from './page-definition';

export interface PageSchema {
  schemaVersion: '1.0';
  locale: string;
  page: PageDefinition;
}
```

For M1, `schemaVersion` is intentionally a literal:

```typescript
'1.0'
```

This prevents the application model from silently accepting an unsupported version.

---

# 5. PHP 8.2 Definitions

Recommended plugin structure:

```text
src/
├── Domain/
│   └── Schema/
│       ├── PageStatus.php
│       ├── PageSchema.php
│       ├── PageDefinition.php
│       ├── PageBlock.php
│       └── HeroBlockData.php
├── Mapping/
├── Rest/
└── Serialization/
```

Namespace example:

```php
HeadlessAngular\Schema\Domain\Schema
```

The final vendor namespace may be changed before implementation, but it should be consistent and PSR-4 compatible.

---

# 6. PHP PageStatus

```php
<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

enum PageStatus: string
{
    case Published = 'published';
    case Preview = 'preview';
    case Private = 'private';
}
```

---

# 7. PHP Hero DTOs and Reusable Primitives

```php
<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class HeroBlockData
{
    /**
     * @param list<HeroAction> $actions
     */
    public function __construct(
        public string $title,
        public ?string $eyebrow = null,
        public ?string $subtitle = null,
        public ?HeroMedia $media = null,
        public array $actions = [],
        public ?HeroLayout $layout = null,
    ) {
    }
}

final readonly class HeroMedia
{
    public function __construct(
        public MediaAsset $image,
        public HeroMediaPlacement $placement,
    ) {
    }
}

enum HeroMediaPlacement: string
{
    case Background = 'background';
    case Start = 'start';
    case End = 'end';
}

final readonly class HeroAction
{
    public function __construct(
        public string $id,
        public string $label,
        public LinkModel $link,
        public ?string $variant = null,
        public ?string $accessibleLabel = null,
    ) {
    }
}

final readonly class HeroLayout
{
    public function __construct(
        public ?string $contentAlignment = null,
        public ?string $verticalAlignment = null,
        public ?string $contentWidth = null,
    ) {
    }
}

final readonly class MediaAsset
{
    public function __construct(
        public string $src,
        public ?string $alt = null,
        public bool $decorative = false,
        public ?int $width = null,
        public ?int $height = null,
    ) {
    }
}
```

`LinkModel` is represented by typed link DTOs sharing a common contract, such as `InternalLink`, `ExternalLink`, and `AnchorLink`. The serializer owns their final JSON representation.

---

# 8. PHP PageBlock

A fully generic PHP equivalent to TypeScript generics is unnecessary.

For M1:

```php
<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class PageBlock
{
    public function __construct(
        public string $id,
        public string $type,
        public object $data,
        public ?BlockStyle $style = null,
    ) {
    }
}
```

Using `object` instead of:

```php
array<string, mixed>
```

is intentional.

It preserves typed DTOs such as `HeroBlockData` until the serialization boundary.

Example:

```php
$block = new PageBlock(
    id: 'hero-main',
    type: 'hero',
    data: new HeroBlockData(
        title: 'Hello',
        subtitle: 'World',
    ),
);
```

This is preferred over passing arbitrary associative arrays through the domain layer.

---

# 9. PHP PageDefinition

```php
<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class PageDefinition
{
    /**
     * @param list<PageBlock> $blocks
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $title,
        public PageStatus $status,
        public array $blocks,
    ) {
    }
}
```

Static analysis should verify the `list<PageBlock>` annotation.

---

# 10. PHP PageSchema

```php
<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class PageSchema
{
    public const VERSION = '1.0';

    public function __construct(
        public string $locale,
        public PageDefinition $page,
    ) {
    }
}
```

Important decision:

`schemaVersion` is not passed freely into the constructor.

For M1, the plugin owns the schema version through:

```php
PageSchema::VERSION
```

This prevents application code from accidentally constructing a V1 DTO and labelling it as another schema version.

The serializer emits the constant as `schemaVersion`.

---

# 11. Serialization Boundary

Domain DTOs should not be serialized by scattering `json_encode()` logic throughout the plugin.

Use a dedicated serializer.

Recommended interface:

```php
<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Serialization;

use HeadlessAngular\Schema\Domain\Schema\PageSchema;

interface PageSchemaSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(PageSchema $schema): array;
}
```

Implementation:

```php
final class V1PageSchemaSerializer implements PageSchemaSerializer
{
    public function serialize(PageSchema $schema): array
    {
        return [
            'schemaVersion' => PageSchema::VERSION,
            'locale' => $schema->locale,
            'page' => [
                'id' => $schema->page->id,
                'slug' => $schema->page->slug,
                'title' => $schema->page->title,
                'status' => $schema->page->status->value,
                'blocks' => array_map(
                    fn (PageBlock $block): array => $this->serializeBlock($block),
                    $schema->page->blocks,
                ),
            ],
        ];
    }
}
```

The block serialization logic should dispatch according to the normalized block type/data DTO rather than exposing arbitrary object state.

---

# 12. Optional Values

For transport consistency, M1 adopts:

> Optional absent values are omitted from JSON.

Therefore:

```php
new HeroBlockData(
    title: 'Hello',
    subtitle: null,
)
```

serializes as:

```json
{
  "title": "Hello"
}
```

not:

```json
{
  "title": "Hello",
  "subtitle": null
}
```

Angular therefore models:

```typescript
subtitle?: string;
```

rather than:

```typescript
subtitle: string | null;
```

This convention should be preserved throughout PageSchema unless `null` later has an explicit semantic meaning.

---

# 13. String Normalization

WordPress mapper rules for M1:

## IDs

- convert WordPress IDs to strings at the schema boundary;
- page ID `"42"` is valid regardless of WordPress storing it as integer `42`.

## Slug

- required;
- non-empty;
- normalized from WordPress post slug.

## Page title

- required as string;
- may be empty only if WordPress legitimately permits the page configuration and the renderer supports it;
- M1 should normally expect a non-empty title.

## Hero title

- required;
- trim surrounding accidental whitespace where appropriate;
- reject missing/non-string title;
- do not silently invent a title.

## Hero subtitle

- optional;
- omit when absent or normalized to no meaningful value.

---

# 14. Block Type Constants

Avoid magic strings in PHP.

Recommended:

```php
final class BlockType
{
    public const HERO = 'hero';

    private function __construct()
    {
    }
}
```

For M1:

```php
BlockType::HERO
```

is the only registered normalized block type.

A PHP enum could also represent block types, but a string-based registry will later need to accept extension-defined types. Therefore, block `type` remains a string in the transport/domain model.

---

# 15. Hero Mapper Contract

Recommended interface:

```php
interface BlockMapper
{
    public function supports(array $block): bool;

    public function map(array $block): PageBlock;
}
```

Hero mapper:

```php
final class HeroBlockMapper implements BlockMapper
{
    public function supports(array $block): bool
    {
        return ($block['blockName'] ?? null) === 'headless-angular/hero';
    }

    public function map(array $block): PageBlock
    {
        // Validate and normalize Gutenberg attributes.

        return new PageBlock(
            id: $normalizedId,
            type: BlockType::HERO,
            data: new HeroBlockData(
                title: $title,
                subtitle: $subtitle,
            ),
        );
    }
}
```

Exact Gutenberg attribute names will be finalized with the Hero block implementation.

---

# 16. Angular Runtime Validation

TypeScript interfaces disappear at runtime.

Therefore this is unsafe:

```typescript
const schema = response as PageSchema;
```

M1 MUST perform runtime validation before treating WordPress JSON as trusted `PageSchema`.

The conceptual boundary is:

```text
HttpClient response
    ↓
unknown
    ↓
runtime validator
    ↓
PageSchema
    ↓
renderer
```

---

# 17. Runtime Validation Requirements

The M1 validator must verify:

## PageSchema

- object exists;
- `schemaVersion === '1.0'`;
- locale is a non-empty string;
- page exists.
- `generatedAt` is rejected in M1 because it is not part of the frozen M1 payload.

## PageDefinition

- id is a string;
- slug is a string;
- title is a string;
- status is a recognized PageStatus;
- blocks is an array.

## Hero block

- id is a string;
- `type === 'hero'`;
- data is an object;
- title is a non-empty string;
- eyebrow/subtitle, when present, are strings;
- media placement is `background`, `start`, or `end`;
- media satisfies URL and alt/decorative accessibility rules;
- actions contain valid HeroAction and LinkModel values;
- action variants and layout values are recognized;
- style contains only M1-whitelisted properties and safe values.

Unknown block handling is a renderer policy, but malformed known core blocks must never be trusted.

---

# 18. Runtime Validation Strategy

For M1, keep validation behind an abstraction:

```typescript
export interface PageSchemaValidator {
  validate(value: unknown): PageSchema;
}
```

Example use:

```typescript
const raw: unknown = await firstValueFrom(
  this.http.get<unknown>(url)
);

const schema = this.validator.validate(raw);
```

M1 uses a lightweight internal validator implementation. The validator should remain small, explicit, SSR-compatible, and easy to replace later if the contract grows enough to justify an external validation library.

---

# 19. Validation Error

Recommended Angular error:

```typescript
export class PageSchemaValidationError extends Error {
  constructor(
    message: string,
    public readonly issues: readonly SchemaValidationIssue[] = [],
  ) {
    super(message);
    this.name = 'PageSchemaValidationError';
  }
}
```

```typescript
export interface SchemaValidationIssue {
  path: string;
  code: string;
  message: string;
}
```

Example:

```json
{
  "path": "page.blocks[0].data.title",
  "code": "INVALID_TYPE",
  "message": "Expected a non-empty string."
}
```

This is valuable for debugging the WordPress → Angular contract.

---

# 20. REST Serialization Rules

The WordPress REST layer:

```text
PageSchema DTO
    ↓
V1PageSchemaSerializer
    ↓
array
    ↓
WP_REST_Response
    ↓
WordPress JSON encoding
```

Do not manually concatenate JSON.

Recommended conceptual endpoint flow:

```php
$schema = $pageSchemaBuilder->build($page, $locale);
$payload = $serializer->serialize($schema);

return new WP_REST_Response($payload, 200);
```

---

# 21. HTTP Contract for M1

Endpoint:

```text
GET /wp-json/headless-renderer/v1/pages/{slug}
```

Optional locale:

```text
?locale=en-CA
```

Successful response:

```text
200
```

Unknown published page:

```text
404
```

Draft/unpublished page through public endpoint:

```text
404
```

This avoids revealing whether a private/draft slug exists.

Invalid request:

```text
400
```

Unexpected normalization/server failure:

```text
500
```

---

# 22. Contract Fixture

Create:

```text
fixtures/pageschema/v1/m1-hero-page.json
```

Contents:

```json
{
  "schemaVersion": "1.0",
  "locale": "en-CA",
  "page": {
    "id": "42",
    "slug": "home",
    "title": "Home",
    "status": "published",
    "blocks": [
      {
        "id": "hero-main",
        "type": "hero",
        "data": {
          "title": "Hello",
          "subtitle": "World"
        }
      }
    ]
  }
}
```

This fixture becomes shared contract evidence.

Both implementations should be tested against equivalent fixture data.

---

# 23. Minimum Invalid Fixtures

Create at least:

```text
invalid-schema-version.json
missing-page.json
invalid-page-status.json
hero-missing-title.json
hero-invalid-title.json
hero-invalid-subtitle.json
```

These ensure the contract fails deliberately rather than accidentally.

---

# 24. Contract Tests — WordPress

M1 tests should verify:

- Hero mapper produces `HeroBlockData`.
- Page builder produces `PageSchema`.
- serializer produces canonical field names.
- `schemaVersion` is exactly `1.0`.
- enum status serializes to lowercase transport value.
- optional subtitle is omitted when absent.
- page ID is serialized as string.
- public endpoint rejects unpublished pages.
- endpoint response matches the expected M1 fixture structure.

---

# 25. Contract Tests — Angular

M1 tests should verify:

- canonical fixture validates.
- invalid version fails.
- missing page fails.
- invalid status fails.
- Hero missing title fails.
- invalid subtitle fails.
- validated output is typed as `PageSchema`.
- Hero block resolves as `HeroBlock`.
- malformed input never reaches the renderer as trusted schema.

---

# 26. Naming Conventions

JSON uses:

```text
camelCase
```

Examples:

```text
schemaVersion
```

PHP uses standard PHP naming internally but serializer explicitly owns JSON field names.

TypeScript mirrors JSON naming directly.

Block types use:

```text
camelCase
```

when multiple words are needed in future versions.

Examples:

```text
hero
richText
```

---

# 27. PHP Compatibility Decision

Frozen for this project:

```text
Minimum PHP: 8.2
```

Therefore the plugin may use:

```text
enums
readonly classes
constructor property promotion
union types
nullable types
match expressions
strict typing
```

All PHP source files should begin with:

```php
declare(strict_types=1);
```

where appropriate.

---

# 28. M1 Contract Freeze

The following shape is now frozen for implementation:

```text
PageSchema
├── schemaVersion: "1.0"
├── locale: string
└── page: PageDefinition
    ├── id: string
    ├── slug: string
    ├── title: string
    ├── status: PageStatus
    └── blocks: M1Block[]
        └── HeroBlock
            ├── id: string
            ├── type: "hero"
            ├── data: HeroBlockData
            │   ├── eyebrow?: string
            │   ├── title: string
            │   ├── subtitle?: string
            │   ├── media?: HeroMedia
            │   │   ├── placement: background | start | end
            │   │   └── image: MediaAsset
            │   ├── actions?: HeroAction[]
            │   │   └── link: LinkModel
            │   └── layout?: HeroLayout
            └── style?: BlockStyle
```

The richer Hero is intentional: M1 validates a realistic CMS-driven component, not merely a text placeholder.

---

# 29. Next Implementation Step

With this contract frozen, implementation can begin.

Recommended order:

```text
1. Bootstrap WordPress plugin
2. Add PHP DTOs
3. Add V1 serializer
4. Add Hero Gutenberg block
5. Add Hero mapper
6. Add PageSchema builder
7. Add REST endpoint
8. Verify canonical JSON fixture
9. Bootstrap Angular library
10. Add TypeScript contract
11. Implement runtime validator
12. Add PageService
13. Add registry
14. Add HeroComponent
15. Prove SSR
16. Prove hydration
```

The contract should not be expanded before step 16 unless implementation evidence shows that the M1 model is insufficient.
