# M2 Implementation — Core Composition and Actions

The plugin recursively maps editable native Gutenberg groups, columns, text, images, details, links, and buttons into PageSchema 1.0. `core/button` is normalized as a safe `link` block with `layout: "button"`.

Rich text is exposed as sanitized inline HTML in `BasicBlockData::html` with plain text preserved. Child order, widths, alignment, and supported styles remain part of the normalized tree.

Validation completed: Composer quality passes with PHP 8.2, including PHPUnit, PHPCS, PHPStan, and Composer normalization.

User acceptance steps are documented in [M2, M3 y M4 — Pruebas de Aceptación de Usuario](../../../docs/M2_M3_M4_USER_ACCEPTANCE_TESTS.md).
