# Reservation System — Complete Implementation Plan

> Last updated: 2026-04-01

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

**Modifies 6 files in OpenCart core.**

---

### File 1: `system/library/cart/cart.php`

This is the heart of the system. All modifications happen in this file.

#### 1.1 Constructor — DB schema auto-init (once per session)

**Location:** After `$this->weight = $registry->get('weight');` (line 18)
**Operation:** `<add position="after">`

```php
// Reservation system: ensure columns exist (runs once per session)
if (empty($this->session->data['_reservation_db_init'])) {
    $col_check = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "cart` LIKE 'visitor_ip'");
    if (!$col_check->num_rows) {
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "cart` ADD COLUMN `visitor_ip` VARCHAR(45) NOT NULL DEFAULT '' AFTER `date_added`");
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "cart` ADD COLUMN `cart_token` VARCHAR(64) NOT NULL DEFAULT '' AFTER `visitor_ip`");
    }
    $this->session->data['_reservation_db_init'] = true;
}
```

#### 1.2 Constructor — Persistent cart recovery (before expiry cleanup)

**Location:** Before `DELETE FROM cart WHERE ... INTERVAL 1 HOUR` (line 21)
**Operation:** `<add position="before">`

```php
// Persistent cart recovery for guests (cookie-first, IP fallback)
if (!(int)$this->customer->getId()) {
    $recovered = false;
    $current_session = $this->session->getId();

    // Method 1: Cookie-based recovery
    if (!empty($_COOKIE['oc_cart_token'])) {
        $token = $_COOKIE['oc_cart_token'];
        $cookie_cart = $this->db->query("SELECT cart_id FROM " . DB_PREFIX . "cart WHERE cart_token = '" . $this->db->escape($token) . "' AND customer_id = '0' AND session_id != '" . $this->db->escape($current_session) . "' LIMIT 1");
        if ($cookie_cart->num_rows) {
            $this->db->query("UPDATE " . DB_PREFIX . "cart SET session_id = '" . $this->db->escape($current_session) . "' WHERE cart_token = '" . $this->db->escape($token) . "' AND customer_id = '0'");
            $recovered = true;
        }
    }

    // Method 2: IP fallback (only if cookie didn't match)
    if (!$recovered) {
        $visitor_ip = $this->getVisitorIp();
        if ($visitor_ip) {
            $ip_cart = $this->db->query("SELECT cart_id FROM " . DB_PREFIX . "cart WHERE visitor_ip = '" . $this->db->escape($visitor_ip) . "' AND customer_id = '0' AND session_id != '" . $this->db->escape($current_session) . "' AND date_added > DATE_SUB(NOW(), INTERVAL 30 MINUTE) LIMIT 1");
            if ($ip_cart->num_rows) {
                $this->db->query("UPDATE " . DB_PREFIX . "cart SET session_id = '" . $this->db->escape($current_session) . "' WHERE visitor_ip = '" . $this->db->escape($visitor_ip) . "' AND customer_id = '0' AND date_added > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
            }
        }
    }
}
```

**Note:** IP fallback is scoped to 30 minutes (active reservations only), not 30 days — a long-lived IP fallback would incorrectly merge carts for shared IPs (family on same Wi-Fi). Cookie recovery has no time limit (cookie itself expires in 30 days).

#### 1.3 Constructor — Replace expiry cleanup

**Search:**
```php
$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE (api_id > '0' OR customer_id = '0') AND date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
```

**Replace with:**
```php
// Reservation expiry: restore stock + delete expired cart rows (transaction)
$this->db->query("START TRANSACTION");
$this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "cart c ON p.product_id = c.product_id SET p.quantity = p.quantity + c.quantity WHERE c.date_added < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND c.api_id = '0'");
$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE date_added < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND api_id = '0'");
$this->db->query("COMMIT");

