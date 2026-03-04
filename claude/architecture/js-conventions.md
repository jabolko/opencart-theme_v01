# JavaScript Conventions

## Environment
- jQuery 3.7.1 is loaded by OpenCart before our script — use `$` freely
- Bootstrap 3 JS is loaded by OC — tooltips, modals, dropdowns available
- Our file: `javascript/src/theme.js` → compiled to `javascript/dist/theme.min.js`

## Rules
- Wrap all code in `$(document).ready(function() { ... })` or `$(function() { ... })`
- Never load jQuery again — it is already available globally
- Write ES5 only: use `var`, not `let`/`const`; use `function()`, not arrow functions
- Namespace all custom functions: `window.OKTheme = window.OKTheme || {};`
- No ES6+ modules — everything goes in a single file

## Namespacing Pattern
```javascript
window.OKTheme = window.OKTheme || {};

OKTheme.header = {
  init: function() {
    // sticky header logic
  }
};

OKTheme.productCard = {
  init: function() {
    // product card interactions
  }
};

$(function() {
  OKTheme.header.init();
  OKTheme.productCard.init();
});
```

## OpenCart JS Globals Available
- `cart` — `cart.add(product_id, quantity)`, `cart.remove(key)`, `cart.update(key, quantity)`
- `wishlist` — `wishlist.add(product_id)`, `wishlist.remove(product_id)`
- `compare` — `compare.add(product_id)`, `compare.remove(product_id)`

## Event Hooks (OC jQuery events)
```javascript
// Cart updated (after add/remove/update)
$(document).on('cart.update', function() {
  // e.g., update mini-cart display
});
```

## File Structure (theme.js sections)
Organize by section comments in this order:

```javascript
/* ============================================================
   SECTION: Utilities
   ============================================================ */

/* ============================================================
   SECTION: Header (sticky, mobile menu)
   ============================================================ */

/* ============================================================
   SECTION: Product Card (hover states, gallery)
   ============================================================ */

/* ============================================================
   SECTION: Category Page (grid/list toggle, filter)
   ============================================================ */

/* ============================================================
   SECTION: Product Page (image gallery, quantity input)
   ============================================================ */

/* ============================================================
   SECTION: Cart (quantity update, totals refresh)
   ============================================================ */

/* ============================================================
   SECTION: Init (call all init functions)
   ============================================================ */
$(function() {
  OKTheme.header.init();
  // etc.
});
```

## Notes
- JS changes are NOT watched by `npm run dev` — run `npm run js:minify` manually after changes
- Use `data-*` attributes for passing server-side values to JS (e.g., `data-product-id`)
- Avoid inline `onclick` handlers in Twig — use jQuery `.on()` event delegation instead
