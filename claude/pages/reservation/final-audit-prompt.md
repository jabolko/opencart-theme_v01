# Final Reservation System Audit Prompt

Run this prompt as a single agent with maximum context. Do not split across multiple agents.

---

## Instructions

Read EVERY file listed below IN FULL. Then verify EVERY item in the verification matrix. For each item, output:
- **PASS [line:file]** — verified correct, cite the exact line(s)
- **FAIL [line:file]** — bug found, describe the issue and the fix
- **N/A** — not applicable to this codebase

Do NOT invent issues. Do NOT report style preferences. Only report actual correctness, security, or data integrity bugs.

## Files to Read (ALL in full)

1. /Users/mihaavgustin/opencart-theme_v01/opencart/system/library/cart/cart.php
2. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/checkout/cart.php (lines 1-80 + lines 430-490)
3. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/checkout/success.php
4. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/checkout/checkout.php (lines 1-15)
5. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/api/order.php (lines 340-380)
6. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/model/checkout/order.php (lines 300-400)
7. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/model/catalog/product.php (last 150 lines)
8. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/product/product.php (lines 253-280 + 410-580)
9. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/product/category.php (lines 183-245)
10. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/extension/module/latest.php
11. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/product/search.php (lines 187-245)
12. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/product/special.php (lines 79-135)
13. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/product/manufacturer.php (lines 148-210)
14. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/controller/common/cart.php
15. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/view/theme/otroskikoticek/template/product/product.twig
16. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/view/theme/otroskikoticek/template/common/cart.twig
17. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/view/theme/otroskikoticek/template/checkout/cart.twig (lines 1-75)
18. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/view/theme/otroskikoticek/template/extension/module/latest.twig
19. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/view/theme/otroskikoticek/template/product/category.twig (lines 210-285)
20. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/view/theme/otroskikoticek/template/checkout/checkout.twig (last 20 lines)
21. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/view/theme/otroskikoticek/javascript/src/theme.js (lines 190-260 + 700-800 + 870-960)
22. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/language/sl-SI/checkout/cart.php (last 10 lines)
23. /Users/mihaavgustin/opencart-theme_v01/opencart/catalog/language/en-gb/checkout/cart.php (last 10 lines)

---

## VERIFICATION MATRIX

### A. Transaction Safety (cart.php)

For EACH method that modifies data (add, remove, update, clear, clearCart, constructor expiry, constructor login merge):

- [ ] A1. Every START TRANSACTION has exactly one COMMIT or ROLLBACK on every code path
- [ ] A2. No START TRANSACTION is issued while another transaction is open (no nesting)
- [ ] A3. SELECT ... FOR UPDATE is used before any UPDATE/DELETE within the transaction
- [ ] A4. If an INSERT/UPDATE/DELETE fails (exception), the transaction gets rolled back (either explicitly or via connection close)
- [ ] A5. No data-modifying SQL runs outside a transaction where a race condition could cause incorrect stock

### B. Stock Integrity — Lifecycle Traces

Trace these exact scenarios through the code. At each step, state the quantity value and cite the line:

- [ ] B1. ADD: product.qty=1 → add() → product.qty=0, cart row exists. Cite lines.
- [ ] B2. REMOVE: product.qty=0, cart row exists → remove() → product.qty=1, cart row gone. Cite lines.
- [ ] B3. EXPIRY: product.qty=0, cart row exists (>30 min old) → constructor expiry → product.qty=1, cart row gone. Cite lines.
- [ ] B4. ORDER SUCCESS: product.qty=0, cart row exists → success.php → clearCart() → product.qty=0 (stays), cart row gone. Cite lines.
- [ ] B5. ORDER CANCEL: product.qty=0, cart row gone, order exists → admin changes status to cancelled → restock fires → product.qty=1. Cite lines.
- [ ] B6. DOUBLE-ADD: user adds same product twice → second add() detects existing row → no stock change, error returned. Cite lines.
- [ ] B7. RACE: two users add same product simultaneously → first wins (qty 1→0), second gets rollback. Cite lines.
- [ ] B8. COOKIE RECOVERY: session lost, user returns with cookie → cart rows recovered, date_added refreshed. Cite lines.
- [ ] B9. LOGIN MERGE: guest has items, logs in → items claimed, duplicates handled, stock correct. Cite lines.
- [ ] B10. LOGIN MERGE DUPLICATE: guest has product X, customer already has product X → guest row deleted, stock restored, no duplicate in cart. Cite lines.

### C. Race Condition Safety

For each pair of concurrent operations, verify no data corruption:

