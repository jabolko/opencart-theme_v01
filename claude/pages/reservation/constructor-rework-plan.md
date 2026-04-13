# Cart Constructor Rework — Architectural Plan (Reservation System)

> Date: 2026-04-13
> Affects: reservation system (cart.php) + checkout template (heartbeat timing)

## Problem Statement

The Cart constructor runs on **every HTTP request** (startup.php line 209). It contains a reservation expiry cleanup that uses `SELECT ... FOR UPDATE NOWAIT` on the `oc_cart` table. When two requests arrive concurrently, the second one's NOWAIT query fails with MySQL error 3572. OC's `mysqli_report(MYSQLI_REPORT_ERROR)` emits a PHP Warning BEFORE the exception propagates — this warning is uncatchable by try/catch and renders in the HTTP response.

The user sees the warning inside the checkout "Tvoji podatki" panel because the AJAX response for the guest form contains the warning output.

## Root Cause

The checkout page fires **two simultaneous AJAX calls** on `$(document).ready()`:

1. `GET checkout/guest` (or `checkout/payment_address` for logged-in users)
2. `POST checkout/checkout/updateCartTime` (immediate heartbeat call)

Both hit the server at the same time. Both trigger the Cart constructor. Both attempt `SELECT ... FOR UPDATE NOWAIT` on the same expired cart rows. The second request fails.

Additionally, at other checkout transitions, up to **4 concurrent AJAX calls** fire:
- Payment address save (logged-in): `GET shipping_method` + `GET shipping_address` + `GET payment_address` (3 parallel)
- Plus heartbeat if it coincides (4th)

## Current Architecture (broken)

```
Every HTTP request
  → startup.php creates Cart object
    → Cart constructor runs:
      1. Schema check (once per session)
      2. Cookie recovery (guests only)
      3. Expiry cleanup: SELECT FOR UPDATE NOWAIT on ALL expired rows
         → Restore stock → Delete rows → COMMIT
      4. Login merge (logged-in users only)
```

**Problem:** Step 3 runs on EVERY request, locks rows across ALL sessions, and fails immediately (NOWAIT) if another request holds those locks. The PHP Warning from `mysqli_report` is uncatchable.

## Proposed Architecture

```
Every HTTP request
  → startup.php creates Cart object
    → Cart constructor runs:
      1. Schema check (once per session) — UNCHANGED
      2. Cookie recovery (guests only) — UNCHANGED
      3. Expiry cleanup: REMOVED from constructor
      4. Login merge (logged-in users only) — UNCHANGED

Expiry runs via TWO mechanisms:
  A. Cron endpoint (every 5 min) — primary, uses SKIP LOCKED
  B. Rate-limited fallback in constructor (once per 60s per session) — uses SKIP LOCKED
```

### Changes in detail:

### Change 1: Remove NOWAIT expiry from constructor, add rate-limited SKIP LOCKED

**Before (lines 44-65 of cart.php):**
```php
// Runs on EVERY request, NOWAIT fails on concurrent requests
try {
    $this->db->query("START TRANSACTION");
    $expired = $this->db->query("SELECT ... FOR UPDATE NOWAIT");
    // ... restore stock, delete rows ...
    $this->db->query("COMMIT");
} catch (\Exception $e) {
    // Warning already emitted — uncatchable
}
```

**After:**
```php
// Rate-limited: runs once per 60 seconds per session
// SKIP LOCKED: silently skips rows held by other transactions (no error, no warning)
if (empty($this->session->data['_last_expiry']) || (time() - $this->session->data['_last_expiry']) > 60) {
    $this->session->data['_last_expiry'] = time();

    $this->db->query("START TRANSACTION");
    $expired = $this->db->query("SELECT cart_id, product_id, quantity FROM " . DB_PREFIX . "cart
        WHERE date_added < DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND api_id = '0'
        FOR UPDATE SKIP LOCKED");
    if ($expired->num_rows) {
        $expired_ids = array();
        foreach ($expired->rows as $row) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = quantity + " . (int)$row['quantity'] . "
                WHERE product_id = '" . (int)$row['product_id'] . "'");
            $expired_ids[] = (int)$row['cart_id'];
        }
        $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id IN (" . implode(',', $expired_ids) . ")");
        if ($this->cache) {
            $this->cache->delete('product');
        }
    }
    $this->db->query("COMMIT");
}
```

