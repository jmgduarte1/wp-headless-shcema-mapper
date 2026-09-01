# Headless Angular Renderer — Shared Contract Requirements

**Classification:** Private / Internal  
**Applies to:** `headless-angular-schema` and `@headless-angular/renderer`  
**Contract:** `PageSchema v1`  
**M1 Contract Status:** Rebaselined for native Gutenberg blocks

---

# 1. Purpose

This document contains requirements that belong to neither implementation independently. They define the contract and interoperability rules that both projects must respect.

The two implementations remain independently versioned and independently deployable.

```text
WordPress Plugin
      ↓ produces
PageSchema v1
      ↓ consumes
Angular Renderer
```

Neither side may depend on the other's internal implementation.

---

# 2. Shared Architectural Boundary

The mandatory boundary is:

> WordPress describes content, composition, configuration, and intent. Angular owns rendering, execution, application behavior, and runtime interaction.

Consequences:

- Angular MUST NOT depend on raw Gutenberg block names.
- WordPress MUST NOT emit Angular templates or executable JavaScript.
- Interactive elements are described declaratively and materialized by Angular.
- `PageSchema` is the public integration contract.
- Schema versioning is independent from plugin/package versions.

---

# 3. Shared M1 Contract

M1 implements:

```text
PageStatus
PageSchema
PageDefinition
PageBlock
BasicBlockData
MediaAsset
LinkModel
BlockStyle
ResponsiveStyleValue
children
```

Deferred beyond M1:

```text
SEO
page-level theme
navigation endpoint
forms
animations
visibility
tracking
data sources
composed blocks such as Hero
advanced responsive media
advanced style properties
```

M1 implementation decisions:

- M1 is based on native Gutenberg blocks and preserves nested `children`; it does not require a proprietary `hero` block.
- Links and buttons use the normalized `LinkModel` shape. A composed Hero action model is deferred to a later milestone.
- `children` is part of the M1 contract for structural blocks such as sections, groups, columns, and details.
- `generatedAt` is part of the broader PageSchema v1 design, but it is excluded from the frozen M1 payload and should be rejected by the M1 validator until the contract is deliberately expanded.
- The M1 style whitelist is limited to the properties documented by the current PHP and Angular implementations, including layout, spacing, color/background, typography, dimensions, and alignment values.
- Angular runtime validation for M1 uses a lightweight internal validation layer behind `PageSchemaValidator`, not an external schema library.
- Initial implementation targets the latest stable Angular and WordPress versions available when development begins, while preserving the documented PHP `8.2` minimum.

---

# 4. Canonical M1 JSON

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
              "link": {
                "type": "internal",
                "path": "/work"
              }
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

---

# 5. Shared Schema Rules

## 5.1 Version

```text
schemaVersion = "1.0"
```

M1 consumers must explicitly validate this version.

## 5.2 Locale

- required;
- string;
- intended to follow BCP 47-style tags;
- included in cache identity.

## 5.3 IDs

- transported as strings;
- block IDs unique within the page;
- stable where practical;
- not treated as cross-system database identifiers.

## 5.4 Optional values

Absent optional values are omitted rather than serialized as `null` unless `null` later gains explicit semantic meaning.

## 5.5 Naming

JSON uses `camelCase`.

Examples:

```text
schemaVersion
contentAlignment
accessibleLabel
```

## 5.6 Security

Schema may never contain:

```text
JavaScript source
event-handler code
Angular templates
credentials
API secrets
authorization headers
unrestricted CSS
server filesystem paths
```

---

# 6. Shared Native Block Semantics

M1 supports these normalized block families:

```text
section/div containers
columns and column children
headings and paragraphs
images
links and buttons
details/summary disclosures
spacers
children?
style?
  whitelisted properties
```

The schema describes intent.

The schema describes intent and hierarchy. Exact DOM/CSS implementation belongs to Angular.

---

# 7. Media Accessibility Contract

Every non-decorative image must provide meaningful alternative text.

Valid:

```json
{
  "src": "...",
  "alt": "Developer working at a computer",
  "decorative": false
}
```

Valid decorative image:

```json
{
  "src": "...",
  "decorative": true
}
```

Invalid:

```json
{
  "src": "..."
}
```

when the image is meaningful and neither `alt` nor `decorative` is supplied.

---

# 8. Link Contract

M1 supports:

```text
internal
external
anchor
```

The WordPress side normalizes the intent.

Angular decides how to execute navigation.

An internal link is not serialized as a WordPress URL if a normalized application path is available.

---

# 9. Style Contract

Styles are declarative and restricted.

M1 whitelist:

```text
minHeight
padding
backgroundColor
color
fontFamily
fontSize
letterSpacing
```

Values may be direct safe values or responsive values:

```json
{
  "mobile": "32px 20px",
  "desktop": "80px 64px"
}
```

The WordPress implementation must not intentionally emit unsupported style properties.

The Angular implementation must not blindly trust received style values.

---

# 10. Shared Fixtures

Both repositories should maintain equivalent test fixtures.

Required valid fixture:

```text
m1-hero-page.json
```

Required invalid cases:

```text
invalid-schema-version.json
missing-page.json
invalid-page-status.json
hero-missing-title.json
hero-invalid-title.json
hero-invalid-subtitle.json
hero-invalid-media.json
hero-invalid-link.json
hero-invalid-style.json
```

Fixtures represent contract evidence and should change only deliberately.

---

# 11. Shared Compatibility Rules

Backward-compatible V1.x evolution may add optional fields.

Breaking changes require a new schema major version.

Neither WordPress nor Angular release versions determine `PageSchema` version.

Example:

```text
WordPress plugin 0.6.0 → PageSchema 1.x
Angular package 0.9.0 → PageSchema 1.x
```

---

# 12. M1 End-to-End Acceptance

M1 is accepted when:

1. A page is configured in Gutenberg using editable native blocks.
2. WordPress normalizes it into canonical PageSchema JSON.
3. Angular validates the JSON at runtime.
4. Angular resolves supported basic block types through a component registry.
5. The renderer outputs the page title and semantic native elements for the supported blocks.
6. Nested composition, child order, columns, alignment, and supported styles are preserved.
7. SSR contains the page content in the initial HTML.
8. Hydration completes without mismatch.
9. The initial client does not duplicate the server fetch unnecessarily.
10. Invalid contract data fails safely.
11. Draft content is not exposed through the public page endpoint.

---

# 13. Source Documents

The shared contract requirements are derived from:

```text
HEADLESS_ANGULAR_RENDERER_TECHNICAL_ARCHITECTURE.md
HEADLESS_ANGULAR_RENDERER_IMPLEMENTATION_PLAN.md
PAGE_SCHEMA_SPECIFICATION.md
PAGE_SCHEMA_M1_CONTRACT_DEFINITIONS.md
```

The authoritative detailed schema remains `PAGE_SCHEMA_SPECIFICATION.md` and the frozen M1 implementation contract remains `PAGE_SCHEMA_M1_CONTRACT_DEFINITIONS.md`.
