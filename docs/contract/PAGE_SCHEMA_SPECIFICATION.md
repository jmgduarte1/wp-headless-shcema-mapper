# PageSchema v1
## Private Contract Specification

**Status:** Draft Contract Specification  
**Classification:** Private / Internal  
**Project:** Headless Angular Renderer  
**Schema Name:** `PageSchema`  
**Schema Version:** `1.0`  
**Date:** 2026-08-27

---

# 1. Purpose

`PageSchema` is the normalized, versioned contract exchanged between the WordPress plugin `headless-angular-schema` and the Angular library `@headless-angular/renderer`.

The schema intentionally abstracts away WordPress and Gutenberg implementation details.

The Angular renderer MUST NOT depend on raw Gutenberg block names, WordPress post internals, plugin-specific metadata structures, or arbitrary HTML for interactive features.

The core principle is:

> WordPress produces a normalized declarative description of a page. Angular validates and materializes that description into native Angular components and behavior.

---

# 2. Design Goals

`PageSchema v1` MUST:

1. Be independent from Gutenberg internals.
2. Be explicit and versioned.
3. Support recursive/nested blocks.
4. Support Angular SSR and hydration.
5. Support native Angular components.
6. Support Angular Material-based interactive controls where appropriate.
7. Support WCAG 2.1 AA-oriented rendering requirements.
8. Support extensibility for consumer-defined block types.
9. Support media, links, SEO, themes, styling, visibility, tracking, forms, and navigation references.
10. Permit backward-compatible schema evolution.
11. Avoid executable code.
12. Avoid unrestricted CSS.
13. Avoid unrestricted external endpoint execution.
14. Be suitable for runtime validation.
15. Be transportable as JSON.

---

# 3. Top-Level Contract

```ts
export interface PageSchema {
  schemaVersion: string;
  locale: string;
  generatedAt?: string;
  page: PageDefinition;
}
```

Example:

```json
{
  "schemaVersion": "1.0",
  "locale": "en-CA",
  "generatedAt": "2026-08-27T15:00:00Z",
  "page": {
    "id": "42",
    "slug": "home",
    "title": "Home",
    "status": "published",
    "seo": {},
    "theme": {},
    "blocks": []
  }
}
```

---

# 4. Field Rules

## 4.1 `schemaVersion`

Required.

```json
"schemaVersion": "1.0"
```

Rules:

- MUST be present.
- MUST be parseable as a supported schema version.
- MUST be evaluated independently from plugin/package versions.
- Angular MUST reject unsupported major versions.
- Angular MAY adapt supported older versions to its internal normalized model.

---

## 4.2 `locale`

Required.

Example:

```json
"locale": "en-CA"
```

Rules:

- SHOULD follow a BCP 47-style language tag.
- MUST represent the locale used to resolve the page content.
- MUST be included in cache keys where locale-aware caching is used.

---

## 4.3 `generatedAt`

Optional.

Example:

```json
"generatedAt": "2026-08-27T15:00:00Z"
```

Purpose:

- diagnostics;
- cache debugging;
- observability;
- preview freshness analysis.

It MUST NOT be used as a security boundary.

---

# 5. PageDefinition

```ts
export interface PageDefinition {
  id: string;
  slug: string;
  title: string;
  status: PageStatus;
  seo?: SeoMetadata;
  theme?: ThemeSchema;
  blocks: PageBlock[];
  metadata?: PageMetadata;
}
```

---

# 6. PageStatus

```ts
export type PageStatus =
  | 'published'
  | 'preview'
  | 'private';
```

Rules:

- Public endpoints MUST return only `published`.
- Preview endpoints MAY return `preview`.
- Private endpoints MAY return `private`.
- The consumer MUST NOT infer authorization from this field alone.

---

# 7. PageMetadata

Optional non-rendering metadata.

```ts
export interface PageMetadata {
  modifiedAt?: string;
  publishedAt?: string;
  template?: string;
}
```

This object MUST remain minimal and MUST NOT expose WordPress internals unnecessarily.

---

# 8. PageBlock

