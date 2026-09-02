# M4 Implementation — Media, Links and Navigation

The plugin exposes reusable media metadata including `srcSet`, loading mode, MIME type, and caption when available. It also exposes the independent navigation endpoint:

```text
GET /wp-json/headless-renderer/v1/menus/{location}
```

Menu items are normalized recursively and support internal, external, anchor, email, and telephone links. Missing menu locations return 404 without exposing internal errors.

Validation completed: Composer quality passes with PHP 8.2. The controller supports classic menus and block-based `wp_navigation` menus, including `core/page-list` fallback for the `primary` location. The live `primary` endpoint returns HTTP `200` with normalized items; missing locations return HTTP `404`.

User acceptance steps are documented in [M2, M3 y M4 — Pruebas de Aceptación de Usuario](../../../docs/M2_M3_M4_USER_ACCEPTANCE_TESTS.md).
