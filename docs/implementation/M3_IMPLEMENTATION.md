# M3 Implementation — Styling and Theme

The plugin normalizes WordPress spacing, typography, colors, gradients, dimensions, borders, gaps, and alignment into the controlled PageSchema style contract. Preset references become stable WordPress CSS custom-property references for the consumer.

Unsafe style values and unsupported properties are rejected or omitted according to mapper policy. The plugin does not serialize executable JavaScript or unrestricted Angular/CSS implementation details.

Validation completed: Composer quality passes with PHP 8.2.

User acceptance steps are documented in [M2, M3 y M4 — Pruebas de Aceptación de Usuario](../../../docs/M2_M3_M4_USER_ACCEPTANCE_TESTS.md).