// Invalidate product cache (Latest/Popular/Bestseller may have stale stock)
$this->cache = $registry->get('cache');
if ($this->cache) { $this->cache->delete('product'); }
```

**Why `api_id = '0'`?** API cart entries (from admin) follow different lifecycle rules. We only expire customer/guest carts.

**Why transaction?** If crash between UPDATE and DELETE, next run would find the same rows and restore stock again (double-restore). Transaction ensures both happen atomically — crash = rollback = safe.

#### 1.4 Constructor — Login merge fix

**Search:**
```php
$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart['cart_id'] . "'");

				// The advantage of using $this->add is that it will check if the products already exist and increaser the quantity if necessary.
				$this->add($cart['product_id'], $cart['quantity'], json_decode($cart['option']), $cart['recurring_id']);
```

**Replace with:**
```php
// Reservation system: claim the guest cart row (don't delete+re-add — would double-decrement)
$this->db->query("UPDATE " . DB_PREFIX . "cart SET customer_id = '" . (int)$this->customer->getId() . "' WHERE cart_id = '" . (int)$cart['cart_id'] . "'");
```

**Why:** Original OC deletes the guest row then calls `add()` which would INSERT a new row. With our system, `add()` includes stock reservation logic. Calling it for an already-reserved product would try to decrement stock again (double-deduction). Instead, we just reassign ownership.

#### 1.5 add() — Atomic stock reservation

**Search:**
```php
if (!$query->row['total']) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "cart SET api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', customer_id = '" . (int)$this->customer->getId() . "', session_id = '" . $this->db->escape($this->session->getId()) . "', product_id = '" . (int)$product_id . "', recurring_id = '" . (int)$recurring_id . "', `option` = '" . $this->db->escape(json_encode($option)) . "', quantity = '" . (int)$quantity . "', date_added = NOW()");
		} else {
			$this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = (quantity + " . (int)$quantity . ") WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
		}
