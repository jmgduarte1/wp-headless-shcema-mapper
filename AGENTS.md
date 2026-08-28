# AGENTS.md

## Purpose

This file defines working instructions for AI coding agents operating on the `headless-angular-schema` repository.

Agents must treat the existing project documentation, accepted architectural decisions, shared PageSchema contract, tests, and working implementation as the source of truth.

The goal is to use AI to accelerate development while preserving contract compatibility, WordPress conventions, code quality, maintainability, security, accessibility metadata, testability, and developer ownership.

---

## 1. Read Project Context Before Significant Work

Before significant implementation, refactoring, architectural, contract, or planning work, review the relevant documentation.

### Repository Requirements

```text
docs/WORDPRESS_PLUGIN_REQUIREMENTS.md
```

### Shared Contract

```text
docs/contract/SHARED_CONTRACT_REQUIREMENTS.md
docs/contract/PAGE_SCHEMA_SPECIFICATION.md
docs/contract/PAGE_SCHEMA_M1_CONTRACT_DEFINITIONS.md
```

### Decisions

If present:

```text
docs/decisions/PROJECT_DECISIONS.md
```

### Implementation Status

If present:

```text
docs/implementation/M1_IMPLEMENTATION.md
docs/implementation/IMPLEMENTATION_STATUS.md
```

Do not begin architectural work from assumptions when the relevant decision or contract is already documented.

---

## 2. Source-of-Truth Priority

When information conflicts, use this priority:

1. Explicit current developer instruction.
2. Frozen shared contract documentation.
3. Existing working implementation and tests.
4. Accepted project decisions.
5. WordPress project requirements.
6. Product architecture documentation.
7. Temporary/local development notes.
8. Agent inference.

If implementation and documentation conflict, identify the discrepancy rather than silently choosing one.

Do not modify a frozen contract merely to make an implementation easier.

---

## 3. Respect the Repository Boundary

This repository owns the WordPress/PHP side of the system.

It is responsible for:

```text
Gutenberg integration
content extraction
normalization
typed PHP DTOs
block mappers
mapper registry
PageSchema construction
serialization
WordPress REST endpoints
WordPress-side validation and sanitization
publication/security boundaries
WordPress-side extension APIs
```

It does NOT own:

```text
Angular components
Angular Material
Angular Router execution
Angular SSR/hydration implementation
browser state
consumer application behavior
consumer-specific UI decisions
```

Do not introduce Angular implementation details into PHP domain models or WordPress block data.

---

## 4. Treat PageSchema as an External Contract

The JSON schema is an integration boundary between independent projects.

WordPress must emit normalized application-neutral concepts.

Avoid exposing Gutenberg internals such as:

```text
blockName
attrs
innerBlocks
core/*
```

as the external consumer contract.

Instead:

```text
Gutenberg block
    ↓
mapper
    ↓
normalized PageBlock
    ↓
PageSchema
```

Mappers convert CMS-specific representation into contract representation.

The REST controller must not perform Gutenberg-to-contract mapping directly.

---

## 5. Inspect Existing Code Before Changing It

Before implementing a solution:

1. Locate the relevant existing implementation.
2. Understand current namespace, DTO, mapper, serializer, controller, and test patterns.
3. Search for an existing abstraction solving the same concern.
4. Review affected contract types.
5. Review relevant tests.
6. Prefer extending established patterns over introducing parallel ones.

Do not create abstractions simply because they are possible.

Do not replace established patterns without a clear technical reason.

---

## 6. PHP Development Principles

Minimum supported PHP:

```text
PHP 8.2
```

Use modern PHP deliberately.

Preferred practices:

- `declare(strict_types=1);`
- explicit parameter and return types;
- `readonly` DTOs where mutation is unnecessary;
- constructor property promotion where it improves clarity;
- enums for closed semantic sets;
- interfaces at meaningful boundaries;
- small focused classes;
- dependency injection instead of hidden global state where practical;
- PSR-4-compatible namespaces;
- clear exceptions/errors instead of silent failure.

Avoid:

- untyped arrays when a domain DTO is appropriate;
- broad `mixed` usage without a boundary reason;
- static global service access where dependency injection is reasonable;
- hidden mutation;
- duplicated normalization logic;
- business/contract logic in REST controllers;
- serialization logic spread across domain classes.

---

## 7. DTO and Domain Modeling

Keep contract concepts strongly typed until the serialization boundary.

Prefer:

```php
new HeroBlockData(...)
```

