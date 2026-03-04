# Component Spec: Product Page

## Template
`opencart/catalog/view/theme/otroskikoticek/template/product/product.twig`

## SCSS Partial
`stylesheet/src/pages/_product.scss`

## Page Layout
```
┌─────────────────────────────────────────────────────┐
│  [Breadcrumb]                                       │
├──────────────────────┬──────────────────────────────┤
│                      │  [Product Name — h1]         │
│  [Main Image]        │  [Rating + Reviews count]    │
│                      │  [Price] / [Special price]   │
│  [Thumbnail strip]   │  [Product code / SKU]        │
│                      │  ─────────────────────────── │
│                      │  [Options (size, color etc)]  │
│                      │  [Quantity input]             │
│                      │  [Add to Cart btn — primary] │
│                      │  [Wishlist] [Compare]         │
├──────────────────────┴──────────────────────────────┤
│  [Description tab] [Reviews tab] [Tags tab]         │
│  ─────────────────────────────────────────────────  │
│  [Tab content]                                      │
└─────────────────────────────────────────────────────┘
```

## Image Gallery
- Main image: large, with zoom on hover (CSS transform or lightbox)
- Thumbnails: horizontal strip below main image
- Click thumbnail → swap main image (JS)
- Mobile: thumbnails become a dot indicator for swipe carousel

## Quantity Input
- Number input with +/- buttons flanking it
- Enforces `minimum` quantity from `{{ product.minimum }}`
- Integer only

## Add to Cart
- Full-width primary button on mobile
- Inline (not full-width) on desktop
- Disabled state if out of stock

## Price Display
- Regular price: `font-size-xl`, `color-text-base`
- Special (sale) price: `color-primary`, `font-size-2xl`, bold
- Old price (when on sale): `color-text-muted`, strikethrough, `font-size-base`

## Tabs
- Description, Tags, Reviews
- Tabs styled as underline indicators (not box tabs)
- Active tab: `color-primary` underline

## Responsive
- Two-column layout (image | details) on desktop/tablet
- Single column, image on top, details below on mobile

## OpenCart Variables Used
- `heading_title` — product name
- `thumb` — main image URL
- `images` — array of thumbnail objects `{popup, thumb}`
- `price` — formatted regular price
- `special` — sale price (empty if none)
- `description` — full HTML description
- `options` — product options array (size, color, etc.)
- `minimum` — minimum order quantity
- `reviews` — rendered reviews section
- `tags` — array of tag links
