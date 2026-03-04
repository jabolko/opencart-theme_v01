# Component Spec: Footer

## Template
`opencart/catalog/view/theme/otroskikoticek/template/common/footer.twig`

## SCSS Partial
`stylesheet/src/layout/_footer.scss`

## Layout Structure
```
┌─────────────────────────────────────────────────────┐
│  [Logo]  [Short store description / tagline]        │  ← Brand row
├──────────┬──────────┬──────────┬────────────────────┤
│ Info     │ Customer │ Account  │ Contact            │  ← Link columns
│ ──────── │ ──────── │ ──────── │ ────────────────── │
│ About Us │ Search   │ Register │ Address            │
│ Delivery │ Wishlist │ Login    │ Phone              │
│ Privacy  │ Returns  │ Orders   │ Email              │
│ T&C      │          │          │                    │
├─────────────────────────────────────────────────────┤
│  © 2026 Otroski Koticek. Vse pravice pridržane.     │  ← Copyright bar
└─────────────────────────────────────────────────────┘
```

## Responsive
- Desktop: 4-column grid (columns as above)
- Tablet: 2-column grid
- Mobile: single column, each section as accordion (expand/collapse)

## Color Scheme
- Dark background: `color-text-base` or slightly lighter dark tone
- Text: light gray / off-white
- Links: off-white, hover → `color-primary` tint or white
- Section headings: white, uppercase, `font-size-sm`, letter-spacing

## Brand Row
- Logo: inverted/white version if possible, or full-color on dark bg
- Tagline: short (1 sentence max), muted color

## Copyright Bar
- Thinner, darker strip at the very bottom
- Smaller font size (`font-size-sm`)

## Notes
- Footer link columns are rendered via `{{ information }}` and similar OC variables
- The footer is a static layout — OC does not provide a footer widget like it does for the header
- Contact details are hardcoded in the template or pulled from store settings