over:

```php
[
    'title' => ...,
    'subtitle' => ...,
]
```

when representing domain data internally.

Associative arrays are appropriate at boundaries such as:

```text
raw Gutenberg input
WordPress APIs
final serialization output
```

They should not become the default internal model.

DTOs describe normalized contract data.

They do not parse Gutenberg structures and do not perform REST operations.

---

## 8. Mapper Principles

Every mapper should have one responsibility:

```text
CMS-specific input
    ↓
validation
    ↓
normalization
    ↓
typed PageBlock
```

A mapper:

- may understand Gutenberg;
- may validate block-specific input;
- may normalize values;
- may construct typed DTOs;
- must not return `WP_REST_Response`;
- must not serialize JSON;
- must not contain Angular behavior.

Use the mapper registry for extensibility.

Do not add large switch statements across controllers/builders when the registry is the established extension point.

---

## 9. Serialization Principles

Serialization is a dedicated boundary.

Expected flow:

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

The serializer owns:

- JSON field naming;
- enum transport values;
- omission of absent optional values;
- schema version emission;
- conversion of typed DTOs to transport representation.

Do not implement `json_encode()` in domain DTOs.

Do not let mapper-specific array formats leak into REST output.

---

## 10. WordPress and Gutenberg Practices

Follow WordPress APIs rather than bypassing them.

For Gutenberg blocks:

- use `block.json`;
- use official registration APIs;
- validate/sanitize block attributes;
- keep editor configuration declarative;
- keep normalized API output independent from editor storage shape.

The editor should prevent invalid configuration where practical.

Do not rely solely on UI validation; server-side normalization must also validate input.

---

## 11. Security Is a Requirement

Never expose:

```text
credentials
API secrets
authorization headers
server filesystem paths
private tokens
draft/private content through public endpoints
arbitrary JavaScript
event-handler source
unrestricted CSS
```

Validate:

- URLs and allowed protocols;
- media metadata;
- CTA link types;
- enum-like values;
- style property names;
- style values;
- locale/request inputs;
- publication state.

Public page endpoints should return `404` for unavailable/unpublished content when required by the architecture.

Do not expose internal exception traces in public REST responses.

Do not log secrets or sensitive payloads.

---

## 12. Accessibility Metadata Is a Requirement

Although this repository does not render final HTML, it must produce enough metadata for accessible rendering.

For media:

- meaningful images require meaningful `alt`;
- decorative images must be explicitly marked decorative;
- invalid ambiguous media states should be rejected where practical.

For actions:

- expose accessible labels when the visible label alone is insufficient.

Accessibility-related contract regressions are defects.

---

## 13. Contract Compatibility

Current M1 contract:

```text
PageSchema 1.0
```

Do not change a frozen field name, required field, semantic meaning, or enum value without an explicit accepted architectural decision.

Backward-compatible evolution within V1.x may add optional capabilities.

Breaking changes require deliberate schema-version planning.

Plugin version and PageSchema version are independent.

---


---

## Composer and Dependency Policy

Composer is the standard dependency and PSR-4 autoloading mechanism for this repository.

Before dependency-related work, review:

```text
docs/COMPOSER_DEPENDENCIES.md
```

Accepted M1 development tooling includes:

```text
wp-coding-standards/wpcs
szepeviktor/phpstan-wordpress
phpunit/phpunit
wp-phpunit/wp-phpunit
ergebnis/composer-normalize
```

M1 intentionally has no third-party runtime dependency beyond PHP and WordPress.

Do not introduce full Laravel or Symfony Framework dependencies.

A focused Composer runtime package or individual Symfony component may be proposed when it solves a concrete problem substantially better than existing PHP/WordPress capabilities.

Before adding a runtime dependency:

1. identify the concrete problem;
2. check existing PHP/WordPress capabilities;
3. verify PHP 8.2 support;
4. consider WordPress/plugin dependency-conflict risk;
5. evaluate maintenance/security status;
6. prefer small focused packages;
7. document an accepted material dependency decision.

Do not add a package merely to avoid writing a small amount of straightforward domain code.


## 14. Testing Requirements

A task is not complete merely because PHP parses.

Depending on the affected area, run relevant:

```text
unit tests
mapper tests
serializer tests
REST endpoint tests
security/sanitization tests
contract fixture tests
```

For contract changes, test both:

```text
valid fixtures
invalid fixtures
```

Every bug fix should include a regression test when practical.

Tests should verify behavior, not implementation trivia.

