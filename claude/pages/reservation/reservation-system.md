# Reservation System — Complete Implementation Plan

> Last updated: 2026-04-08 (code samples reflect final audited code)

## Overview

Server-side product reservation for unique (qty=1) second-hand children's clothing.
When a customer adds a product to cart, stock is **atomically decremented** for **30 minutes**.
No other customer can add the same product. If checkout is not completed within 30 min, stock restores automatically. During active checkout a heartbeat extends the timer indefinitely.

---

## Architecture Decision

**Modify `product.quantity` directly. Use `oc_cart` as the reservation ledger. No extra tables.**

| Principle | How it's achieved |
|-----------|-------------------|
| Atomic reservation | `UPDATE product SET quantity = quantity - 1 WHERE quantity >= 1` — MySQL row lock |
| Single source of truth | `product.quantity` is always correct — every OC view reads it unchanged |
| No extra tables | `oc_cart` row = reservation. 2 new columns (`visitor_ip`, `cart_token`) for persistent recovery |
| Expiry cleanup | 2-query transaction: JOIN UPDATE (restore stock) + DELETE (remove rows) |
| Order completion | New `clearCart()` deletes cart rows WITHOUT restoring stock (reservation → sale) |
| Order cancellation | OC's built-in restock logic (`quantity + N`) is untouched — works correctly |
| Cache safety | `$this->cache->delete('product')` after reserve/release clears Latest/Popular/Bestseller cache |

### Why not an extra `oc_reserved_stock` table?

- Fewer tables = fewer queries = fewer things to break
- `oc_cart` already tracks who has what — it IS the reservation ledger
- No N+1 loops for expiry (JOIN UPDATE handles all rows at once)
- Every OC query that checks `product.quantity` works automatically

---

## Database Changes

### ALTER existing table (one-time, auto-detected)

```sql
ALTER TABLE `oc_cart`
  ADD COLUMN IF NOT EXISTS `visitor_ip` VARCHAR(45) NOT NULL DEFAULT '' AFTER `date_added`,
  ADD COLUMN IF NOT EXISTS `cart_token` VARCHAR(64) NOT NULL DEFAULT '' AFTER `visitor_ip`;
```

**No new tables. No new indexes needed.** The `date_added` column is already used in OC's expiry query.
Column existence is checked once per session (cached in `$_SESSION`) to avoid repeated `SHOW COLUMNS`.

---

## OpenCart Caching Impact

Research confirmed these caching layers exist in OC 3.0.5.0:

| Cache layer | Caches stock? | Impact on reservation |
|-------------|---------------|----------------------|
| `getProduct($id)` | NO — direct DB query | None — always fresh |
| `getProducts($filters)` | NO — direct DB query | None — category/search always fresh |
| `getLatestProducts()` | YES — `product.latest.*` (1hr TTL) | **Must invalidate** after reserve/release |
| `getPopularProducts()` | YES — `product.popular.*` (1hr TTL) | Same |
| `getBestSellerProducts()` | YES — `product.bestseller.*` (1hr TTL) | Same |
| `cart/cart.php` | NO — always queries DB | None |
| Twig template cache | Bytecode only, not data | None |
| OCMOD cache | Code only — manual refresh after install | One-time setup step |

**Fix:** Add `$this->cache->delete('product')` after every stock modification (reserve, release, expiry).
This is the same call OC admin uses. Clears all `product.*` cache keys instantly.

---

## Deliverables

### 2 OCMOD XML files (installed via admin):
1. `reservation-timer.ocmod.xml` — core reservation logic
2. `reservation-checkout-extend.ocmod.xml` — checkout heartbeat

### Theme files (normal development, not OCMOD):
- `cart.twig` — `data-time-added` attribute, server time variable
- `common/cart.twig` — same for header mini-cart
- `theme.js` — replace localStorage timer with server-synced timer
- `_cart.scss` — timer badge (already exists, minor update)
- Product card templates — REZERVIRAN / PRODANO badge
- `catalog/model/catalog/product.php` — batch reservation status query (tracked in core-modifications.md)

