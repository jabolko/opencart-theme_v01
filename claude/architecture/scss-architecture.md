# SCSS Architecture

## Methodology: BEM + 7-1 Pattern

Single entry point: `stylesheet/src/theme.scss`
All actual styles live in partials, imported into `theme.scss` in a specific order.

## Target Directory Structure
```
stylesheet/src/
├── theme.scss               ← Entry point only. @use and @forward — no rules here.
│
├── abstracts/               ← No CSS output. Variables, mixins, functions.
│   ├── _variables.scss      ← All design tokens as SCSS variables (see design/tokens.md)
│   ├── _mixins.scss         ← Reusable mixins (respond-to, flex-center, etc.)
│   └── _functions.scss      ← Pure functions (rem(), strip-unit(), etc.)
│
├── base/                    ← Global resets and element defaults
│   ├── _reset.scss          ← Minimal reset on top of Bootstrap's reset
│   ├── _typography.scss     ← h1-h6, p, a, list base styles
│   └── _body.scss           ← body, html, * box-sizing
│
├── layout/                  ← Page-level structural containers
│   ├── _header.scss         ← #top nav + header element
│   ├── _footer.scss         ← footer element
│   └── _grid.scss           ← Custom grid overrides
│
├── components/              ← Reusable UI components (BEM blocks)
│   ├── _buttons.scss        ← .btn overrides and custom button variants
│   ├── _product-card.scss   ← .product-thumb BEM block
│   ├── _breadcrumb.scss     ← .breadcrumb overrides
│   ├── _alerts.scss         ← .alert overrides
│   ├── _forms.scss          ← Input, select, label overrides
│   └── _badge.scss          ← Sale/new badges
│
├── pages/                   ← Page-specific styles (scoped to OC page wrapper ID)
│   ├── _home.scss           ← #common-home
│   ├── _category.scss       ← #product-category
│   ├── _product.scss        ← #product-product
│   └── _cart.scss           ← #checkout-cart
│
└── utilities/               ← Single-purpose helper classes
    └── _helpers.scss        ← .visually-hidden, .text-center overrides, etc.
```

## Import Order in theme.scss
```scss
// 1. Abstracts (must be first — no CSS output)
@use 'abstracts/variables' as *;
@use 'abstracts/mixins' as *;
@use 'abstracts/functions' as *;

// 2. Base
@use 'base/reset';
@use 'base/body';
@use 'base/typography';

// 3. Layout
@use 'layout/grid';
@use 'layout/header';
@use 'layout/footer';

// 4. Components
@use 'components/buttons';
@use 'components/forms';
@use 'components/alerts';
@use 'components/breadcrumb';
@use 'components/badge';
@use 'components/product-card';

// 5. Pages
@use 'pages/home';
@use 'pages/category';
@use 'pages/product';
@use 'pages/cart';

// 6. Utilities
@use 'utilities/helpers';
```

## BEM Naming Convention
```scss
// Block
.product-card { }

// Element (double underscore)
.product-card__image { }
.product-card__title { }
.product-card__price { }

// Modifier (double dash)
.product-card--featured { }
.product-card--out-of-stock { }
```

## Respond-to Mixin
```scss
// Definition (in _mixins.scss)
$breakpoints: (
  'sm':  576px,
  'md':  768px,
  'lg':  992px,
  'xl':  1200px
);

@mixin respond-to($breakpoint) {
  @media (min-width: map-get($breakpoints, $breakpoint)) {
    @content;
  }
}

// Usage (mobile-first)
.product-card {
  flex-direction: column;

  @include respond-to('md') {
    flex-direction: row;
  }
}
```

## Rules
- Never use `#id` selectors for styling — only for JS hooks. Use classes.
- Never nest more than 3 levels deep.
- Never use `!important` — refactor specificity instead.
- All colors, fonts, spacing MUST use token variables from `abstracts/_variables.scss`.
- Bootstrap overrides go in the relevant component or layout partial — not a single overrides file.
- Media queries use the `respond-to()` mixin — never write raw `@media` inline.
- Write mobile-first styles (base = mobile, `respond-to` = desktop enhancements).
