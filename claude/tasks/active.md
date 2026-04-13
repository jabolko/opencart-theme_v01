# Active Work

## Current Focus
**Reservation system complete — moving to remaining pages + polish**

## Recently Completed (2026-04-03)
- Reservation system: atomic stock, 30-min expiry, checkout heartbeat, persistent cart
- Product labels: REZERVIRANO, PRODANO, V KOŠARICI, Top znamka, Z etiketo, NOVO
- Instant UI: ajaxComplete badge/button swap, getStockStatus endpoint
- Cart timers: server-synced countdown in cart page, dropdown, mobile sheet
- Sold filtered from similar/related/recently viewed
- Product page CTA states (V košarici / Že v košarici drugega kupca / Prodano)
- N+1 fix: similar products batch query (10→1)
- Labels wired to all product controllers (category, latest, search, special, manufacturer)
- Fixed subtract=0→1 on all 6913 products (required for restock on cancel)
- All manual tests pass: end-to-end order, cancel restock, login merge, second-browser
- Lighthouse: Desktop 98-99, Mobile 78-82 (Docker TTFB bottleneck, not theme)

## Recently Completed (2026-03-31)
- Checkout page complete: V1 accordion, merged steps, conversion elements
- Mobile search bottom sheet (V3)

## TODO — Custom Templates (search, special, manufacturer pages)
- [ ] Create `template/product/search.twig` — custom design with labels + card states
- [ ] Create `template/product/special.twig` — same
- [ ] Create `template/product/manufacturer.twig` — same (info page + product grid)
- Note: label data already passed from controllers, just needs templates

## TODO — EU Cookie Compliance
- [ ] When installing EU cookie consent plugin: register `oc_cart_token` as "strictly necessary" (not analytics/marketing). GDPR/ePrivacy exempts functional cookies that the user explicitly requested (shopping cart persistence). If the plugin blocks it, cart recovery breaks but reservation/stock integrity is unaffected.
- [ ] Test after plugin install: add to cart → close browser → reopen WITHOUT accepting cookies → cart should persist

## TODO — Reservation System (deferred to production)
- [ ] Generate OCMOD XML from git diffs (pre-production deploy)
- [ ] Update core-modifications.md with all reservation entries
- [ ] Setup cron: `*/5 * * * * curl clearExpired` on production server
- [ ] Auto-restock on return complete:
  - **Problem:** OC returns never touch stock. For a 5-product order with 1 return, cancelling the order restocks all 5. Manual product edit is error-prone.
  - **Solution:** Register OC event on `admin/model/sale/return/addReturnHistory/after`. When return status changes to "Zaključeno" (status_id=3), auto-run `UPDATE product SET quantity = quantity + return.quantity WHERE product_id = return.product_id AND subtract = '1'`.
  - **Implementation:** Single event handler file + event registration in DB (no core modifications). Can be OCMOD or direct event registration via admin SQL.
  - **DB schema:** `oc_return` has `product_id`, `quantity`, `return_status_id` — all data needed.
  - **Edge case:** Manual returns (`order_id=0`) with `product_id=0` — skip restock if product_id is 0.
  - **Note:** Return actions (Refund/Credit/Replacement) are labels only — zero automation in OC. Financial refund is always manual.
- [ ] Admin email alert on new return submission:
  - **Problem:** OC sends zero notifications when a customer submits a return. No email, no admin panel alert. Admin has to check Sales > Returns manually.
  - **Solution:** Register OC event on `catalog/model/account/return/addReturn/after`. Send email to store admin (`config_email`) with return details (product name, order ID, customer, reason).
  - **Implementation:** Single event handler file + event registration. Same pattern as restock event. Can bundle both return events in one OCMOD.
  - **Note:** `config_mail_alert` only supports `["order"]` — OC has no built-in return alert option.

## TODO — Cart Page (deferred)
- [ ] Replace hardcoded shipping estimate with proper OC shipping cost

## TODO — Checkout Page (deferred polish)
- [ ] Success page: newsletter opt-in + account creation prompt
- [ ] Express checkout (Apple Pay / Google Pay) when payment provider ready

## TODO — Homepage (deferred)
- [ ] Category strip design decision (handoff active/category-strip.md)
- [ ] Latest Products admin wiring (assign to Homepage → content_top)

## TODO — Pre-Production (must do before launch)
- [ ] Reservation failure logging: add `error_log()` in cart.php add() rollback path, remove() empty-row path, and clearExpired when stock is restored. Gives audit trail for production monitoring. One line per failure point.
- [ ] Activate cron in Docker for dev testing: `docker exec opencart_web bash -c "echo '*/2 * * * * curl -s http://localhost/index.php?route=checkout/cart/clearExpired > /dev/null' | crontab -"`. Verify expiry works without page loads.
- [ ] Duplicate address check in guest.php and register flows — currently only in payment_address.php. Guest save and register save can create duplicate addresses on re-submit. Same pattern: check existing addresses before addAddress().
- [ ] OCMOD XML generation from git diffs (reservation: 3 OCMODs, checkout: 1 OCMOD). See `claude/pages/reservation/reservation-system.md` OCMOD checklist.
- [ ] Add shared secret to `clearExpired` endpoint — e.g. `?token=xxx` checked against config
- [ ] Add index: `ALTER TABLE oc_cart ADD INDEX idx_expiry (api_id, date_added), ADD INDEX idx_product_id (product_id);`

## TODO — Reservation Optional Hardening (from audit, not blockers)
- [ ] Add ID cap to `getStockStatus`: `$ids = array_slice($ids, 0, 100);` — prevents abuse
- [ ] Order status toggle guard — admin training: don't toggle Complete→Cancelled→Complete (double-restocks). Optional: add `stock_restored` flag to oc_order to prevent re-restock
- [ ] Option-level stock reservation — extend add()/remove()/expiry to also decrement/restore `product_option_value.quantity`. Only needed if store adds products with size/color options that track per-option stock
- [ ] getProducts() remove() for disabled products — optionally skip stock restore when product status=0

## TODO — Phase 5 Sweep
- [ ] Tap feedback: `:active` scale+tint on all tappable elements
- [ ] SEO: heading hierarchy, schema.org, meta tags, Open Graph across all pages
- [ ] Color audit: replace all hardcoded hex values with design tokens
- [ ] Dead code cleanup: remove unused HTML, CSS classes, commented code
- [ ] A11y: fix category page (78), product page (82) — missing labels, contrast

## Decisions Made
- Always run `npm run build` (not `scss:build`) — browser loads `theme.min.css`
- Cart reservation: server-synced 30min timer (replaced localStorage)
- No quantity selector on cart (all items qty 1, second-hand)
- Coupon code always visible (not collapsible)
- Guest checkout default, login as secondary
- Direct core edits in dev → OCMOD XML for production
- Test script: `bash claude/pages/reservation/test-reservation.sh` (13 assertions)
- subtract=1 required for all products (unique items must track stock)

## Next Up
1. Pre-production hardening (failure logging, cron, duplicate address, OCMOD)
2. Custom templates for search/special/manufacturer (with labels)
3. Success page design
4. Phase 5 sweep (A11y, SEO, tap feedback, color audit)
5. Production deploy (OCMOD install, cron setup, index, monitoring)

## Future (v2)
- [ ] Checkout: rewrite to single `custom.php` controller + Twig macros + partials. Architecturally cleaner (one controller, enforced template contract, easy to add/change steps), but high regression risk against the current 5 tested checkout flows. Do after launch when the current system is stable in production and we have real user data to validate against.

## Session Notes
_Clear this section at the start of each session._