**Why this works:**
- `SKIP LOCKED` never errors — it returns 0 rows if all expired rows are locked
- Rate limiting means at most 1 expiry check per session per 60 seconds (not per request)
- No try/catch needed — no exception, no warning
- 60-second delay is acceptable (items are reserved for 30 minutes — 1 extra minute is negligible)

### Change 2: Update cron endpoint to use SKIP LOCKED

**Before (cart controller clearExpired):**
```php
$this->db->query("SELECT ... FOR UPDATE NOWAIT");
```

**After:**
```php
$this->db->query("SELECT ... FOR UPDATE SKIP LOCKED");
```

Same reasoning — cron should gracefully skip locked rows.

### Change 3: Delay the checkout heartbeat initial call

**Before (checkout.twig line 1348):**
```javascript
$.post('index.php?route=checkout/checkout/updateCartTime');
```

**After:**
```javascript
setTimeout(function() {
    $.post('index.php?route=checkout/checkout/updateCartTime');
}, 3000);
```

**Why:** The immediate heartbeat races with the guest/payment_address form AJAX. A 3-second delay ensures the form loads first. The 30-second interval continues normally after that.

### Change 4: Remove retry loops from add/remove/clear/clearCart

**Before:** Each method has a `for ($retry = 0; $retry < 3; $retry++)` loop with try/catch.

**After:** Single try/catch (no retry loop). The retry was needed because NOWAIT/deadlock could fail the expiry, which then conflicted with add/remove. With SKIP LOCKED on expiry + rate limiting, the concurrent conflict is eliminated. The remaining deadlock risk between add() and remove() on different users is near-zero (different cart rows, different product rows).

If a deadlock still occurs (extremely unlikely), the Exception propagates normally — OC's error handler shows it. This is correct behavior for a genuine unexpected error, not a routine concurrency event that should be silently retried.

**Keeping the transactions (START TRANSACTION + FOR UPDATE + COMMIT) is essential** — they protect stock integrity. Only the retry loop wrapper is removed.

## Impact Analysis

| What | Before | After |
|------|--------|-------|
| Expiry frequency | Every request (10+ per checkout page) | Once per 60s per session + cron every 5min |
| Lock contention | High (NOWAIT fails on 2nd concurrent request) | Near-zero (SKIP LOCKED never conflicts) |
| PHP Warnings | Frequent on checkout | Impossible (SKIP LOCKED doesn't error) |
| Deadlock risk | Moderate (retry loops mask it) | Near-zero (rate-limited, no concurrent expiry) |
| Worst-case expiry delay | 0s (every request) | 60s (rate limit) + 5min (cron miss) |
| Stock safety | Protected by FOR UPDATE | Protected by FOR UPDATE SKIP LOCKED |
| Code complexity | try/catch + retry loops in 5 methods | Simple sequential code, no retries |

## Files to modify

| File | Change |
|------|--------|
| `system/library/cart/cart.php` | Replace NOWAIT expiry with rate-limited SKIP LOCKED. Remove retry loops from add/remove/clear/clearCart (keep transactions). |
| `catalog/controller/checkout/cart.php` | clearExpired: NOWAIT → SKIP LOCKED. Remove try/catch. |
| `catalog/view/theme/otroskikoticek/template/checkout/checkout.twig` | Delay initial heartbeat by 3 seconds |

## What stays the same

- Atomic add() with transaction + FOR UPDATE (stock reservation)
- Transactional remove() with FOR UPDATE (stock restore)
- Transactional clear() with FOR UPDATE (stock restore)
- Transactional clearCart() with FOR UPDATE (no stock restore)
- Cookie recovery (no transaction needed)
- Login merge with dedup (no transaction needed — guarded by countAffected)
- All label logic, template states, JS instant updates
- Cron endpoint (exists, just changes from NOWAIT to SKIP LOCKED)
- Heartbeat interval (30s — unchanged, only initial call delayed)

## Execution order

```
1. Rewrite cart.php constructor expiry (SKIP LOCKED + rate limit)
2. Remove retry loops from add/remove/clear/clearCart (keep transactions)
3. Update clearExpired endpoint (SKIP LOCKED)
4. Delay heartbeat initial call in checkout.twig
5. Run test-reservation.sh (13 assertions)
6. Run checkout-flows.js (5 checkout combinations)
7. Run multi-user-sim.js (10 concurrent users)
8. Manual browser test: register → add → cart → checkout
```
