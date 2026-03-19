# Task Backlog

Ordered by priority. Move to `active.md` when starting, `done.md` when complete.

## Phase 1 — Foundation (COMPLETE)
- [x] SCSS-001: Create 7-1 partial folder structure under `stylesheet/src/`
- [x] SCSS-002: Define all design tokens in `abstracts/_variables.scss` (based on `design/tokens.md`)
- [x] SCSS-003: Write `respond-to()` mixin and `flex-center()` mixin in `abstracts/_mixins.scss`
- [x] SCSS-004: Write base resets (`_reset.scss`, `_typography.scss`, `_body.scss`)
- [x] TWIG-001: Refactor `header.twig` — remove any external font calls, clean up markup
- [x] TWIG-002: Inject `theme.min.css` via `header.twig` (after Bootstrap stylesheet)
- [x] TWIG-003: Inject `theme.min.js` via `footer.twig` (before closing `</body>`)

## Phase 2 — Core Components (COMPLETE)
- [x] COMP-001: Product card (`_product-card.scss`) — grid and list view variants
- [x] COMP-002: Button styles (`_buttons.scss`) — primary, secondary, ghost
- [x] COMP-003: Header layout (`_header.scss`) — logo, search, cart bar, sticky behavior
- [x] COMP-004: Navigation bar (`_header.scss`) — Bootstrap navbar override, mobile hamburger
- [x] COMP-005: Footer layout (`_footer.scss`) — 4-column link grid, mobile accordion

## Phase 3 — Page Templates (IN PROGRESS)
- [x] PAGE-001: Homepage (`home.twig` + `_home.scss`) — hero, all 9 sections, mobile pass
- [~] PAGE-002: Category page (`category.twig` + `_category.scss`) — mobile filter done, desktop pending
- [ ] PAGE-003: Product page (`product.twig` + `_product.scss`) — image gallery, add to cart
- [ ] PAGE-004: Cart page (`cart.twig` + `_cart.scss`) — items table, order summary, checkout btn

## Phase 4 — Performance
- [x] PERF-001: Remove external Google Fonts call from header (self-hosted via @fontsource)
- [ ] PERF-002: Audit Bootstrap 3 CSS — identify and comment out unused component blocks
- [ ] PERF-003: Verify OC image cache is configured and working
- [x] PERF-004: Run Lighthouse on category page, record in `performance/log.md`
- [ ] PERF-005: Run Lighthouse on product page, record in `performance/log.md`

## Phase 5 — Polish
- [ ] A11Y-001: Add visible focus styles to all interactive elements (`:focus-visible`)
- [ ] A11Y-002: Verify color contrast ratios meet 4.5:1 minimum (use browser DevTools)
- [ ] A11Y-003: Verify all images have meaningful alt text in admin
- [ ] QA-001: Cross-browser check — Chrome, Firefox, Safari, Edge
- [ ] QA-002: Mobile check at 375px (iPhone SE), 414px (iPhone XR), 768px (iPad)
- [ ] QA-003: Cart flow end-to-end test (add item → cart → checkout page loads)