---

## OCMOD 1: reservation-timer.ocmod.xml

**Replaces the entire `system/library/cart/cart.php`.** The changes are too extensive for search/replace OCMOD operations — use a full file replacement approach or carefully diff against OC 3.0.5.0 original.

The final audited code is the source of truth. Read it directly:
```
opencart/system/library/cart/cart.php
```

### Key changes vs OC 3.0.5.0 original:

| Method | Change |
|--------|--------|
| **Properties** | Added `private $cache;` + `$this->cache = $registry->get('cache');` in constructor |
| **Constructor: schema init** | `SHOW COLUMNS` + `ALTER TABLE` for `visitor_ip` + `cart_token` columns (once per session) |
| **Constructor: recovery** | Cookie-based recovery with `date_added = NOW()` refresh. IP fallback removed. |
| **Constructor: expiry** | `SELECT ... FOR UPDATE` → per-row stock restore → `DELETE` → `COMMIT`. Cache invalidated only when rows found. |
| **Constructor: login merge** | `date_added = NOW()` on guest rows before merge. Dedup check: if customer already has product, delete guest row (countAffected guard) + restore stock. Otherwise claim row. |
| **add()** | Transaction wraps entire method. `SELECT COUNT ... FOR UPDATE` prevents duplicate race. Atomic `UPDATE product WHERE qty >= N`. Session-scoped `cart_token` (reused across adds). Three session flags: `reservation_failed`, `reservation_sold`, `reservation_already_in_cart`. Cookie Secure flag based on HTTPS. |
| **update()** | Hardcaps quantity to 1. Delegates to remove() if qty <= 0. |
| **remove()** | Transactional: `SELECT ... FOR UPDATE` → stock restore → `DELETE` → `COMMIT`. Cache invalidated. |
| **clearCart()** | NEW method. Transactional with `FOR UPDATE`. Deletes without restoring stock (successful order). |
| **clear()** | Transactional: `SELECT ... FOR UPDATE` → JOIN UPDATE stock restore → `DELETE` → `COMMIT`. Cache invalidated. |
| **getProducts()** | `$stock = true` override (reserved items always in-stock for this user). `date_added` passed through in product array. |
| **getVisitorIp()** | NEW private method. `X-Forwarded-For` → `HTTP_CLIENT_IP` → `REMOTE_ADDR` chain. |

### Other core files (OCMOD search/replace):

**`catalog/controller/checkout/cart.php`** — 3 new methods + reservation catch:
- `currentTime()` — JSON server time
- `clearExpired()` — cron endpoint with FOR UPDATE pattern
- `getStockStatus()` — batch product status (reserved/sold/in_cart/available)
- In `add()`: catches `reservation_failed`, `reservation_sold`, `reservation_already_in_cart` session flags

**`catalog/controller/checkout/success.php`** — `clearCart()` instead of `clear()`

**`catalog/controller/api/order.php`** — `clearCart()` + per-product cart cleanup with `LIMIT 1`

**`catalog/model/checkout/order.php`** — Stock subtraction + option subtraction commented out (lines 324-331). Restock on cancel unchanged.

**Language files** — 4 new strings: `error_reserved`, `error_already_in_cart`, `error_sold`, `text_reservation_timer`

---

## OCMOD 2: reservation-checkout-extend.ocmod.xml

**`catalog/controller/checkout/checkout.php`** — `updateCartTime()` method:
```php
public function updateCartTime() {
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
        $this->db->query("UPDATE " . DB_PREFIX . "cart SET date_added = NOW() WHERE session_id = '" . $this->db->escape($this->session->getId()) . "' AND api_id = '0'");
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array('success' => true, 'server_time' => date('Y-m-d H:i:s'))));
    }
}
```

