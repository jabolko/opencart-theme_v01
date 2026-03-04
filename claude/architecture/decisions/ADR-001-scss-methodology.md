# ADR-001: SCSS Methodology — BEM + 7-1 Pattern

**Date:** 2026-03-03
**Status:** Accepted

## Context
We needed to choose a CSS architecture approach for the theme. Options considered:
1. Utility-first (Tailwind-style classes in Twig)
2. BEM with 7-1 partial structure
3. Flat single-file SCSS

## Decision
BEM naming with 7-1 partial structure.

## Rationale
- Twig templates are controlled by OpenCart's HTML structure — we cannot add utility classes
  to elements rendered by OC widgets (cart, search, menu). BEM lets us style OC's existing
  class names cleanly.
- 7-1 keeps files organized as the theme grows — one file per component is findable.
- Flat single-file does not scale past 200 lines.

## Consequences
- All new components must follow BEM naming (`block__element--modifier`)
- New SCSS partials must be placed in the correct 7-1 folder and imported in `theme.scss`
- Utility classes are permitted in `utilities/` but kept to a minimum
