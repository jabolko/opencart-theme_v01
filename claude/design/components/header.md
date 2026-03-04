# Component Spec: Header

## Template
`opencart/catalog/view/theme/otroskikoticek/template/common/header.twig`

## SCSS Partial
`stylesheet/src/layout/_header.scss`

## Layout Structure
```
<header>
  ┌─────────────────────────────────────────────────────┐
  │  [Logo]          [Search bar]          [Cart] [User] │  ← Top bar
  ├─────────────────────────────────────────────────────┤
  │  [Navigation menu — horizontal categories]          │  ← Nav bar
  └─────────────────────────────────────────────────────┘
```

## Responsive Behavior
- **Desktop (lg+):** Logo left, search center, cart/user icons right. Nav bar below.
- **Tablet (md):** Logo left, cart/user icons right. Search collapses to icon. Nav bar below.
- **Mobile (xs/sm):** Logo left, hamburger menu right. Search in collapsed menu. Cart visible.

## Key Elements

### Logo
- Uses `{{ logo }}` variable for image URL
- Wrapped in link to `{{ home }}`
- Max height: 60px on desktop, 48px on mobile
- Alt text: `{{ name }}` (store name)

### Search Bar
- Rendered via `{{ search }}` widget (OC-provided)
- Style the wrapper `.search` class
- Full-width on mobile when open

### Cart Icon
- Rendered via `{{ cart }}` widget (OC-provided)
- Shows item count badge when cart is not empty
- Badge uses `color-accent` token

### Navigation
- Rendered via `{{ menu }}` widget (OC-provided)
- Style `.navbar` and `.navbar-nav` classes
- Dropdown menus on hover (desktop), tap (mobile)

## States
- Sticky on scroll: header becomes compact (reduced height) after scrolling 80px
- Mobile menu: full-screen overlay when hamburger is clicked

## Notes
- Do not call external fonts — use system UI stack from design-system.md
- The `<header>` element wraps the top bar; `#top` is the Bootstrap navbar wrapper