Heartbeat JS added directly to theme's `checkout.twig` (not OCMOD).

---

## Theme Changes (not OCMOD — normal theme development)

### 1. Server-synced timer (replaces localStorage timer)

**Current state:** `theme.js` uses `localStorage.getItem('cart_reserve_ts')` — a client-side 30-minute timer that starts when the cart page first loads. Not synced with server. Can be manipulated. Resets on browser change.

**New approach:**

1. Cart controller passes `server_time` to template: `$data['server_time'] = date('Y-m-d H:i:s');`
2. Each cart item has `data-time-added="{{ product.date_added }}"` attribute
3. JS calculates: `remaining = 1800 - (serverTime - dateAdded)` per item
4. Timer ticks locally (1s interval) — no per-second AJAX
5. On expiry (remaining ≤ 0): show expired state, prompt page reload
6. One-time server time fetch on page load (from `data-server-time` attribute) — eliminates clock skew

**`cart.twig` changes:**
```twig
{# On the page container: #}
<div class="cart-page" data-server-time="{{ server_time }}">

{# On each cart item: #}
<div class="cart-item" data-cart-id="{{ product.cart_id }}" data-time-added="{{ product.date_added }}">
```

**`theme.js` — replace `initCartPage()` timer section:**
```javascript
// Server-synced reservation timer
var pageEl = document.querySelector('.cart-page');
var serverNow = pageEl ? new Date(pageEl.getAttribute('data-server-time').replace(' ', 'T') + 'Z').getTime() : Date.now();
var clientNow = Date.now();
var clockOffset = serverNow - clientNow; // server - client (add to Date.now() for server time)

var items = document.querySelectorAll('.cart-item[data-time-added]');
var globalTimerEl = document.getElementById('js-cart-timer');
var reserveBanner = document.getElementById('js-cart-reserve');

function tickTimers() {
    var now = Date.now() + clockOffset; // approximate server time
    var minRemaining = Infinity;

    for (var i = 0; i < items.length; i++) {
        var added = new Date(items[i].getAttribute('data-time-added').replace(' ', 'T') + 'Z').getTime();
        var remaining = 1800000 - (now - added); // 30 min in ms
        if (remaining < minRemaining) minRemaining = remaining;

        var timerEl = items[i].querySelector('.cart-item__timer-val');
        if (timerEl) timerEl.textContent = formatTime(remaining);
    }

    if (globalTimerEl) globalTimerEl.textContent = formatTime(minRemaining);

    if (minRemaining <= 0) {
        if (reserveBanner) reserveBanner.classList.add('cart-reserve--expired');
        return; // stop ticking
    }
    if (minRemaining < 300000) { // < 5 min
        if (reserveBanner) reserveBanner.classList.add('cart-reserve--urgent');
    }

    setTimeout(tickTimers, 1000);
}

if (globalTimerEl && items.length) tickTimers();
```

### 2. REZERVIRAN / PRODANO badges on product cards

**In `catalog/model/catalog/product.php` (core modification, tracked):**

After fetching products in `getProducts()`, run a batch query:

```php
// Batch check: which of these products have active reservations?
if (!empty($product_ids)) {
    $reserved_query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "cart WHERE product_id IN (" . implode(',', array_map('intval', $product_ids)) . ") AND date_added > DATE_SUB(NOW(), INTERVAL 30 MINUTE) GROUP BY product_id");

    $reserved_ids = array();
    foreach ($reserved_query->rows as $row) {
        $reserved_ids[] = (int)$row['product_id'];
    }
}
```

Then in the product array, add:

```php
'reservation_status' => ($product['quantity'] > 0) ? 'available' : (in_array((int)$product['product_id'], $reserved_ids) ? 'reserved' : 'sold'),
```

**Template badges (product-card partial):**
```twig
{% if product.reservation_status == 'reserved' %}
  <span class="product-card__badge product-card__badge--reserved">REZERVIRAN</span>
{% elseif product.reservation_status == 'sold' %}
  <span class="product-card__badge product-card__badge--sold">PRODANO</span>
{% endif %}
```