```ts
export interface PageBlock<TData = unknown> {
  id: string;
  type: string;
  data?: TData;
  children?: PageBlock[];
  style?: BlockStyle;
  animation?: AnimationConfig;
  visibility?: VisibilityRules;
  tracking?: TrackingConfig;
  dataSource?: DataSourceConfig;
  metadata?: BlockMetadata;
}
```

---

# 9. Block Rules

Every block:

- MUST have `id`.
- MUST have `type`.
- MAY have `data`.
- MAY have recursive `children`.
- MAY have styling.
- MAY have animation.
- MAY have visibility rules.
- MAY have tracking metadata.
- MAY have deferred data source metadata.
- MAY have non-rendering metadata.

Angular MUST resolve block types through the Component Registry.

WordPress MUST normalize raw Gutenberg blocks into this structure.

---

# 10. Block ID

```json
"id": "hero-main"
```

Rules:

- MUST be unique within a page.
- SHOULD be stable across renders when the originating CMS block remains the same.
- SHOULD be suitable for diagnostics and hydration correlation.
- MUST NOT be treated as a database primary key by consumers.

---

# 11. Block Type

```json
"type": "hero"
```

Core V1 types:

```text
hero
richText
image
section
columns
cta
gallery
slider
form
```

Consumer-defined types are allowed.

Example:

```json
"type": "projects"
```

The core Angular library does not need to understand `projects` if the consumer registers it.

---

# 12. Recursive Composition

Nested blocks are supported.

Example:

```json
{
  "id": "section-1",
  "type": "section",
  "children": [
    {
      "id": "columns-1",
      "type": "columns",
      "children": [
        {
          "id": "image-1",
          "type": "image",
          "data": {}
        },
        {
          "id": "copy-1",
          "type": "richText",
          "data": {}
        }
      ]
    }
  ]
}
```

Rules:

- Angular MUST render recursively.
- WordPress MUST validate block nesting.
- A configurable maximum depth SHOULD exist.
- Cycles are impossible in JSON tree form and MUST NOT be emulated through references in V1.

---

# 13. Hero Block

```ts
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

Example:

```json
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
      "minHeight": "70vh"
    }
  }
}
```

---

# 14. RichText Block

```ts
export interface RichTextBlockData {
  html: string;
}
```

Example:

```json
{
  "id": "about-copy",
  "type": "richText",
  "data": {
    "html": "<p>Example content.</p>"
  }
}
```

Rules:

- HTML MUST be sanitized before or during normalization.
- Angular MUST apply an additional safety boundary.
- Event handlers are prohibited.
- Script tags are prohibited.
- Executable URLs are prohibited.
- Rich text MUST NOT become an escape hatch for arbitrary interactive components.

---

# 15. Image Block

```ts
export interface ImageBlockData {
  media: MediaAsset;
  caption?: string;
}
```

Example:

```json
{
  "id": "profile-image",
  "type": "image",
  "data": {
    "media": {
      "src": "https://cms.example.com/image.jpg",
      "alt": "Developer working at a desk",
      "decorative": false,
      "width": 1600,
      "height": 900,
      "loading": "lazy"
    }
  }
}
```

---

# 16. Section Block

```ts
export interface SectionBlockData {
  semanticTag?: 'section' | 'div' | 'article' | 'aside';
  anchorId?: string;
}
```

The primary content usually lives in `children`.

Example:

```json
{
  "id": "services-section",
  "type": "section",
  "data": {
    "semanticTag": "section",
    "anchorId": "services"
  },
  "children": []
}
```

---

# 17. Columns Block

```ts
export interface ColumnsBlockData {
  columns?: number;
  gap?: SemanticOrCssValue;
  stackAt?: SemanticBreakpoint;
}
```

The actual column content lives in children.

Example:

```json
{
  "id": "two-column-layout",
  "type": "columns",
  "data": {
    "columns": 2,
    "stackAt": "tablet"
  },
  "children": []
}
```

---

# 18. CTA Block

```ts
export interface CtaBlockData {
  label: string;
  link: LinkModel;
  variant?: string;
  icon?: MediaAsset;
  accessibleLabel?: string;
}
```

Example:

```json
{
  "id": "contact-cta",
  "type": "cta",
  "data": {
    "label": "Contact me",
    "link": {
      "type": "internal",
      "path": "/contact"
    },
    "variant": "primary"
  }
}
```

---

# 19. Gallery Block

```ts
export interface GalleryBlockData {
  items: MediaAsset[];
  layout?: 'grid' | 'masonry';
  columns?: ResponsiveValue<number>;
}
```

Example:

```json
{
  "id": "gallery-1",
  "type": "gallery",
  "data": {
    "layout": "grid",
    "columns": {
      "mobile": 1,
      "tablet": 2,
      "desktop": 3
    },
    "items": []
  }
}
```

---

# 20. Slider Block

```ts
export interface SliderBlockData {
  slides: SliderItem[];
  autoplay?: boolean;
  intervalMs?: number;
  loop?: boolean;
  showControls?: boolean;
  showIndicators?: boolean;
}
```

```ts
export interface SliderItem {
  id: string;
  media?: MediaAsset;
  title?: string;
  description?: string;
  action?: ActionLink;
}
```

Rules:

- `intervalMs` MUST be bounded.
- Autoplay behavior MUST respect accessibility policy.
- Renderer MUST honor reduced-motion preferences.
- Initial SSR output MUST be deterministic.

---

# 21. Form Block

```ts
export interface FormBlockData {
  formId: string;
  fields: FormFieldSchema[];
  submit: FormSubmitConfig;
  onSuccess?: FormAction[];
  onFailure?: FormAction[];
  successMessage?: string;
  failureMessage?: string;
}
```

---

# 22. FormFieldSchema

```ts
export interface FormFieldSchema {
  name: string;
  type: FormFieldType;
  label: string;
  value?: unknown;
  placeholder?: string;
  hint?: string;
  required?: boolean;
  disabled?: boolean;
  readOnly?: boolean;
  options?: FormFieldOption[];
  validators?: ValidatorSchema[];
  asyncValidators?: AsyncValidatorSchema[];
  accessibility?: FieldAccessibility;
  metadata?: Record<string, unknown>;
}
```

---

# 23. FormFieldType

Initial V1 candidates:

```ts
export type FormFieldType =
  | 'text'
  | 'email'
  | 'password'
  | 'number'
  | 'textarea'
  | 'select'
  | 'checkbox'
  | 'radio'
  | 'date';