- [ ] C1. add() vs add() (same product, different users) — FOR UPDATE prevents double-decrement
- [ ] C2. add() vs expiry cleanup (same cart row) — transaction ordering prevents conflict
- [ ] C3. remove() vs expiry cleanup (same cart row) — FOR UPDATE prevents double-restore
- [ ] C4. clear() vs expiry cleanup (same cart rows) — FOR UPDATE prevents double-restore
- [ ] C5. clearCart() vs expiry cleanup (same cart rows) — FOR UPDATE prevents expiry from restoring stock on completed order
- [ ] C6. login merge vs expiry cleanup (same guest rows) — date_added refreshed before merge prevents expiry from touching merge candidates
- [ ] C7. cookie recovery vs expiry cleanup — date_added refreshed during recovery prevents immediate expiry
- [ ] C8. add() vs add() (same product, same user/session) — FOR UPDATE on COUNT prevents duplicate cart rows

### D. Session Flags

- [ ] D1. `reservation_failed` — set in cart.php add(), read+unset in cart controller. Never stale across requests.
- [ ] D2. `reservation_sold` — same pattern.
- [ ] D3. `reservation_already_in_cart` — same pattern.
- [ ] D4. `cart_token` — stored in session, reused across add() calls within same session. New token only if empty.
- [ ] D5. `_reservation_db_init` — set once per session after schema check. Prevents repeated SHOW COLUMNS.

### E. Cache Invalidation

- [ ] E1. add() — calls cache->delete('product') on successful reservation
- [ ] E2. remove() — calls cache->delete('product') on stock restore
- [ ] E3. clear() — calls cache->delete('product') on stock restore
- [ ] E4. expiry cleanup — calls cache->delete('product') ONLY when rows were actually expired (not on every request)
- [ ] E5. clearCart() — does NOT call cache->delete (correct — stock doesn't change)

### F. SQL Consistency

- [ ] F1. Every reservation status query includes `AND api_id = '0'` (list all occurrences)
- [ ] F2. Every `INTERVAL 30 MINUTE` is consistent across all files (list all occurrences)
- [ ] F3. Every user-supplied string is escaped with $this->db->escape()
- [ ] F4. Every integer is cast with (int)
- [ ] F5. `option` column is always backtick-escaped (reserved word)

### G. Label Data Flow

For EACH of these 7 controllers, verify:
- getProductLabels() is called
- ALL 5 fields passed to template: reservation_status, in_cart, is_new, is_top_brand, has_tag_label

Controllers: category, latest, search, special, manufacturer, product.php(related), product.php(similar)

- [ ] G1-G7. One check per controller.

### H. Template States

For EACH template that shows product cards, verify ALL states are handled:
- in_cart: green checkmark button + V KOŠARICI label
- reserved: disabled grey button + REZERVIRANO label
- sold: disabled grey button + PRODANO label
- available: active button + positive labels (NOVO/Top znamka/Z etiketo)

Templates: category.twig, latest.twig, product.twig(similar), product.twig(related), product.twig(gallery+CTA)

- [ ] H1-H5. One check per template section.

### I. JavaScript

- [ ] I1. ajaxComplete hook for cart/add — extracts product_id correctly, updates all matching cards
- [ ] I2. Cart page timer — clockOffset calculated correctly, ticks per-item, stops at 0
- [ ] I3. Cart dropdown/sheet timer — tickCartDropTimers re-queries DOM, fires after AJAX reload
- [ ] I4. Recently viewed — esc() applied to ALL user data, safeId computed before use, sold filtered
- [ ] I5. Product page complete() — checks disabled before button reset
- [ ] I6. Product page success — disables button, shows label, correct class names
- [ ] I7. Checkout heartbeat — fires every 30s, immediate first call, cleanup on beforeunload

### J. Error Messages

- [ ] J1. Reserved by other → "Artikel je že rezerviran za drugo stranko." (sl-SI)
- [ ] J2. Already in cart → "Ta artikel je že v tvoji košarici." (sl-SI)
- [ ] J3. Sold → "Ta artikel je žal že prodan." (sl-SI)
- [ ] J4. English equivalents exist for all three

### K. Edge Cases

- [ ] K1. Empty cart — no errors on cart page, checkout blocked, timer hidden
- [ ] K2. Product disabled while in cart — getProducts() removes it, stock restored
- [ ] K3. Voucher-only cart — clear()/clearCart() don't affect vouchers (session data, not cart table)
- [ ] K4. Guest checkout (customer_id=0) — all cart queries work correctly
- [ ] K5. Logged-in checkout — all cart queries work correctly

---

## Output Format

```
## A. Transaction Safety
A1: PASS [add():328-365, remove():391-406, clear():412-426, clearCart():397-407, expiry:46-62]
A2: PASS [no method calls another transactional method]
...

## B. Stock Integrity
B1: PASS [add() line 334 decrements, line 340 inserts cart row]
...

## K. Edge Cases
K5: PASS [customer_id cast to int, works for both 0 and positive IDs]
```

If ANY check is FAIL, describe the exact bug, cite lines, and propose a fix.
If ALL checks are PASS, output: "CLEAN — all 50+ verification checks passed."
