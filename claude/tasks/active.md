# Active Work

## Current Focus
**Phase 3 — Page Templates**

## In Progress
PAGE-004 — Cart page (mobile build in progress)

## Recently Completed (2026-03-25)
- Cart page mobile: horizontal product cards, reservation timer (30min), free shipping bar
- Sticky checkout footer with lock icon + "Varno zaključi nakup"
- Coupon code input (always visible, charcoal submit button)
- Order summary with estimated shipping from OC flat rate config
- Trust strip (3 icons), continue shopping link
- Cart controller: added manufacturer + product_id to product data

## Recently Completed (2026-03-18)
- Category page: desktop + mobile complete (filter system, sidebar, auto-apply)
- Product page: desktop + mobile complete (gallery, eco/trust tabs, similar products, toast V3)
- Cart bottom sheet + toast alerts

## Recently Completed (2026-03-13)
- Homepage mobile pass complete (all sections S1–S9)
- Footer mobile accordion
- Lighthouse homepage: Mobile 83/90/100/100, Desktop 99/94/100/100

## TODO — Cart Page
- [ ] Replace hardcoded shipping estimate with proper OC shipping cost (use OC shipping extension/language)
- [ ] Cart page desktop layout (2-column: products left, sticky summary right)
- [ ] Empty cart state (illustration + CTA)
- [ ] Cart page Lighthouse audit

## Decisions Made
- Always run `npm run build` (not `scss:build`) — browser loads `theme.min.css`
- Cart reservation timer: 30min, client-side localStorage (visual urgency only)
- No quantity selector on cart (all items qty 1, second-hand)
- Coupon code always visible (not collapsible)
- Sticky CTA: "Varno zaključi nakup" with lock icon, gold pill

## Next Up
1. PAGE-004 — Cart page polish (desktop layout, empty state, OC shipping integration)
2. PAGE-005 — Checkout page
3. Homepage deferred: category strip design decision, Latest Products admin wiring,
   A11y violations, mobile Perf 83→90

## Session Notes
_Clear this section at the start of each session. Add working notes here during the session._