```

Custom controls may be added through a registry.

---

# 24. FormFieldOption

```ts
export interface FormFieldOption {
  label: string;
  value: string | number | boolean;
  disabled?: boolean;
}
```

---

# 25. Field Accessibility

```ts
export interface FieldAccessibility {
  ariaLabel?: string;
  ariaDescription?: string;
}
```

Rules:

- Every control MUST have an accessible name.
- `ariaLabel` MUST NOT be used as a substitute for a visible label unless a valid design reason exists.
- Error states must be announced accessibly by the renderer.

---

# 26. ValidatorSchema

```ts
export interface ValidatorSchema {
  type: string;
  value?: unknown;
  message?: string;
}
```

Core validator types:

```text
required
email
minLength
maxLength
min
max
pattern
```

Example:

```json
{
  "type": "maxLength",
  "value": 120,
  "message": "Maximum 120 characters."
}
```

Custom validator names are allowed if registered by the Angular consumer.

---

# 27. AsyncValidatorSchema

Designed in V1 contract, implementation deferred.

```ts
export interface AsyncValidatorSchema {
  type: string;
  debounceMs?: number;
  message?: string;
}
```

Example:

```json
{
  "type": "emailAvailability",
  "debounceMs": 300
}
```

The initial implementation will not execute async validators for PortfolioJMGD.

Future async validation is expected to use protected WordPress validation endpoints.

---

# 28. Form Submit Configuration

```ts
export type FormSubmitConfig =
  | NamedEndpointSubmit
  | AdapterSubmit
  | DirectEndpointSubmit;
