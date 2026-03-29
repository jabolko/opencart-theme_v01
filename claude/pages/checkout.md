# PAGE-005 — Checkout Page

## Design
V1 Accordion — restyled OC panels with numbered dots, progress bar, order summary sidebar.
Prototype: `prototypes/checkout-designs.html` (V1 tab)

## Strategy
**CSS-only restyle + minimal Twig overrides.** Keep OC's 800-line checkout JS 100% intact.
Only change the HTML shell and sub-templates. Never touch button IDs, panel IDs, or AJAX endpoints.

---

## OC Checkout Contract (DO NOT BREAK)

These are the critical selectors, IDs, and structures that OC's `checkout.twig` JS depends on.
**Every item here must exist in our custom templates or checkout will silently break.**

### Panel IDs (AJAX content targets)

| Panel ID | JS writes to | Loaded template |
|----------|-------------|-----------------|
| `#collapse-checkout-option` | `.panel-body` inside it | `login.twig` |
| `#collapse-payment-address` | `.panel-body` inside it | `guest.twig` / `register.twig` / `payment_address.twig` |
| `#collapse-shipping-address` | `.panel-body` inside it | `guest_shipping.twig` / `shipping_address.twig` |
| `#collapse-shipping-method` | `.panel-body` inside it | `shipping_method.twig` |
| `#collapse-payment-method` | `.panel-body` inside it | `payment_method.twig` |
| `#collapse-checkout-confirm` | `.panel-body` inside it | `confirm.twig` |

### Required DOM Structure Per Panel

```html
<div>  <!-- any wrapper, JS uses .parent() from panel-collapse -->
  <div class="panel-heading">
    <h4 class="panel-title">...</h4>  <!-- JS replaces innerHTML with <a> -->
  </div>
  <div class="panel-collapse collapse" id="collapse-XXXXX">
    <div class="panel-body"></div>  <!-- AJAX HTML injected here -->
  </div>
</div>
```

JS does: `$('#collapse-X').parent().find('.panel-heading .panel-title').html(...)` — parent must contain `.panel-heading > .panel-title`.

JS does: `$('a[href=\'#collapse-X\']').trigger('click')` — Bootstrap collapse needs `data-toggle="collapse"` and `data-parent="#accordion"` on the `<a>`.

### Button IDs (JS delegates click handlers)

| Button ID | In template | Action |
|-----------|------------|--------|
| `#button-account` | `login.twig` | Selects guest/register, loads next step |
| `#button-login` | `login.twig` | POST to `checkout/login/save` |
| `#button-guest` | `guest.twig` | POST to `checkout/guest/save` |
| `#button-register` | `register.twig` | POST to `checkout/register/save` |
| `#button-guest-shipping` | `guest_shipping.twig` | POST to `checkout/guest_shipping/save` |
| `#button-shipping-address` | `shipping_address.twig` | POST to `checkout/shipping_address/save` |
| `#button-shipping-method` | `shipping_method.twig` | POST to `checkout/shipping_method/save` |
| `#button-payment-method` | `payment_method.twig` | POST to `checkout/payment_method/save` |

### Form Field Names (backend expects these exact names)

#### guest.twig (billing/contact)
| Name | Required | Notes |
|------|----------|-------|
| `customer_group_id` | Radio | Hidden if 1 group |
| `firstname` | Yes | |
| `lastname` | Yes | |
| `email` | Yes | |
| `telephone` | Yes | |
| `company` | No | We hide this (CSS) |
| `address_1` | Yes | |
| `address_2` | No | We hide this (CSS) |
| `city` | Yes | |
| `postcode` | Yes | |
| `country_id` | Yes | Pre-select Slovenia (182), hide |
| `zone_id` | Yes | Pre-select, hide |
| `shipping_address` | Checkbox | "Same as billing" — default checked |
| `custom_field[location][id]` | Varies | Keep if any exist |

#### shipping_method.twig
| Name | Required | Notes |
|------|----------|-------|
| `shipping_method` | Radio | Value = `flat_rate.flat_rate` etc. |
| `comment` | Textarea | We can hide/remove — optional |

#### payment_method.twig
| Name | Required | Notes |
|------|----------|-------|
| `payment_method` | Radio | Value = `cod`, `bank_transfer` etc. |
| `comment` | Textarea | We can hide/remove — optional |
| `agree` | Checkbox | T&C — required if `text_agree` set |

### AJAX Endpoints (for reference, we do NOT change these)

| URL | Method | Returns |
|-----|--------|---------|
| `checkout/login` | GET | HTML (login.twig) |
| `checkout/login/save` | POST | JSON (redirect or error) |
| `checkout/guest` | GET | HTML (guest.twig) |
| `checkout/guest/save` | POST | JSON (redirect, error, or next step trigger) |
| `checkout/register` | GET | HTML (register.twig) |
| `checkout/register/save` | POST | JSON |
| `checkout/shipping_address` | GET | HTML |
| `checkout/guest_shipping` | GET | HTML |
| `checkout/guest_shipping/save` | POST | JSON |
| `checkout/shipping_method` | GET | HTML (shipping_method.twig) |
| `checkout/shipping_method/save` | POST | JSON |
| `checkout/payment_method` | GET | HTML (payment_method.twig) |
| `checkout/payment_method/save` | POST | JSON |
| `checkout/confirm` | GET | HTML (confirm.twig) |
| `checkout/checkout/country&country_id=X` | GET | JSON (zones) |
| `checkout/checkout/customfield&customer_group_id=X` | GET | JSON |