---

## 15. Implementation Workflow

### Before Implementation

1. Read relevant requirements and contract documentation.
2. Inspect current code and tests.
3. Identify affected layers.
4. Check project decisions.
5. Identify contract/security risks.
6. Confirm whether the task is M1 or deferred scope.

### During Implementation

1. Make the smallest coherent change satisfying the requirement.
2. Preserve established boundaries.
3. Keep domain, mapping, serialization, and REST responsibilities separate.
4. Avoid unrelated refactors.
5. Add/update tests with the implementation.
6. Do not expand the frozen M1 contract without an explicit reason.

### After Implementation

1. Run affected automated tests.
2. Run lint/static-analysis tools when configured.
3. Verify REST output against contract fixtures.
4. Check security implications.
5. Check whether documentation is now inaccurate.
6. Record meaningful implementation progress.

---

## 16. Documentation and Change Recording

Code changes must leave the documentation consistent with reality.

Use the appropriate document for the type of change.

### A. Implementation Progress

Update:

```text
docs/implementation/M1_IMPLEMENTATION.md
```

or the current milestone implementation file when:

- completing a planned task;
- implementing a contract type;
- adding a mapper;
- adding a serializer;
- exposing an endpoint;
- completing a validation/test milestone;
- discovering a blocker affecting the milestone.

Recommended entry format:

```markdown
## YYYY-MM-DD — Short change title

### Completed
- ...

### Validation
- Tests:
- Manual verification:

### Notes
- ...

### Next
- ...
```

Keep this concise and factual.

Do not turn progress logs into architecture documents.

### B. Architectural Decisions

Update:

```text
docs/decisions/PROJECT_DECISIONS.md
```

when a change materially affects:

- architecture;
- data contracts;
- extension strategy;
- technology selection;
- security model;
- compatibility policy;
- REST behavior;
- project-wide coding conventions.

Recommended decision format:

```markdown
## DEC-XXX — Decision title

**Date:** YYYY-MM-DD  
**Status:** Accepted

### Context
...

### Decision
...

### Consequences
...

### Alternatives Considered
...
```

If a decision is replaced, preserve it and mark it `Superseded`.

### C. Requirements / Contract Documentation

Update requirements or contract documents only when the accepted requirement itself changes.

Do not modify contract documents merely to describe implementation progress.

### D. CHANGELOG

If a repository changelog exists, add externally meaningful changes there.

Do not use the changelog for internal task-by-task notes.

---

## 17. Document Code Changes Correctly

When implementation changes behavior:

- update tests;
- update affected API/contract examples;
- update comments only when they explain non-obvious intent;
- update developer documentation if setup/commands changed;
- update decisions when architecture changed;
- update implementation status when milestone progress changed.

Do not add comments that merely restate code.

Do not leave TODO comments without clear ownership/context.

Prefer a documented backlog item over vague permanent TODOs.

---

## 18. Do Not Invent Requirements

If behavior is not documented and cannot be reliably inferred:

- do not invent a business or contract requirement;
- identify the ambiguity;
- preserve existing behavior where possible;
- make assumptions explicit;
- prefer the solution most consistent with the frozen contract and accepted architecture.

When a requested change conflicts with a frozen contract:

1. identify the conflict;
2. explain the impact;
3. propose the smallest resolution;
4. wait for an explicit contract decision before silently changing it.

---

## 19. Avoid Unnecessary Complexity

Prefer the simplest solution that satisfies current requirements and preserves extensibility already required by the architecture.

Avoid:

- premature frameworks;
- unnecessary dependencies;
- duplicate abstractions;
- large base-class hierarchies;
- generic systems with no current use;
- large refactors for small tasks;
- speculative support for future blocks during M1.

M1 is a walking skeleton, not the full product.

Implement the extension points already required, but do not implement deferred features early without a clear reason.

---

## 20. AI-Generated Code Is a Draft

AI-generated code, architecture, documentation, and tests are proposed engineering work.

The developer remains responsible for:

- correctness;
- contract compatibility;
- architecture;
- maintainability;
- security;
- accessibility metadata;
- testing;
- performance;
- final technical decisions.

Do not assume generated code is correct because it passes syntax checks.

Inspect and validate it.

---

## 21. Working Principle

Understand the current system first.

Preserve the PageSchema boundary.

Keep WordPress concerns on the WordPress side.

Make deliberate, testable changes.

Document decisions and progress in the correct place.

Leave the repository easier to understand than you found it.