```

---

# 29. NamedEndpointSubmit

Preferred strategy.

```ts
export interface NamedEndpointSubmit {
  type: 'namedEndpoint';
  endpoint: string;
  method?: 'POST' | 'PUT' | 'PATCH';
}
```

Example:

```json
{
  "type": "namedEndpoint",
  "endpoint": "contactApi"
}
```

Angular resolves the endpoint through consumer configuration.

---

# 30. AdapterSubmit

```ts
export interface AdapterSubmit {
  type: 'adapter';
  action: string;
}
```

Example:

```json
{
  "type": "adapter",
  "action": "salesforceLead"
}
```

Angular resolves the adapter through a registry.

---

# 31. DirectEndpointSubmit

```ts
export interface DirectEndpointSubmit {
  type: 'endpoint';
  url: string;
  method: 'POST' | 'PUT' | 'PATCH';
}
```

Rules:

- MUST pass Angular endpoint security policy.
- MUST match an allowlisted origin or path.
- MUST NOT include secrets.
- MUST NOT define arbitrary headers containing credentials.

---

# 32. Form Actions

```ts
export type FormAction =
  | ShowMessageAction
  | ResetFormAction
  | NavigateAction
  | RedirectAction;
```

---

# 33. ShowMessageAction

```ts
export interface ShowMessageAction {
  type: 'showMessage';
  message: string;
  severity?: 'success' | 'info' | 'warning' | 'error';
}
```

---

# 34. ResetFormAction

```ts
export interface ResetFormAction {
  type: 'resetForm';
}
```

---

# 35. NavigateAction

```ts
export interface NavigateAction {
  type: 'navigate';
  path: string;
}
```

---

# 36. RedirectAction

```ts
export interface RedirectAction {
  type: 'redirect';
  url: string;
}
```

Rules:

- Redirect URL MUST be validated.
- External redirect policy SHOULD be configurable.

---

# 37. MediaAsset

```ts
export interface MediaAsset {
  src: string;
  alt?: string;
  decorative?: boolean;
  width?: number;
  height?: number;
  srcSet?: MediaSource[];
  sizes?: string;
  loading?: 'eager' | 'lazy';
  mimeType?: string;
  caption?: string;
}
```

---

# 38. MediaSource

```ts
export interface MediaSource {
  src: string;
  width?: number;
  density?: number;
}
```

Rules:

- `src` MUST be validated.
- Images MUST define either meaningful `alt` or `decorative: true`.
- If `decorative: true`, renderer SHOULD emit empty alt text.
- Width and height SHOULD be supplied where available.
- Responsive sources SHOULD be preserved from WordPress where possible.

---

# 39. LinkModel

```ts
export type LinkModel =
  | InternalLink
  | ExternalLink
  | AnchorLink
  | EmailLink
  | TelephoneLink;
```

---

# 40. InternalLink

```ts
export interface InternalLink {
  type: 'internal';
  path: string;
  query?: Record<string, string | number | boolean>;
  fragment?: string;
}
```

---

# 41. ExternalLink

```ts
export interface ExternalLink {
  type: 'external';
  url: string;
  target?: '_self' | '_blank';
  rel?: string[];
}
```

Security rule:

If `target = "_blank"`, the renderer SHOULD ensure safe `rel` behavior such as `noopener` where applicable.

---

# 42. AnchorLink

```ts
export interface AnchorLink {
  type: 'anchor';
  anchor: string;
}
```

---

# 43. EmailLink

```ts
export interface EmailLink {
  type: 'email';
  address: string;
  subject?: string;
}
```

---

# 44. TelephoneLink

```ts
export interface TelephoneLink {
  type: 'telephone';
  number: string;
}
```

---

# 45. ActionLink

```ts
export interface ActionLink {
  label: string;
  link: LinkModel;
  accessibleLabel?: string;
}
```

---

# 46. SeoMetadata

```ts
export interface SeoMetadata {
  title?: string;
  description?: string;
  canonical?: string;
  robots?: RobotsMetadata;
  openGraph?: OpenGraphMetadata;
  twitter?: SocialCardMetadata;
}
```

---

# 47. RobotsMetadata

```ts
export interface RobotsMetadata {
  index?: boolean;
  follow?: boolean;
}
```

---

# 48. OpenGraphMetadata

```ts
export interface OpenGraphMetadata {
  title?: string;
  description?: string;
  url?: string;
  type?: string;
  image?: MediaAsset;
}
```

---

# 49. SocialCardMetadata

```ts
export interface SocialCardMetadata {
  card?: 'summary' | 'summary_large_image';
  title?: string;
  description?: string;
  image?: MediaAsset;
}
```

The WordPress plugin normalizes the source. Angular does not care whether values originate from WordPress core, Yoast, Rank Math, or another provider.

---

# 50. ThemeSchema

```ts
export interface ThemeSchema {
  variant?: string;
  tokens?: Record<string, SemanticOrCssValue>;
  overrides?: BlockStyle;
}
```

Theme is hybrid:

- Angular owns authoritative design-system tokens.
- WordPress may reference semantic tokens.
- WordPress may apply allowed global overrides.

---

# 51. BlockStyle

```ts
export interface BlockStyle {
  variant?: string;
  alignment?: 'start' | 'center' | 'end' | 'stretch';
  spacing?: SemanticOrCssValue;
  properties?: StyleProperties;
}
```

---

# 52. StyleProperties

Representative V1 shape:

```ts
export interface StyleProperties {
  fontFamily?: ResponsiveStyleValue;
  fontSize?: ResponsiveStyleValue;
  fontWeight?: ResponsiveStyleValue;
  fontStyle?: ResponsiveStyleValue;
  lineHeight?: ResponsiveStyleValue;
  letterSpacing?: ResponsiveStyleValue;
  textAlign?: ResponsiveStyleValue;
  textTransform?: ResponsiveStyleValue;
  textDecoration?: ResponsiveStyleValue;