### 3. Product page — disable add-to-cart for reserved/sold

On the product page (`product.twig`), when `quantity == 0`:
- If product has active reservation → show "REZERVIRAN" badge + disabled button
- If no reservation → show "PRODANO" badge + disabled button

This requires passing reservation status from the product controller, using the same cart check query.

### 4. Instant UI update after add-to-cart

When a user adds to cart successfully, other product cards showing the same product should update instantly (without page reload). Strategy:

- Use `$(document).ajaxComplete()` hook to detect successful cart additions
- On success, find matching `[data-product-id="X"]` cards and toggle badge to REZERVIRAN
- No extra AJAX needed — the adding user already knows the product is now reserved
- Other users will see updated `quantity=0` on next page load (always-fresh DB query)

### 5. Mini-cart timer (`common/cart.twig`)

The header mini-cart dropdown also shows cart items. Add per-item timer here too:
- Pass `date_added` through to the mini-cart template
- Add timer badge next to each item
- Share the same `clockOffset` / `tickTimers` logic from theme.js

---

## Race Condition Analysis

| Scenario | What happens | Safe? |
|----------|-------------|-------|
| **Two users add same product simultaneously** | Both hit `UPDATE WHERE qty >= 1`. MySQL row lock: first gets affected=1, commits. Second gets affected=0, rolls back. | Yes — atomic |
| **Server crash mid-add** | Transaction not committed → InnoDB auto-rollback. Stock unchanged, no cart row. | Yes |
| **PHP fatal mid-add** | Connection close = implicit rollback. | Yes |
| **Expiry runs during active checkout** | Heartbeat resets `date_added = NOW()` every 30s. Expiry checks `< 30 MIN`. Active checkout never matches. | Yes |
| **Heartbeat fails (network down)** | After 30 min without heartbeat, item expires. Correct — customer is gone. | Yes |
| **Login merge** | `UPDATE customer_id` on existing cart row. No delete+re-add. No double-decrement. | Yes |
| **Persistent cart recovery + expired** | Expiry already deleted the cart row and restored stock. Cookie/IP finds nothing. Clean. | Yes |
| **Two tabs, same product** | Second `add()` finds product already in cart (COUNT > 0). Enters else branch (no-op). | Yes |
| **Order cancelled** | OC's restock logic (`qty + N WHERE subtract=1`) runs unchanged. Stock restored. | Yes |
| **Expiry double-restore** | Wrapped in transaction. Crash between UPDATE+DELETE = rollback = both undone. | Yes |
| **Admin changes stock while reserved** | Works. If admin sets qty=1 on a reserved (qty=0) product, it creates a second unit. Admin should be aware. | Yes (documented) |
| **Product disabled while in cart** | `getProducts()` checks `status = 1`. Disabled product drops from cart view. Remove triggers stock restore. | Yes |
| **clearExpired cron + constructor race** | Both run `UPDATE + DELETE` inside transaction. If cron handles it, constructor finds 0 rows. If constructor handles it, cron finds 0 rows. No conflict. | Yes |

---

## Performance Impact

| Event | Extra queries | Time | Frequency |
|-------|--------------|------|-----------|
| Page load (constructor) | 3 (START + UPDATE + DELETE + COMMIT) | ~1ms | Every request |
| Session init (schema check) | 1 | ~0.3ms | Once per session |
| Persistent cart check | 1-2 | ~0.4ms | Only guests, once per session |
| Add to cart | 3 (START + UPDATE + INSERT + COMMIT) | ~0.5ms | On add |
| Remove from cart | 1 extra (SELECT before DELETE) | ~0.2ms | On remove |
| Cart page render | 0 | 0ms | `date_added` already in query |
| Checkout heartbeat | 1 | ~0.3ms | Every 30 seconds |
| Category page (badges) | 1 batch SELECT | ~0.5ms | On category load |
| Order success | 0 | 0ms | `clearCart()` = simple DELETE |
| Cache invalidation | ~0.1ms (file delete) | ~0.1ms | On stock change |

