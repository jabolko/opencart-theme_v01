# Active Work

## Current Focus
**Phase 3 — Page Templates**

## In Progress
PAGE-005 — Checkout page (V1 Accordion restyle)
- Spec: `claude/pages/checkout.md` (contract, checklist, debugging guide)

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

## Recently Completed (2026-03-31)
- Checkout page complete: V1 accordion, merged steps, conversion elements
- Merged guest/register/login into one step with pills nav
- Auto-copy billing to shipping address
- Total bar with payment icons, confirm proxy with lock + price
- Completed step summaries, progress bar with checkmarks
- Loading skeleton, auto-scroll to errors, unified alerts
- Form field reorder (CSS Grid + tabindex), phone hint text
- Trust strip: 14 dni vračilo, Google reviews, phone
- All alerts unified (no more browser alert() popups)
- Toast suppressed on checkout
- Focus outlines removed globally

## TODO — Cart Page (deferred)
- [ ] Replace hardcoded shipping estimate with proper OC shipping cost

## TODO — Checkout Page (deferred polish)
- [ ] Success page: newsletter opt-in + account creation prompt
- [ ] Express checkout (Apple Pay / Google Pay) when payment provider ready

## TODO — Reservation System (implement together)
- [ ] Enforce maximum qty in cart.php add() — cap at product.maximum (prevents login merge duplicates)
- [ ] Enforce maximum qty in cart.php getProducts() — clamp on read (safety net)
- [ ] Stock hold on add-to-cart (prevents two users buying same item)
- [ ] Timer expiry (releases held items after 30min)
- [ ] Frontend: disable "Add to cart" if product already in cart
- [ ] Handle edge cases: login merge, register merge, stale cookies, persistent cart
- Spec: see conversation 2026-03-31 for full analysis of all duplicate scenarios

## Decisions Made
- Always run `npm run build` (not `scss:build`) — browser loads `theme.min.css`
- Cart reservation timer: 30min, client-side localStorage (visual urgency only, for now)
- No quantity selector on cart (all items qty 1, second-hand)
- Coupon code always visible (not collapsible)
- Sticky CTA: "Varno zaključi nakup" with lock icon, gold pill
- Guest checkout default, login as secondary
- Newsletter opt-in moved to post-purchase success page (not checkout)
- Maximum qty enforcement deferred until reservation system built

## Next Up
1. Success page design + post-purchase newsletter/account prompt
2. Reservation system (stock hold + timer + duplicate prevention)
3. Homepage deferred: category strip, Latest Products admin wiring, A11y
4. Phase 5 sweep: tap feedback, SEO, color audit, dead code

## Session Notes
_Clear this section at the start of each session. Add working notes here during the session._