  display?: ResponsiveStyleValue;
  width?: ResponsiveStyleValue;
  minWidth?: ResponsiveStyleValue;
  maxWidth?: ResponsiveStyleValue;
  height?: ResponsiveStyleValue;
  minHeight?: ResponsiveStyleValue;
  maxHeight?: ResponsiveStyleValue;
  margin?: ResponsiveStyleValue;
  padding?: ResponsiveStyleValue;
  gap?: ResponsiveStyleValue;
  flexDirection?: ResponsiveStyleValue;
  alignItems?: ResponsiveStyleValue;
  justifyContent?: ResponsiveStyleValue;

  position?: ResponsiveStyleValue;
  top?: ResponsiveStyleValue;
  right?: ResponsiveStyleValue;
  bottom?: ResponsiveStyleValue;
  left?: ResponsiveStyleValue;
  zIndex?: ResponsiveStyleValue;

  background?: ResponsiveStyleValue;
  backgroundColor?: ResponsiveStyleValue;
  color?: ResponsiveStyleValue;
  border?: ResponsiveStyleValue;
  borderRadius?: ResponsiveStyleValue;
  boxShadow?: ResponsiveStyleValue;
  opacity?: ResponsiveStyleValue;
}
```

This interface represents the architectural whitelist, not permission for arbitrary values.

The exact allowed value grammar is defined separately by the security/styling policy.

---

# 53. Responsive Values

```ts
export type SemanticBreakpoint =
  | 'mobile'
  | 'tablet'
  | 'desktop';
```

```ts
export type ResponsiveValue<T> =
  | T
  | Partial<Record<SemanticBreakpoint, T>>;
```

```ts
export type ResponsiveStyleValue =
  ResponsiveValue<SemanticOrCssValue>;
```

The consumer maps semantic breakpoints to actual media-query values.

---

# 54. SemanticOrCssValue

```ts
export type SemanticOrCssValue =
  | string
  | number;
```

Interpretation depends on property and policy.

Examples:

```json
"large"
```

```json
"2rem"
```

```json
48
```

The renderer MUST NOT blindly write values into styles without validation.

---

# 55. AnimationConfig

```ts
export interface AnimationConfig {
  type: AnimationType;
  durationMs?: number;
  delayMs?: number;
  trigger?: AnimationTrigger;
}
```

```ts
export type AnimationType =
  | 'none'
  | 'fadeIn'
  | 'slideUp'
  | 'scaleIn';
```

```ts
export type AnimationTrigger =
  | 'onLoad'
  | 'onEnterViewport';
