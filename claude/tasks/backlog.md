# Task Backlog

Ordered by priority. Move to `active.md` when starting, `done.md` when complete.

## Phase 1 — Foundation
- [ ] SCSS-001: Create 7-1 partial folder structure under `stylesheet/src/`
- [ ] SCSS-002: Define all design tokens in `abstracts/_variables.scss` (based on `design/tokens.md`)
- [ ] SCSS-003: Write `respond-to()` mixin and `flex-center()` mixin in `abstracts/_mixins.scss`
- [ ] SCSS-004: Write base resets (`_reset.scss`, `_typography.scss`, `_body.scss`)
- [ ] TWIG-001: Refactor `header.twig` — remove any external font calls, clean up markup
- [ ] TWIG-002: Inject `theme.min.css` via `header.twig` (after Bootstrap stylesheet)
- [ ] TWIG-003: Inject `theme.min.js` via `footer.twig` (before closing `</body>`)

## Phase 2 — Core Components
- [ ] COMP-001: Product card (`_product-card.scss`) — grid and list view variants
- [ ] COMP-002: Button styles (`_buttons.scss`) — primary, secondary, ghost
- [ ] COMP-003: Header layout (`_header.scss`) — logo, search, cart bar, sticky behavior
- [ ] COMP-004: Navigation bar (`_header.scss`) — Bootstrap navbar override, mobile hamburger
- [ ] COMP-005: Footer layout (`_footer.scss`) — 4-column link grid, mobile accordion

## Phase 3 — Page Templates
- [ ] PAGE-001: Homepage (`home.twig` + `_home.scss`) — hero section, featured products grid
- [ ] PAGE-002: Category page (`category.twig` + `_category.scss`) — product grid, sort bar, sub-cats
- [ ] PAGE-003: Product page (`product.twig` + `_product.scss`) — image gallery, add to cart
- [ ] PAGE-004: Cart page (`cart.twig` + `_cart.scss`) — items table, order summary, checkout btn

## Phase 4 — Performance
- [ ] PERF-001: Remove external Google Fonts call from header if present
- [ ] PERF-002: Audit Bootstrap 3 CSS — identify and comment out unused component blocks
- [ ] PERF-003: Verify OC image cache is configured and working
- [ ] PERF-004: Run Lighthouse on category page, record in `performance/log.md`
- [ ] PERF-005: Run Lighthouse on product page, record in `performance/log.md`

## Phase 5 — Polish
- [ ] A11Y-001: Add visible focus styles to all interactive elements (`:focus-visible`)
- [ ] A11Y-002: Verify color contrast ratios meet 4.5:1 minimum (use browser DevTools)
- [ ] A11Y-003: Verify all images have meaningful alt text in admin
- [ ] QA-001: Cross-browser check — Chrome, Firefox, Safari, Edge
- [ ] QA-002: Mobile check at 375px (iPhone SE), 414px (iPhone XR), 768px (iPad)
- [ ] QA-003: Cart flow end-to-end test (add item → cart → checkout page loads)
