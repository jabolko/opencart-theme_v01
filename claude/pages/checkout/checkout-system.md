# Checkout System — Implementation Plan

> Last updated: 2026-04-08

## Overview

Custom checkout for a Slovenian children's second-hand clothing store (otroskikoticek).
V1 accordion design with merged steps, guest-first flow, conversion-optimized UI.
Single-page checkout with pills nav (guest/register/login), auto-copy billing to shipping,
progress bar, total bar, trust strip, and reservation-aware confirm step.

---

## Architecture Decisions

- **Guest-first**: guest checkout is default, login/register as secondary options via pills nav
- **Merged steps**: guest info + address combined into one panel (OC default has 3 separate steps)
- **Auto-copy**: billing address auto-copies to shipping (single-address store, Slovenia only)
- **Duplicate prevention**: checks existing addresses before creating new ones on re-submit
- **PHP 8.4 safe**: all hidden fields (company, address_2) use isset() guards
- **Step numbers removed**: header text uses direct labels without "Korak X:" prefix
- **Slovenian Ti form**: all checkout text uses informal "ti" instead of formal "vi"

---

## Core File Modifications

### File 1: `catalog/controller/checkout/checkout.php`

**What changed:**
1. Step header text: removed `sprintf()` with `%s` step numbers — now plain `$this->language->get()`
2. Cart summary for total bar: added cart_count + cart_total computed from `$this->cart->getProducts()`
3. `updateCartTime()` — reservation heartbeat (documented in reservation system)

**Why:** OC default passes step numbers in header text ("Step 1: ..."). Our V1 accordion doesn't use numbered steps. Cart total bar needs product count and formatted total.

### File 2: `catalog/controller/checkout/confirm.php`

**What changed:**
1. Added `model/tool/image` and `model/catalog/product` loading
2. Added `image` (80x80 thumbnail) and `manufacturer` to each product in confirm data
3. Added `shipping_address_summary` — formatted string for address display
4. Added `shipping_title` and `payment_title` for confirm step display
5. Removed some comments (cleanup)

**Why:** OC default confirm step only has product name/model/price. Our V1 card design shows product images and brand. Address summary needed for the review step.

### File 3: `catalog/controller/checkout/guest.php`

**What changed:**
PHP 8.4 compatibility fix — 4 lines changed:
- Line 256: `$this->request->post['company']` → `isset(...) ? ... : ''`
- Line 258: `$this->request->post['address_2']` → `isset(...) ? ... : ''`
- Line 307: same for shipping address company
- Line 309: same for shipping address address_2

**Why:** Our checkout hides company and address_2 fields via CSS (`:has()` selector). These fields are never submitted in the POST. PHP 8.4 strict mode throws warnings on undefined array keys.

### File 4: `catalog/controller/checkout/payment_address.php`

**What changed:**
1. Duplicate address prevention: before calling `addAddress()`, checks all existing addresses for matching firstname+lastname+address_1+city+postcode+country_id
2. Auto-copy to shipping: `$this->session->data['shipping_address'] = $this->session->data['payment_address']`

**Why:**
- OC calls `addAddress()` on every "new address" submit, creating duplicates
- Our checkout doesn't have a separate shipping address step — billing auto-copies to shipping in PHP (eliminated redundant JS AJAX call to shipping_address/save)

### File 5: `catalog/model/account/address.php`

**What changed:**
PHP 8.4 fix — `isset()` guards on `company` and `address_2` in both `addAddress()` and `editAddress()` SQL queries.

**Why:** Same as guest.php — hidden fields not submitted, PHP 8.4 warnings.

### File 6: `catalog/language/sl-SI/checkout/checkout.php`

**What changed:**
Complete Slovenian translation (98 lines added). Step headers without numbers, Ti form throughout, "Ulica in hisna stevilka" for address_1 placeholder.

**Why:** OC ships with English only for checkout. Full Slovenian localization needed.

---

## Theme Files (not OCMOD)

### Templates
| File | What |
|------|------|
| `checkout.twig` | Complete rewrite: V1 accordion, pills nav, progress bar, total bar, trust strip, confirm proxy button, payment icon rotation, skeleton loading, auto-scroll alerts, tab order fix, heartbeat JS |
| `confirm.twig` | Product cards with images + manufacturer, address summary, shipping/payment display |
| `payment_address.twig` | Custom form layout |
| `payment_method.twig` | Custom radio/checkbox styling |
| `register.twig` | Custom form with password fields + checkbox layout |
| `shipping_address.twig` | Custom form layout |
| `shipping_method.twig` | Custom radio styling |