```

Rules:

- duration and delay MUST be bounded.
- renderer MUST honor reduced-motion preferences.
- no arbitrary animation code is allowed.

---

# 56. VisibilityRules

```ts
export interface VisibilityRules {
  authenticated?: boolean;
  roles?: string[];
  locales?: string[];
  devices?: SemanticBreakpoint[];
}
```

Rules:

- presentation only;
- NOT an authorization mechanism;
- consumer application provides runtime context.

---

# 57. TrackingConfig

```ts
export interface TrackingConfig {
  event: string;
  category?: string;
  label?: string;
  metadata?: Record<string, string | number | boolean>;
}
```

The renderer emits generic tracking/lifecycle events.

The schema MUST NOT name or configure vendor-specific SDKs directly.

---

# 58. DataSourceConfig

Designed in V1, implementation deferred.

```ts
export type DataSourceConfig =
  | NamedDataSource
  | AdapterDataSource
  | EndpointDataSource;
```

---

# 59. NamedDataSource

```ts
export interface NamedDataSource {
  type: 'namedSource';
  source: string;
  params?: Record<string, string | number | boolean>;
}
```

Preferred future strategy.

---

# 60. AdapterDataSource

```ts
export interface AdapterDataSource {
  type: 'adapter';
  source: string;
  params?: Record<string, unknown>;
}
```

---

# 61. EndpointDataSource

```ts
export interface EndpointDataSource {
  type: 'endpoint';
  url: string;
  method?: 'GET' | 'POST';
}
```

Rules:

- endpoint MUST pass an allowlist policy.
- implementation is deferred.
- no credentials are carried in schema.

---

# 62. BlockMetadata

```ts
export interface BlockMetadata {
  sourceType?: string;
  debugLabel?: string;
}
```

Rules:

- optional;
- intended for diagnostics;
- SHOULD NOT expose sensitive CMS internals;
- consumers MUST NOT rely on `sourceType` for rendering.

---

# 63. NavigationSchema

Navigation is a sibling contract to PageSchema.

```ts
export interface NavigationSchema {
  schemaVersion: string;
  locale: string;
  location: string;
  items: NavigationItem[];
}
```

---

# 64. NavigationItem

```ts
export interface NavigationItem {
  id: string;
  label: string;
  link: LinkModel;
  children?: NavigationItem[];
  icon?: MediaAsset;
  accessibleLabel?: string;
  style?: BlockStyle;
  visibility?: VisibilityRules;
  metadata?: NavigationMetadata;
}
```

---

# 65. NavigationMetadata

```ts
export interface NavigationMetadata {
  order?: number;
  variant?: string;
}
```

The Angular library initially provides navigation data/services and does not force a universal navbar component.

---

# 66. Page Endpoint

```text
GET /wp-json/headless-renderer/v1/pages/{slug}
```

Optional query:

```text
?locale=en-CA
```

Expected success:

```http
200 OK
Content-Type: application/json
```

Expected failures:

```text
404 page not found
401/403 protected access
400 invalid locale/request
500 normalization failure
```

Detailed error envelopes are defined separately.

---

# 67. Navigation Endpoint

```text
GET /wp-json/headless-renderer/v1/menus/{location}
```

Optional query:

```text
?locale=en-CA
```

---

# 68. Preview Endpoint

Reserved contract shape:

```text
GET /wp-json/headless-renderer/v1/preview/{id}
```

or equivalent implementation.

Preview must require authenticated/signed access.

Exact URL and token strategy are implementation details and may change without altering PageSchema.

---

# 69. Validation Endpoint

Reserved for later async validator support.

Conceptual shape:

```text
POST /wp-json/headless-renderer/v1/validation/{validator}
```

This is NOT required for the PortfolioJMGD initial implementation.

---

# 70. Error Envelope

Recommended generic API error shape:

```ts
export interface ApiErrorResponse {
  error: {
    code: string;
    message: string;
    details?: Record<string, unknown>;
    correlationId?: string;
  };
}
```

Example:

```json
{
  "error": {
    "code": "PAGE_NOT_FOUND",
    "message": "The requested page could not be found."
  }
}
```

Sensitive internals MUST NOT be exposed.

---

# 71. Schema Validation Rules

Angular MUST validate:

- top-level object shape;
- schema version;
- locale;
- page shape;
- block IDs;
- block types;
- recursive structure;
- known core block data;
- media URLs;
- link types;
- style keys;
- style values;
- form schema;
- submit strategy;
- action types;
- animation values;
- visibility shape.

Consumer-defined custom block payloads may be validated by consumer-provided validators.

---

# 72. Unknown Block Handling

Development:

```text
render diagnostic fallback
emit UNKNOWN_BLOCK_TYPE
```

Production:

```text
skip or fallback according to configuration
emit UNKNOWN_BLOCK_TYPE
do not crash whole page
```

---

# 73. Unknown Fields

Forward compatibility policy:

- unknown optional fields SHOULD be ignored unless security-sensitive;
- unknown block types follow unknown-block policy;
- unknown enum values MUST fail validation for that feature;
- unknown style properties MUST be rejected or dropped;
- unknown form action types MUST NOT execute;
- unknown submit modes MUST fail form-schema validation.

---

# 74. Backward-Compatible Changes

Examples of compatible V1.x changes:

- adding optional fields;
- adding optional metadata;
- adding new core block types;
- adding new optional action types if older consumers safely ignore them;
- adding new optional SEO fields.

Any addition that older consumers cannot safely ignore requires careful compatibility review.

---

# 75. Breaking Changes

Examples:

- renaming required fields;
- changing block meaning;
- changing field types incompatibly;
- removing required structures;
- changing submit semantics;
- changing security assumptions.

Breaking changes require a new schema major version.

---

# 76. Version Adapters

Angular may normalize multiple versions:

```text
PageSchema 1.x
    ↓