**Total per-page overhead: ~1-2ms.** Negligible on 50-200ms page renders.

---

## Product Labels System

Labels are part of the reservation system because REZERVIRAN/PRODANO are reservation-driven.
All 5 label types share the same infrastructure: `.product-card__labels` container (already in templates) + `.product-label` component (already in `_product-card.scss`).

### Label Definitions

| Label | Slovenian | Data source | Logic | Admin effort |
|-------|-----------|-------------|-------|-------------|
| Reserved | REZERVIRAN | `quantity=0` + active `oc_cart` row | Automatic | None |
| Sold | PRODANO | `quantity=0` + no active cart row | Automatic | None |
| New | NOVO | `product.date_added > NOW() - INTERVAL 14 DAY` | Automatic | None |
| Top brand | Top znamka | `product.manufacturer_id IN (whitelist)` | Config array | One-time setup |
| Tags attached | Z etiketo | Product attribute "Z etiketo" = `da` | Per-product attribute | Per product (bulk via IE Pro) |

### Display priority

REZERVIRAN and PRODANO are **exclusive** — when a product is unavailable, positive labels are irrelevant. The positive labels (NOVO, Top znamka, Z etiketo) can stack.

```
if quantity=0 AND in oc_cart → show REZERVIRAN only
if quantity=0 AND NOT in oc_cart → show PRODANO only
else → show any combination of NOVO + Top znamka + Z etiketo
```

### Top znamka — Manufacturer ID whitelist

Instead of tagging every product, define "top" manufacturer IDs once. Every product from those brands automatically gets the badge.

**Config array (in product model or controller):**
```php
// Top brand manufacturer IDs — configure once
// TODO: populate with actual IDs from oc_manufacturer after admin review
$top_brand_ids = array(42, 78, 103, 155); // e.g. Adidas, Benetton, Calzedonia, etc.
```

**In product data array:**
```php
'is_top_brand' => in_array((int)$product['manufacturer_id'], $top_brand_ids),
```

**Why hardcoded array, not admin UI:** The store has ~15-20 brands. Adding/removing a top brand is a rare 1-line code change. No admin UI complexity needed. Can upgrade to an OC setting (`config_top_brands`) later if the list changes frequently.

### Z etiketo — Product attribute

1. Create attribute group **"Oznake"** in OC admin (Catalog > Attributes > Attribute Groups)
2. Create attribute **"Z etiketo"** under "Oznake" (Catalog > Attributes)
3. On products with tags attached: set attribute value to `da`
4. Bulk-manageable via IE Pro import/export

**In product model query (batch for visible products):**
```php
// Batch check: which products have "Z etiketo" = "da"
if (!empty($product_ids)) {
    $tag_query = $this->db->query("SELECT pa.product_id FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id IN (" . implode(',', array_map('intval', $product_ids)) . ") AND pa.attribute_id = " . (int)$z_etiketo_attribute_id . " AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' AND LOWER(pa.text) = 'da'");

    $tagged_ids = array();
    foreach ($tag_query->rows as $row) {
        $tagged_ids[] = (int)$row['product_id'];
    }
}
```

### NOVO — Automatic from date_added

```php
'is_new' => (strtotime($product['date_added']) > strtotime('-14 days')),
```

No admin input. Products older than 14 days automatically lose the badge. Threshold can be adjusted.

### Combined product model query (batch, single pass)

All label data is gathered alongside the reservation status query (Section "Theme Changes > 2"):

