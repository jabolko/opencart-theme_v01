 # Component Spec: Product Card

## SCSS Partial
`stylesheet/src/components/_product-card.scss`

## BEM Block
`.product-card` (maps to OpenCart's `.product-thumb`)

## Grid View Layout
```
┌──────────────────────┐
│                      │
│      [Image]         │  ← aspect-ratio: 1/1, object-fit: cover
│                      │
├──────────────────────┤
│  [Product Name]      │  ← 2 lines max, ellipsis overflow
│  [Rating stars]      │  ← fa-star icons, color-accent
│  [Price]  [Special]  │  ← special = sale price (strikethrough original)
├──────────────────────┤
│  [Add to Cart btn]   │  ← full-width, primary button style
│  [Wishlist] [Compare]│  ← icon buttons, secondary
└──────────────────────┘
```

## List View Layout (when user switches to list mode)
```
┌────────┬──────────────────────────────────────────┐
│        │  [Product Name — larger]                  │
│ [Img]  │  [Description — 3 lines max]              │
│        │  [Rating]                                 │
│        │  [Price]   [Add to Cart btn]              │
└────────┴──────────────────────────────────────────┘
```

## Sale Badge
- Positioned top-left over image
- Text: "Akcija" (Slovenian for sale)
- Uses `color-accent` background, white text
- Only visible when `product.special` is set

## Hover State
- Image: subtle scale(1.03) transform
- Card: shadow lifts from `shadow-card` to `shadow-hover`
- Transition: `transition-base`

## Responsive
- Grid: 2 columns on mobile, 3 on tablet, 4 on desktop
- List: always single column

## OpenCart Variables Used
- `product.thumb` — image URL
- `product.name` — product name
- `product.href` — product page URL
- `product.price` — formatted price (includes currency symbol)
- `product.special` — sale price (empty string if no sale)
- `product.rating` — integer 0–5

## Notes
- OpenCart renders this via `product/product_thumb.twig` — check if theme overrides it
- The `.button-group` div wraps wishlist and compare buttons (OC markup)