### SCSS
| File | What |
|------|------|
| `_checkout.scss` | Complete checkout styling: header, progress bar, total bar, panels, radios, checkboxes, pills, done summaries, trust strip, form grid, skeleton, spinner, address bar, field hints |
| `_bootstrap-overrides.scss` | Hidden fields globally: `.form-group:has(#input-country)`, etc. |

### JavaScript (in checkout.twig inline)
- Pills nav (guest/register/login)
- Done summaries for completed steps
- Address bar display
- Skeleton loading on panel transitions
- Confirm proxy button with loading spinner
- Payment icon rotation (mobile, 3 at a time)
- Auto-scroll to alerts (MutationObserver)
- Tab order fix for CSS Grid reordered fields
- Phone hint text injection
- `escapeHtml()` for XSS protection on DOM-injected content
- `$.ajaxSetup({ timeout: 30000 })` for global AJAX timeout

---

## Hidden Fields (CSS approach)

Instead of removing fields from PHP (which would break OC's validation), we hide them via CSS `:has()`:

```scss
.form-group:has(#input-country),
.form-group:has(#input-zone),
.form-group:has(#input-company),
.form-group:has(#input-address-2) {
  display: none;
}
```

Country defaults to Slovenia (ID=190) and zone to Ljubljana via OC admin settings. The fields are present in DOM with default values but invisible to users.

---

## Form Field Reorder (CSS Grid)

Checkout address fields are reordered visually using CSS Grid + `display: contents`:

```scss
#account, #address, fieldset { display: contents; }
.form-group:has(#input-payment-firstname) { grid-column: 1; order: 1; }
.form-group:has(#input-payment-lastname) { grid-column: 2; order: 2; }
// ... etc
```

Tab order fixed via `tabindex` attributes set by JS after form loads.

---

## Error Handling

- All OC `alert()` popups replaced with inline alerts (`alert-danger` divs)
- Auto-scroll to error via MutationObserver
- Toast suppressed on checkout page (`if (document.querySelector('.ck-page')) return;`)
- Login errors scoped to panel (`$('#collapse-payment-address .alert-dismissible').remove()`)

---

## Overlap with Reservation System

checkout.php has `updateCartTime()` which belongs to the reservation system (OCMOD 2). When generating OCMODs, this method should be in the reservation OCMOD, not the checkout OCMOD. The remaining checkout.php changes (step headers + cart summary) go in the checkout OCMOD.

### Heartbeat timing issue (2026-04-13)

The checkout template fires the initial heartbeat `$.post('updateCartTime')` immediately on `$(document).ready()`, simultaneously with the guest/payment_address form AJAX. Both requests trigger the Cart constructor, both attempt the reservation expiry cleanup, causing MySQL lock conflicts.

**Fix (in reservation rework):** Delay the initial heartbeat call by 3 seconds. The 30-second interval continues normally. See `claude/pages/reservation/constructor-rework-plan.md` for full details.

### Concurrent AJAX calls that trigger Cart constructor

| Checkout moment | Simultaneous AJAX calls |
|---|---|
| Page load | `GET checkout/guest` + `POST updateCartTime` |
| Guest save (same address) | `GET shipping_method` + `GET guest_shipping` |
| Payment address save (logged in) | `GET shipping_method` + `GET shipping_address` + `GET payment_address` |
| Register save | `GET shipping_method` + `GET shipping_address` + `GET payment_address` |
| Any step + heartbeat | Current AJAX + heartbeat POST |

Each of these AJAX calls creates a new Cart object on the server (startup.php line 209), running the constructor's expiry cleanup. The reservation system rework (SKIP LOCKED + rate limiting) ensures these concurrent constructors don't conflict.

---

## OCMOD Generation Checklist

```
1. Separate reservation changes from checkout changes in checkout.php:
   - updateCartTime() → reservation OCMOD 2
   - Step headers + cart summary → checkout OCMOD
2. Generate OCMOD for each file:
   - checkout.php (step headers + cart summary only)
   - confirm.php (images + manufacturer + address summary)
   - guest.php (isset guards)
   - payment_address.php (duplicate check + auto-copy)
   - address.php (isset guards)
   - Language file (sl-SI translation)
3. Test: install OCMOD, clear cache, run full checkout (guest + logged-in)
4. Verify: PHP error log clean on PHP 8.4
```