```php
// After fetching products, gather all label data in batch:
if (!empty($product_ids)) {
    $ids_str = implode(',', array_map('intval', $product_ids));

    // 1. Reservation status (already planned)
    $reserved_query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "cart WHERE product_id IN (" . $ids_str . ") AND date_added > DATE_SUB(NOW(), INTERVAL 30 MINUTE) GROUP BY product_id");
    $reserved_ids = array();
    foreach ($reserved_query->rows as $row) {
        $reserved_ids[] = (int)$row['product_id'];
    }

    // 2. Z etiketo attribute
    $z_etiketo_attr_id = 123; // TODO: set after creating attribute in admin
    $tag_query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product_attribute WHERE product_id IN (" . $ids_str . ") AND attribute_id = '" . (int)$z_etiketo_attr_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "' AND LOWER(text) = 'da'");
    $tagged_ids = array();
    foreach ($tag_query->rows as $row) {
        $tagged_ids[] = (int)$row['product_id'];
    }
}

// Top brand IDs (static config)
$top_brand_ids = array(42, 78, 103, 155); // TODO: populate with real IDs

// Then in each product's data array:
'reservation_status' => ($product['quantity'] > 0) ? 'available' : (in_array((int)$product['product_id'], $reserved_ids) ? 'reserved' : 'sold'),
'is_new'             => (strtotime($product['date_added']) > strtotime('-14 days')),
'is_top_brand'       => in_array((int)$product['manufacturer_id'], $top_brand_ids),
'has_tag_label'      => in_array((int)$product['product_id'], $tagged_ids),
```

**Performance:** 2 batch queries total (reservation + z etiketo). Top znamka and NOVO are computed from data already in memory. Zero extra queries for those.

### Template (both `latest.twig` and `category.twig`)

Replaces the existing empty `<div class="product-card__labels"></div>`:

```twig
<div class="product-card__labels">
  {% if product.reservation_status == 'reserved' %}
    <span class="product-label product-label--reserved">REZERVIRAN</span>
  {% elseif product.reservation_status == 'sold' %}
    <span class="product-label product-label--sold">PRODANO</span>
  {% else %}
    {% if product.is_new %}
      <span class="product-label product-label--novo">NOVO</span>
    {% endif %}
    {% if product.is_top_brand %}
      <span class="product-label product-label--brand">Top znamka</span>
    {% endif %}
    {% if product.has_tag_label %}
      <span class="product-label product-label--tagged">Z etiketo</span>
    {% endif %}
  {% endif %}
</div>
```

### SCSS (extend existing `.product-label` in `_product-card.scss`)

```scss
.product-label {
  // Base styles already exist (inline-block, padding, radius, font)

  &--reserved { background: $color-warning; color: $color-text-heading; }
  &--sold     { background: $color-text-muted; color: #fff; }
  &--novo     { background: $color-primary; color: $color-text-heading; }  // exists
  &--brand    { background: $color-surface-alt; color: $color-text-base; } // exists
  &--tagged   { background: $color-success; color: #fff; }
}
```

### Product page labels

Same logic applies to the product page (`product.twig`). When `quantity == 0`:
- REZERVIRAN → show badge + disabled "V košarico" button with text "Rezerviran"
- PRODANO → show badge + disabled button with text "Prodano"

When `quantity > 0`: show NOVO / Top znamka / Z etiketo badges above the product title.

### Label assets

Existing label shape PNGs in `image/catalog/assets/elements/labels/` (ribbons, hexagons, scalloped circles) are available if we later want image-based labels instead of CSS pills. Current plan uses CSS-only pills for speed and simplicity. Image labels can be a future design option.

---

## Implementation Status (2026-04-03, final audit complete)

All phases complete. 6 audit rounds, converging to zero findings. Development uses direct core edits + test script. OCMOD XML generation deferred to production deploy.

### Automated Testing — 13/13 PASS
Test script: `claude/pages/reservation/test-reservation.sh`
```bash
bash claude/pages/reservation/test-reservation.sh
```
Tests: add, race condition, duplicate add, cart page, checkout, remove, heartbeat, labels (REZERVIRANO + PRODANO), server time, homepage. Cleans up after itself.