V1 adapter
    ↓
InternalPageModel

PageSchema 2.x
    ↓
V2 adapter
    ↓
InternalPageModel
```

The internal renderer should ideally operate on one normalized internal model.

---

# 77. Security Constraints

PageSchema MUST NOT contain:

```text
JavaScript source
event-handler attributes
Angular template code
Angular class names intended for dynamic execution
credentials
API secrets
authorization headers
arbitrary executable URLs
unrestricted CSS
server filesystem paths
WordPress database details
```

---

# 78. Accessibility Constraints

Schema producers should enforce:

- image `alt` or `decorative`;
- accessible form labels;
- accessible link/button names;
- sensible heading content;
- safe animation configuration;
- no inaccessible form field definitions;
- meaningful error messages where configurable.

Angular remains responsible for final accessible rendering behavior.

---

# 79. SSR Constraints

PageSchema values MUST be deterministic for the request.

The renderer must be able to interpret the same schema on server and client.

Schema must not depend on browser-only runtime state for its basic structural meaning.

Visibility rules may depend on runtime context, but SSR/client evaluation must be coordinated to avoid hydration mismatch.

---

# 80. Cache Identity

Recommended page cache identity:

```text
page:{schemaMajor}:{locale}:{slug}
```

Example:

```text
page:1:en-CA:home
```

Recommended navigation cache identity:

```text
menu:{schemaMajor}:{locale}:{location}
```

---

# 81. Full Example

```json
{
  "schemaVersion": "1.0",
  "locale": "en-CA",
  "generatedAt": "2026-08-27T15:00:00Z",
  "page": {
    "id": "42",
    "slug": "home",
    "title": "Home",
    "status": "published",
    "seo": {
      "title": "Example Site",
      "description": "Example description",
      "canonical": "https://example.com/",
      "robots": {
        "index": true,
        "follow": true
      }
    },
    "theme": {
      "variant": "default",
      "tokens": {
        "primary": "brand-primary",
        "contentWidth": "1200px"
      }
    },
    "blocks": [
      {
        "id": "hero-main",
        "type": "hero",
        "data": {
          "title": "Software Engineer",
          "subtitle": "Building maintainable digital experiences",
          "actions": [
            {
              "id": "view-work",
              "label": "View work",
              "variant": "primary",
              "link": {
                "type": "internal",
                "path": "/projects"
              }
            }
          ]
        },
        "style": {
          "variant": "primary",
          "alignment": "center",
          "properties": {
            "padding": {
              "mobile": "24px",
              "desktop": "64px"
            }
          }
        },
        "animation": {
          "type": "fadeIn",
          "durationMs": 400,
          "trigger": "onLoad"
        }
      },
      {
        "id": "about-section",
        "type": "section",
        "data": {
          "semanticTag": "section",
          "anchorId": "about"
        },
        "children": [
          {
            "id": "about-columns",
            "type": "columns",
            "data": {
              "columns": 2,
              "stackAt": "tablet"
            },
            "children": [
              {
                "id": "about-image",
                "type": "image",
                "data": {
                  "media": {
                    "src": "https://cms.example.com/profile.jpg",
                    "alt": "Profile portrait",
                    "decorative": false,
                    "width": 1200,
                    "height": 1200,
                    "loading": "lazy"
                  }
                }
              },
              {
                "id": "about-copy",
                "type": "richText",
                "data": {
                  "html": "<p>Example content.</p>"
                },
                "children": [
                  {
                    "id": "contact-cta",
                    "type": "cta",
                    "data": {
                      "label": "Contact me",
                      "link": {
                        "type": "internal",
                        "path": "/contact"
                      }
                    }
                  }
                ]
              }
            ]
          }
        ]
      },
      {
        "id": "contact-form",
        "type": "form",
        "data": {
          "formId": "contact",
          "fields": [
            {
              "name": "email",
              "type": "email",
              "label": "Email",
              "validators": [
                {
                  "type": "required",
                  "message": "Email is required."
                },
                {
                  "type": "email",
                  "message": "Enter a valid email address."
                }
              ]
            },
            {
              "name": "message",
              "type": "textarea",
              "label": "Message",
              "validators": [
                {
                  "type": "required"
                },
                {
                  "type": "maxLength",
                  "value": 2000
                }
              ]
            }
          ],
          "submit": {
            "type": "namedEndpoint",
            "endpoint": "contactApi",
            "method": "POST"
          },
          "onSuccess": [
            {
              "type": "showMessage",
              "message": "Thank you for contacting us.",
              "severity": "success"
            },
            {
              "type": "resetForm"
            }
          ],
          "onFailure": [
            {
              "type": "showMessage",
              "message": "Something went wrong. Please try again.",
              "severity": "error"
            }
          ]
        }
      }
    ]
  }
}
```

---

# 82. Walking Skeleton Subset

The first implementation milestone does NOT need to implement the full schema.

The walking skeleton requires the core page contract plus the minimum reusable primitives needed by a realistic Hero:

```ts
PageSchema
PageDefinition
PageBlock
HeroBlockData
HeroMedia
HeroAction
HeroLayout
MediaAsset
LinkModel
BlockStyle
```

This intentionally validates media, navigation intent, semantic layout, styling, accessibility metadata, SSR, and hydration through one realistic vertical slice.

Minimum response:

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

All other V1 types can be added incrementally without changing the core top-level contract.

M1-specific clarifications:

- The frozen M1 payload does not include `generatedAt`, even though the broader V1 contract reserves it as an optional field.
- M1 ignores block-level `children` if present and introduces recursive rendering in M2.
- Hero actions use `actions[]`; fixed action fields such as `primaryAction` are not part of the M1 contract.
- M1 style properties are limited to `minHeight`, `padding`, `backgroundColor`, `color`, `fontFamily`, `fontSize`, and `letterSpacing`.
- M1 Angular validation uses a lightweight internal validator behind the `PageSchemaValidator` abstraction.

---

# 83. Implementation Priority

Recommended schema implementation order:

```text
1. PageSchema
2. PageDefinition
3. PageBlock
4. HeroBlockData
5. Runtime validation
6. Recursive children
7. RichText
8. MediaAsset
9. LinkModel
10. Section / Columns / CTA
11. BlockStyle
12. ThemeSchema
13. NavigationSchema
14. SeoMetadata
15. Slider / Gallery
16. Form schema
17. Visibility
18. Tracking
19. Deferred DataSource types
20. Deferred async validators
```

---

# 84. Definition of Done for PageSchema v1

PageSchema v1 is considered implementation-ready when:

- top-level interfaces are accepted;
- core block types are documented;
- form model is accepted;
- media and links are accepted;
- styling model is accepted;
- SEO model is accepted;
- navigation sibling schema is accepted;
- schema security restrictions are explicit;
- compatibility policy is explicit;
- walking-skeleton fixture exists;
- full-page fixture exists;
- Angular runtime validation strategy is selected;
- WordPress serialization conventions match this document.

---

# 85. Final Contract Principle

The most important rule in the schema is:

> `PageSchema` describes **what the page means and how it should be configured**, not executable implementation details.

WordPress owns normalization.

Angular owns execution.

That boundary is mandatory.
