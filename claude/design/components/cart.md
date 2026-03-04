# Component Spec: Cart Page

## Template
`opencart/catalog/view/theme/otroskikoticek/template/checkout/cart.twig`

## SCSS Partial
`stylesheet/src/pages/_cart.scss`

## Page Layout
```
┌───────────────────────────────────────────────────-------──┐
│  [Page Title: "Moja košarica"]                             │
├──────────────────────────────────┬────-------──────────────┤
│                                          │                 │
│  [Cart Items Table]                      │  [Order Summary]│
│  ───────────────────────────────---------│  ────────────── │
│  [Image] [Name] [Brand] [Price] [Qty]    │  Subtotal: X    │
│                       [Remove]           │  Shipping: X    │
│                                          │  Total: X       │
│  [Coupon code input]  [Apply]            │                 │
│                                          │  [Checkout btn] │
│                                          │  [Continue btn] │
└──────────────────────────────────------- ┴─────────────────┘
```

## Cart Table
- Columns: Image | Product | Unit Price | Quantity | Total | Remove
- On mobile: collapse to stacked card layout (no table)
- Quantity: inline input with +/- buttons, auto-updates totals via AJAX

## Quantity Update
- Change quantity → AJAX call → totals update without full page reload
- Loading state on totals area during update

## Remove Item
- Red/error color trash icon button
- No confirmation dialog (immediate removal with undo toast optional)

## Order Summary Card
- Sticky on desktop while scrolling the cart table
- Background: `color-surface-alt`
- Border radius: `radius-md`
- Shadow: `shadow-card`

## Checkout Button
- Full-width primary button
- Text: "Nadaljuj na plačilo" (Continue to payment)

## Continue Shopping
- Secondary/ghost button or text link
- Text: "Nadaljuj z nakupovanjem"

## Empty Cart State
- Centered illustration or icon
- Message: "Vaša košarica je prazna"
- Button: "Začni z nakupovanjem" → links to homepage or featured category

## OpenCart Variables Used
- `products` — cart items array with `{key, thumb, name, model, option, quantity, price, total, href, remove}`
- `vouchers` — applied vouchers
- `coupon` — coupon form rendered HTML
- `totals` — totals array `{title, text}` (subtotal, shipping, total)
- `checkout` — checkout page URL
- `continue` — continue shopping URL