### Manual Browser Testing — ALL PASS (2026-04-03)
- [x] Full end-to-end: browse → add → checkout → order
- [x] Order cancel (admin) restores stock
- [x] Login merge: guest → register → cart preserved
- [x] Logout → cart hidden, login back → cart restored
- [x] Second browser: REZERVIRANO label, disabled CTA, "Že v košarici drugega kupca"
- [x] Cron endpoint: clearExpired returns `{"success":true}`
- [x] All 5 label types verified on category pages
- [x] All 3 error messages verified (reserved, sold, already in cart)

### Audit History
| Round | Approach | Findings found | All fixed |
|-------|----------|---------------|-----------|
| 1st | Heuristic | 5 critical, 7 warning | Yes |
| 2nd | Deeper heuristic | 5 critical, 2 warning | Yes |
| 3rd | Even deeper | 3 high, 1 medium | Yes |
| 4th | Targeted | 2 medium | Yes |
| 5th | Verification matrix (50 checks) | 1 low | Yes |
| **6th** | **Re-run matrix + 7 extra (57 checks)** | **0** | **Clean** |

### Fixes Applied During Audits
- Transaction wrapping: remove(), clear(), clearCart() — all use FOR UPDATE
- Double-restock race: expiry uses SELECT FOR UPDATE to lock rows before restoring
- Login merge dedup: checks for existing product, delete-before-restore with countAffected guard
- Login merge race: date_added refreshed on guest rows before merge loop
- Cookie recovery race: date_added = NOW() during recovery prevents immediate expiry
- Cart token: session-scoped (reused across add calls, all items share one token)
- IP fallback removed: shared IPs caused cart hijacking, cookie-only is sufficient
- Cookie Secure flag: set based on HTTPS
- Cache invalidation: only when expired rows found (not every request)
- api_id='0' filter: consistent across all 10+ reservation queries
- API order cleanup: LIMIT 1 prevents deleting other guests' reservations
- XSS: all localStorage data escaped in recently viewed via esc() helper
- Template sold state: added to similar + related product sections
- JS safeId: moved before btnHtml (was undefined due to hoisting)
- Product page CTA: green checkmark on success, Bootstrap reset prevented when disabled
- All products: subtract=0 → subtract=1 (required for OC restock on order cancel)

### Security Properties (verified in final audit)
- **No SQL injection**: all strings escaped, all integers cast
- **No XSS**: all user data from localStorage escaped via esc()
- **No stock inflation**: every decrement has matching restore path, all in transactions
- **No stock leak**: every add() has eventual remove/expire/clearCart path
- **No negative stock**: `WHERE quantity >= N` prevents over-decrement
- **No double-restock**: FOR UPDATE locks prevent concurrent restore
- **Race-safe**: atomic reservation via InnoDB row lock + FOR UPDATE
- **256-bit cart token**: infeasible to brute-force
- **Cookie HttpOnly + Secure**: no JS access, HTTPS-only on production

---

## Files Summary — Complete for OCMOD Generation

### OCMOD 1: reservation-timer (core reservation logic)
| File | What changed |
|------|-------------|
| `system/library/cart/cart.php` | Heart of system: atomic add, transactional remove/clear/clearCart, expiry with FOR UPDATE, cookie recovery, login merge with dedup, getVisitorIp, cache property |
| `catalog/controller/checkout/cart.php` | currentTime, clearExpired, getStockStatus endpoints + reservation failure/sold/already-in-cart catch |
| `catalog/controller/checkout/success.php` | `clearCart()` instead of `clear()` |
| `catalog/controller/api/order.php` | `clearCart()` + product-specific cart cleanup with LIMIT 1 |
| `catalog/model/checkout/order.php` | Stock subtraction commented out (reserved at cart-add time) |
| `catalog/language/sl-SI/checkout/cart.php` | error_reserved, error_already_in_cart, error_sold, text_reservation_timer |
| `catalog/language/en-gb/checkout/cart.php` | English equivalents |