```

**Replace with:**
```php
if (!$query->row['total']) {
    // Atomic stock reservation: decrement only if available
    $this->db->query("START TRANSACTION");
    $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = quantity - " . (int)$quantity . " WHERE product_id = '" . (int)$product_id . "' AND quantity >= " . (int)$quantity);

    if ($this->db->countAffected() > 0) {
        $cart_token = bin2hex(random_bytes(32));
        $visitor_ip = $this->getVisitorIp();

        $this->db->query("INSERT INTO " . DB_PREFIX . "cart SET api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', customer_id = '" . (int)$this->customer->getId() . "', session_id = '" . $this->db->escape($this->session->getId()) . "', product_id = '" . (int)$product_id . "', recurring_id = '" . (int)$recurring_id . "', `option` = '" . $this->db->escape(json_encode($option)) . "', quantity = '" . (int)$quantity . "', visitor_ip = '" . $this->db->escape($visitor_ip) . "', cart_token = '" . $this->db->escape($cart_token) . "', date_added = NOW()");

        $this->db->query("COMMIT");

        // Set persistent recovery cookie (30 days, HttpOnly, SameSite)
        setcookie('oc_cart_token', $cart_token, time() + (30 * 86400), '/', '', false, true);

        // Invalidate product cache
        $cache = $this->config->get('cache');
        // Use registry if available
        if (isset($this->cache)) { $this->cache->delete('product'); }

    } else {
        $this->db->query("ROLLBACK");
        // Signal reservation failure to controller
        $this->session->data['reservation_failed'] = (int)$product_id;
    }
} else {
    // Product already in this customer's cart — do nothing
    // For qty=1 unique products, don't increment (would exceed max=1)
}
```

**Race condition handling:** Two users add the same product simultaneously. Both execute `UPDATE WHERE quantity >= 1`. MySQL acquires a row lock on the first UPDATE. Second waits. First commits (quantity 1→0). Second runs, `quantity >= 1` fails (quantity is now 0), `countAffected()` = 0, rollback. Second user gets "already reserved" error. No overselling.

#### 1.6 remove() — Restore stock on item removal

**Search:**
```php
public function remove($cart_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
```

**Replace with:**
```php
public function remove($cart_id) {
    // Restore reserved stock before removing from cart
    $cart_item = $this->db->query("SELECT product_id, quantity FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
    if ($cart_item->num_rows) {
        $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = quantity + " . (int)$cart_item->row['quantity'] . " WHERE product_id = '" . (int)$cart_item->row['product_id'] . "'");
        // Invalidate cache
        if (isset($this->cache)) { $this->cache->delete('product'); }
    }
    $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
```

#### 1.7 update() — Cap quantity at 1

**Search:**
```php
public function update($cart_id, $quantity) {
		$this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = '" . (int)$quantity . "' WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
```

**Replace with:**
```php
public function update($cart_id, $quantity) {
    if ((int)$quantity <= 0) {
        $this->remove($cart_id);
        return;
    }
    // For unique products (qty=1): always cap at 1, no stock change needed
    $this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = '1' WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
```

#### 1.8 New method: clearCart() — order success (no stock restore)

**Location:** Before `public function clear()` (line 296)
**Operation:** `<add position="before">`

```php
// Called on successful order — stock stays deducted (reservation becomes sale)
public function clearCart() {
    $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
}

```

#### 1.9 clear() — Restore stock on manual clear

**Search:**
```php
public function clear() {
		$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
```

**Replace with:**
```php
public function clear() {
    // Restore stock for all reserved items before clearing
    $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "cart c ON p.product_id = c.product_id SET p.quantity = p.quantity + c.quantity WHERE c.api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND c.customer_id = '" . (int)$this->customer->getId() . "' AND c.session_id = '" . $this->db->escape($this->session->getId()) . "'");
    // Invalidate cache
    if (isset($this->cache)) { $this->cache->delete('product'); }
    $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
```

**Where `clear()` is called (all must be audited):**
- `catalog/controller/checkout/success.php:7` — **CHANGE to `clearCart()`** (sale complete, don't restore)
- `catalog/controller/api/order.php:355` — **CHANGE to `clearCart()`** (API order complete)
- `catalog/controller/api/cart.php:12` — **KEEP as `clear()`** (API reset = genuinely emptying cart, restore stock)
- `catalog/controller/account/login.php:11` — **KEEP as `clear()`** (admin override = clear everything, restore stock)

#### 1.10 getProducts() — Pass `date_added` through

**Search:**
```php
'cart_id'         => $cart['cart_id'],
```

**Add after:**
```php
'date_added'      => $cart['date_added'],
```

This allows templates to display per-item server-time countdown.

#### 1.11 New method: getVisitorIp()

**Location:** Before `public function hasDownload()` (line 404)
**Operation:** `<add position="before">`

```php
private function getVisitorIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    return !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}

```

#### 1.12 Cache property — store registry reference

**Location:** After `private $weight;` (line 10)
**Operation:** `<add position="after">`

```php
private $cache;
```

**Location:** After `$this->weight = $registry->get('weight');` (line 18), before the DB init block
**Operation:** `<add position="after">`

```php
$this->cache = $registry->get('cache');
```

---

### File 2: `catalog/controller/checkout/cart.php`

#### 2.1 New endpoints — server time + cron cleanup

**Location:** Before `public function index()` (line 3)
**Operation:** `<add position="before">`

```php
public function currentTime() {
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode(array('server_time' => date('Y-m-d H:i:s'))));
}

public function clearExpired() {
    // Cron endpoint — run every 5 min via crontab or OC cron
    $this->db->query("START TRANSACTION");
    $this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "cart c ON p.product_id = c.product_id SET p.quantity = p.quantity + c.quantity WHERE c.date_added < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND c.api_id = '0'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE date_added < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND api_id = '0'");
    $this->db->query("COMMIT");
    $this->cache->delete('product');
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode(array('success' => true)));
}

```

**Note:** `clearExpired()` is a safety net. The constructor already runs expiry on every page load. The cron catches cases where no customer visits the site for extended periods (items stay reserved until someone triggers the constructor).

#### 2.2 add() — Catch reservation failure

**Location:** After `$this->cart->add(...)` call, before `$json['success'] = sprintf(...)` (around line 366)
**Operation:** `<add position="before">`

```php
// Check if reservation failed (stock unavailable — another customer reserved first)
if (isset($this->session->data['reservation_failed']) && $this->session->data['reservation_failed'] == $this->request->post['product_id']) {
    unset($this->session->data['reservation_failed']);
    $json = array();
    $json['error']['warning'] = $this->language->get('error_reserved');
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
    return;
}
```

---

### File 3: `catalog/controller/checkout/success.php`

#### 3.1 Use clearCart() instead of clear()

**Search:**
```php
$this->cart->clear();
```

**Replace with:**
```php
$this->cart->clearCart();
```

Stock was already deducted at add-to-cart time. On successful order, just delete cart rows — do NOT restore stock.

---

### File 4: `catalog/controller/api/order.php`

#### 4.1 Use clearCart() for API orders

**Search:** (line ~355)
```php
$this->cart->clear();
```

**Replace with:**
```php
$this->cart->clearCart();
```

Same reason as success.php — API orders are completed orders.

---

### File 5: `catalog/model/checkout/order.php`

#### 5.1 Disable stock subtraction on order processing

**Search:** (line ~324)
```php
$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");
```

**Replace with:**
```php
// Reservation system: stock already deducted at add-to-cart time — skip
// $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");
```

Also disable option value subtraction (line ~329):
```php
// $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
```

**IMPORTANT:** The **restock logic** (order cancellation, line ~354: `quantity + N`) stays **UNCHANGED**. If an order is cancelled/voided, stock is correctly restored by OC's built-in logic.

---

### File 6: Language files

#### 6.1 Slovenian — `catalog/language/sl-SI/checkout/cart.php`

```php
$_['error_reserved']          = 'Artikel je že rezerviran za drugo stranko.';
$_['text_reservation_timer']  = 'Rezervirano še %s';
```

#### 6.2 English — `catalog/language/en-gb/checkout/cart.php`

```php
$_['error_reserved']          = 'This item is already reserved by another customer.';
$_['text_reservation_timer']  = 'Reserved for %s';
```

---

## OCMOD 2: reservation-checkout-extend.ocmod.xml

**Modifies 1 file.** Extends reservation timer during active checkout via heartbeat.

---

### File 1: `catalog/controller/checkout/checkout.php`

#### 1.1 New method: updateCartTime()

**Location:** Before `public function index()`
**Operation:** `<add position="before">`

```php
public function updateCartTime() {
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
        $this->db->query("UPDATE " . DB_PREFIX . "cart SET date_added = NOW() WHERE session_id = '" . $this->db->escape($this->session->getId()) . "' AND api_id = '0'");
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(array('success' => true, 'server_time' => date('Y-m-d H:i:s'))));
    }
}