### JS Error Selectors (used for validation feedback)

| Selector | Purpose |
|----------|---------|
| `.alert-dismissible` | Removed on each step submit |
| `.text-danger` | Removed on each step submit |
| `.form-group.has-error` | Class removed, re-added per field |
| `#input-payment-{field}` | Error message appended after this element |
| `#input-shipping-{field}` | Same for shipping address |

---

## Implementation Checklist

### Phase 1: Shell + Styles (mobile first)
- [ ] Create `template/checkout/checkout.twig` — progress bar + accordion + sidebar
- [ ] Create `stylesheet/src/pages/_checkout.scss` — all checkout styles
- [ ] Add `@import 'pages/checkout'` to `theme.scss`
- [ ] Verify: page loads, step 1 appears, no JS errors in console

### Phase 2: Sub-templates
- [ ] Create `login.twig` — guest-first, "Imam račun" secondary
- [ ] Verify: guest/register toggle works, button-account triggers next step
- [ ] Create `guest.twig` — 6 visible fields, country/zone hidden+preselected
- [ ] Verify: form submits, validation errors show, next step opens
- [ ] Create `shipping_method.twig` — radio cards, hide comments
- [ ] Verify: shipping options load, selection saves, next step opens
- [ ] Create `payment_method.twig` — radio cards with icons, T&C checkbox
- [ ] Verify: payment options load, agree checkbox works, next step opens
- [ ] Create `confirm.twig` — mini product cards, clean totals, CTA
- [ ] Verify: order summary correct, payment template loads, order can be placed

### Phase 3: Controller + Sidebar
- [ ] Override `controller/checkout/checkout.php` — pass cart products to template
- [ ] Order summary sidebar (desktop: sticky, mobile: collapsible top)
- [ ] Verify: sidebar shows correct items and totals

### Phase 4: Desktop + Polish
- [ ] Desktop two-column layout (form left, sticky sidebar right)
- [ ] Sticky mobile CTA footer
- [ ] Trust badges, payment icons
- [ ] Progress bar animation on step change
- [ ] Lighthouse audit

### Phase 5: Testing
- [ ] Full guest checkout flow (place real test order)
- [ ] Full login checkout flow
- [ ] Validation errors display correctly per field
- [ ] Shipping address different from billing
- [ ] All payment methods render correctly
- [ ] Mobile: all steps usable, no overflow, sticky CTA visible
- [ ] Desktop: sidebar sticky, form readable, no layout breaks

---

## Debugging Guide

### Checkout step not loading?
1. Open browser console — look for AJAX 404 or 500 errors
2. Check the panel ID exists in HTML: `document.getElementById('collapse-payment-address')`
3. Check `.panel-body` exists inside it: `$('#collapse-payment-address .panel-body').length`
4. Check parent structure: `$('#collapse-payment-address').parent().find('.panel-heading .panel-title').length`

### Form not submitting / validation not showing?
1. Check button ID matches contract: e.g. `#button-guest` must exist inside guest.twig
2. Check form field `name` attributes match contract (e.g. `firstname`, not `first_name`)
3. Check `#input-payment-{field}` IDs exist — error messages are appended after these
4. Check `.form-group` wrapper exists — `has-error` class is toggled on it

### Step transition not happening?
1. JS triggers collapse via: `$('a[href=\'#collapse-shipping-method\']').trigger('click')`
2. This needs: an `<a>` tag with `data-toggle="collapse"` and `data-parent="#accordion"`
3. The JS creates this `<a>` dynamically — it replaces `.panel-title` innerHTML
4. Check `#accordion` wrapper exists with that exact ID

### Country/Zone not loading?
1. `country_id` select triggers AJAX to `checkout/checkout/country&country_id=X`
2. Response populates `zone_id` select
3. Both selects must exist in DOM with exact `name="country_id"` and `name="zone_id"`
4. Even if hidden, they must have correct values selected

---

## Fields We Hide (CSS only, NOT removed from HTML)

These fields must stay in the DOM for OC backend validation. We hide them with CSS.
- `company` — `display: none`
- `address_2` — `display: none`
- `country_id` — `display: none` (pre-selected via Twig to Slovenia = 182)
- `zone_id` — `display: none` (pre-selected to appropriate zone)
- `customer_group_id` — already hidden by OC when single group
- `comment` textarea (shipping_method + payment_method) — `display: none`

## Fields We Show
| Order | Field | Label (SL) |
|-------|-------|------------|
| 1 | `email` | E-naslov |
| 2 | `firstname` | Ime |
| 3 | `lastname` | Priimek |
| 4 | `telephone` | Telefon |
| 5 | `address_1` | Ulica in hišna številka |
| 6 | `postcode` + `city` | Pošta + Mesto (same row) |
