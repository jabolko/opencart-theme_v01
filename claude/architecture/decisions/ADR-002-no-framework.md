# ADR-002: Bootstrap Override Strategy (Not Replacement)

**Date:** 2026-03-03
**Status:** Accepted

## Context
OpenCart 3.0.5.0 loads Bootstrap 3 CSS and JS unconditionally in all themes.
Options:
1. Remove Bootstrap, load Bootstrap 4/5 instead
2. Keep Bootstrap 3, write all custom styles on top of it
3. Use CSS custom properties to retheme Bootstrap 3 variables

## Decision
Keep Bootstrap 3 loaded by OC. Write custom styles that override and extend it.
Do not load any additional CSS framework.

## Rationale
- OC's admin panel and many modules depend on Bootstrap 3 JS components (modals, dropdowns).
  Swapping the CSS version breaks JS behavior.
- Adding a second framework doubles CSS weight with high specificity conflicts.
- Our design system (`tokens.md`) provides the visual identity; Bootstrap provides the grid
  and base interactive component behavior.

## Consequences
- Our SCSS must load after Bootstrap — handled by OC's style injection order
- We rely on Bootstrap 3's 12-column grid (`col-sm-*`, `col-md-*`, etc.)
- No Bootstrap 4+ utility classes (`d-flex`, etc.) — use our own `utilities/` partial
