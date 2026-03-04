# Component Spec: Category Page

## Template
`opencart/catalog/view/theme/otroskikoticek/template/product/category.twig`

## SCSS Partial
`stylesheet/src/pages/_category.scss`

## Page Layout
```
┌─────────────────────────────────────────────────────┐
│  [Breadcrumb]                                       │
├─────────────────────────────────────────────────────┤
│  [Category Image]  [Category Title]                 │
│  [Category Description]                             │
├─────────────────────────────────────────────────────┤
│  [Sub-category pills/chips]                         │
├──────────────────────────┬──────────────────────────┤
│  Sort by: [dropdown]     │  Show: [limit dropdown]  │
│  [Grid/List toggle]      │  [Results count text]    │
├─────────────────────────────────────────────────────┤
│  [Product Card] [Product Card] [Product Card]       │  ← Grid
│  [Product Card] [Product Card] [Product Card]       │
├─────────────────────────────────────────────────────┤
│  [Pagination]                                       │
└─────────────────────────────────────────────────────┘
```

## Sub-category Display
- Render as horizontal scrollable chips on mobile
- Render as wrap of chips on desktop
- Style: pill-shaped, border, subtle background, hover fills with `color-primary`

## Sort/Filter Bar
- Sticky on desktop when scrolling (stays below header)
- On mobile: collapses to "Sort & Filter" button that opens a bottom sheet

## Grid/List Toggle
- Two icon buttons: grid icon, list icon
- Active state: `color-primary` fill
- Saves preference to localStorage

## Pagination
- Uses OC's rendered `{{ pagination }}` widget
- Style `.pagination` Bootstrap class overrides
- Current page: `color-primary` background

## OpenCart Variables Used
- `heading_title` — category name
- `breadcrumbs` — array of `{text, href}`
- `thumb` — category image URL
- `description` — category description (raw HTML from admin)
- `categories` — sub-category array `{name, href}`
- `products` — product objects array (see product-card.md)
- `sorts` — sort options
- `limits` — per-page options
- `pagination` — rendered HTML
- `results` — "Showing X to Y of Z" string

## Notes
- `{{ description }}` is raw HTML from admin — do not escape it
- Product grid uses Bootstrap `.row` + `.col-*` classes
- Grid columns: `col-xs-6 col-sm-4 col-md-3` (2 mobile, 3 tablet, 4 desktop)
