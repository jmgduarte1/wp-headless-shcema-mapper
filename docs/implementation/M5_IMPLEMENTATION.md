# M5 Implementation: SEO and Localization

The plugin now normalizes WordPress core page metadata in the PageSchema response and includes the requested locale in page and navigation responses.

`page.seo` contains the page title, excerpt-derived description, canonical URL, robots directives, Open Graph metadata, and Twitter card metadata. Featured image data is included when the page has a featured image. The endpoint accepts `?locale=...`; provider-specific translation integrations such as WPML and Polylang remain deferred.

The navigation endpoint also accepts `?locale=...` and returns the locale alongside `schemaVersion`, `location`, and `items`, allowing consumers to keep locale-aware cache entries distinct.

Verification: PHP 8.2.33, PHPUnit 14 tests / 102 assertions, PHPStan, and PHPCS passed.