```

### Theme integration — Heartbeat JS

**In `checkout.twig`, before `{{ footer }}`:**

```html
<script type="text/javascript"><!--
(function() {
    // Heartbeat: extend reservation every 30 seconds during checkout
    var hb = setInterval(function() {
        $.post('index.php?route=checkout/checkout/updateCartTime');
    }, 30000);
    // Immediate call on page load
    $.post('index.php?route=checkout/checkout/updateCartTime');
    // Stop on unload
    $(window).on('beforeunload', function() { clearInterval(hb); });
})();
//--></script>
```

This is added directly to our theme's checkout.twig (not via OCMOD) since we already own this template.

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

## Implementation Status (2026-04-03)

All phases complete. Development uses direct core edits + test script. OCMOD XML generation deferred to production deploy.

### Phase 1: Core reservation — DONE
- Direct edits to 12 core PHP files (manifest: `modified-files.txt`)
- Test script: `test-reservation.sh` (13 assertions + cleanup)

### Phase 2: Core testing — ALL PASS
- [x] Add product → qty decremented (T1)
- [x] Race condition → second user blocked (T2)
- [x] Already in cart → error returned (T3)
- [x] Cart page → no stock warning (T4)
- [x] Checkout accessible (T5)
- [x] Remove → stock restored (T6)
- [x] Heartbeat extends timer (T7)
- [x] Expiry → stock restored after 30 min (T9)

### Phase 3: Theme integration — DONE
- [x] cart.twig — data-server-time, data-time-added
- [x] Cart controller — server_time passed
- [x] Server-synced timer in theme.js (replaced localStorage)
- [x] Checkout heartbeat JS
- [x] Z etiketo attribute (ID=82, 52 products tagged)
- [x] Top brand IDs (Benetton=14, Gap=23, H&M=13, Next=18, Okaidi=17, S.Oliver=16, Zara=12)
- [x] getProductLabels() batch method in product model
- [x] Labels wired: category, latest, product page, similar, related
- [x] SCSS: --reserved, --sold, --tagged, --in-cart, --brand, --novo
- [x] Instant UI: ajaxComplete hook for badge/button swap
- [x] getStockStatus endpoint for JS-rendered cards (recently viewed)
- [x] Cart dropdown + mobile sheet per-item timers
- [x] Sold filtered from similar/related/recently viewed
- [x] Product page CTA states (V košarici / Že v košarici drugega kupca / Prodano)
- [x] Scarcity hidden when sold
- [x] npm run build

### Phase 4: Manual browser testing — ALL PASS (2026-04-03)
- [x] Full end-to-end: browse → add → checkout → order (order 9276)
- [x] Order cancel restores stock (orders 9278, 9279) — required `subtract=1` fix
- [x] Login merge: guest → register → cart preserved
- [x] Logout → cart hidden, login back → cart restored
- [x] Second browser: REZERVIRANO label, disabled CTA, "Že v košarici drugega kupca"
- [x] Cron endpoint: clearExpired returns `{"success":true}`
- [x] NOVO, Top znamka, Z etiketo labels verified on category pages
- [x] Duplicate add → "Ta artikel je že v tvoji košarici"
- [x] Sold add → "Ta artikel je žal že prodan"
- [x] Reserved add → "Artikel je že rezerviran za drugo stranko"

### Critical fix discovered during testing
All 6913 products had `subtract=0` (original import). Fixed globally to `subtract=1`.
Without this, OC's restock on order cancel does not fire (`WHERE subtract='1'`).

### Remaining items (not blockers)
- Generate OCMOD XML from diffs (pre-production)
- N+1 optimization in similar products (batch query)
- Search page labels (product/search controller — same pattern)
- Special/manufacturer page labels (same pattern)
- Update core-modifications.md with reservation entries

---

## Files Summary

### OCMOD files (install via admin):
| File | Core files modified |
|------|-------------------|
| `reservation-timer.ocmod.xml` | cart.php, cart controller, success.php, api/order.php, order model, language files |
| `reservation-checkout-extend.ocmod.xml` | checkout.php controller |

### Theme files (normal development):
| File | Change |
|------|--------|
| `cart.twig` | `data-server-time`, `data-time-added` attributes |
| `common/cart.twig` | Per-item timer badge |
| `theme.js` | Replace localStorage timer with server-synced timer |
| `_cart.scss` | Minor update for expired/urgent states (already exists) |
| `_product-card.scss` | Label modifiers: `--reserved`, `--sold`, `--tagged` |
| `latest.twig` | Label template in `.product-card__labels` |
| `category.twig` | Same label template |
| `product.twig` | Labels + disabled button for reserved/sold |
| `checkout.twig` | Heartbeat JS (already in our template) |

### Core modifications tracked in `core-modifications.md`:
| File | Change |
|------|--------|
| `catalog/model/catalog/product.php` | Batch reservation + label queries, label fields in product data |
| `catalog/controller/checkout/cart.php` | `server_time` passed to cart template |

---

## Cron Setup (optional safety net)

```crontab
*/5 * * * * curl -s http://localhost:8080/index.php?route=checkout/cart/clearExpired > /dev/null
```

This catches expired reservations even when no customer visits the site. The constructor handles it on every page load too, so the cron is defense-in-depth only.