### OCMOD 2: reservation-checkout-extend (heartbeat)
| File | What changed |
|------|-------------|
| `catalog/controller/checkout/checkout.php` | updateCartTime() method — resets date_added on POST |

### OCMOD 3: reservation-labels (labels + product page states)
| File | What changed |
|------|-------------|
| `catalog/model/catalog/product.php` | getProductsByIds() batch query + getProductLabels() (reservation, in_cart, is_new, is_top_brand, has_tag_label) |
| `catalog/controller/extension/module/latest.php` | Calls getProductLabels(), passes 5 label fields |
| `catalog/controller/product/category.php` | Same |
| `catalog/controller/product/search.php` | Same |
| `catalog/controller/product/special.php` | Same |
| `catalog/controller/product/manufacturer.php` | Same |
| `catalog/controller/product/product.php` | reservation_status for PDP, labels for similar/related, sold filter, batch query for similar |
| `catalog/controller/common/cart.php` | date_added + server_time passed to template |

### Theme files (NOT OCMOD — deployed with theme)
| File | What changed |
|------|-------------|
| `template/checkout/cart.twig` | data-server-time, data-time-added attributes |
| `template/checkout/checkout.twig` | Heartbeat JS (30s interval) |
| `template/common/cart.twig` | Per-item timer in dropdown + mobile sheet |
| `template/extension/module/latest.twig` | Label + button states (in_cart/reserved/sold/available) |
| `template/product/category.twig` | Same label + button states |
| `template/product/product.twig` | Gallery label, CTA states, similar/related labels, scarcity hidden when sold |
| `javascript/src/theme.js` | Server-synced timer, ajaxComplete instant update, recently viewed with getStockStatus + XSS escaping |
| `stylesheet/src/components/_product-card.scss` | --in-cart, --reserved, --sold, --tagged, --novo, --brand label modifiers + cart button states |
| `stylesheet/src/pages/_product.scss` | pdp-cart-btn --in-cart, --disabled + gallery label positioning |
| `stylesheet/src/layout/_header.scss` | cart-drop__timer styling |

### Database Changes (one-time, auto-detected on first request)
```sql
ALTER TABLE oc_cart ADD COLUMN visitor_ip VARCHAR(45) NOT NULL DEFAULT '' AFTER date_added;
ALTER TABLE oc_cart ADD COLUMN cart_token VARCHAR(64) NOT NULL DEFAULT '' AFTER visitor_ip;
UPDATE oc_product SET subtract = 1 WHERE status = 1;
```

### Recommended Index (add before production at scale)
```sql
ALTER TABLE oc_cart ADD INDEX idx_expiry (api_id, date_added), ADD INDEX idx_product_id (product_id);
```

---

## Cron Setup (optional safety net)

```crontab
*/5 * * * * curl -s https://yourdomain.com/index.php?route=checkout/cart/clearExpired > /dev/null
```

Catches expired reservations when no visitors trigger the constructor. Add shared secret token before production.

---

## OCMOD Generation Checklist (when ready to deploy)

```
1. Create git tag for the clean baseline: git tag reservation-baseline
2. For each OCMOD file:
   a. Diff each modified core file against the original OC 3.0.5.0 version
   b. Convert each diff hunk into an OCMOD <operation> XML block
   c. Test: upload OCMOD via admin, refresh modifications, verify site works
   d. Run test-reservation.sh to verify all 13 assertions pass
3. Remove direct core file edits (revert to original + OCMOD overlay)
4. Final test: full end-to-end browser test
5. Deploy theme files (templates, JS, SCSS) alongside OCMOD files
6. Run SQL: ALTER TABLE + UPDATE subtract + ADD INDEX
7. Setup cron
8. Monitor: check system/storage/logs/ for PHP errors after first hour
```
