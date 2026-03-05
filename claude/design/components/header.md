# Component Spec: Header

## Files
- Template: `template/common/header.twig`
- SCSS layout: `stylesheet/src/layout/_header.scss`
- SCSS nav: `stylesheet/src/layout/_nav.scss`
- SCSS buttons: `stylesheet/src/components/_button.scss`
- SCSS fonts: `stylesheet/src/base/_fonts.scss`

## DOM Structure (actual)

```
<div class="site-header">               ← sticky wrapper (z-index 100)
  <header>                              ← white bg, padding: $space-4 top/bottom
    <div class="container">
      <div class="header-inner">        ← flex row, gap: $space-6
        <div class="header-logo">       ← flex: 0 0 auto
          <div id="logo">
            <a><img /></a>              ← logo image (max-height 52px) OR
            <h1><a>name</a></h1>        ← text fallback
          </div>
        </div>
        <div class="header-search">     ← flex: 1 1 auto
          {{ search }}                  ← #search.input-group pill shape
        </div>
        <div class="header-actions">    ← flex: 0 0 auto, gap: $space-4
          <!-- Account dropdown -->
          <div class="header-action dropdown">
            <a class="header-action__btn dropdown-toggle">
              <i class="fa fa-user-o"></i>
              <span class="header-action__label">Račun</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">...</ul>
          </div>
          <!-- Wishlist -->
          <a id="wishlist-total" class="header-action header-action__btn">
            <i class="fa fa-heart-o"></i>
            <span class="header-action__label">Želje</span>
          </a>
          <!-- Cart -->
          <div class="header-action header-action--cart">
            {{ cart }}                  ← #cart.btn-group rendered by OC
          </div>
        </div>
      </div>
    </div>
  </header>
  {{ menu }}                            ← #menu.navbar (white bg, border-bottom)
</div>
```

**Note:** The `#top` dark utility bar (currency/language switchers) was intentionally removed.
The store is single-currency, single-language (Slovenian), so the bar added no value.

## Responsive Behavior
- **Desktop (sm+):** single row — logo | flex-grow search | icon actions
- **Mobile (xs):** header-inner wraps: logo + actions on row 1, search on row 2 (full width)

## Key Selectors & Styles

### `.site-header`
- `position: sticky; top: 0; z-index: 100`
- Background: `$color-surface` (white)

### `header` (main bar)
- Background: `$color-surface` (white)
- Padding: `$space-4` top/bottom

### `.header-inner`
- `display: flex; align-items: center; gap: $space-6`
- Mobile: `flex-wrap: wrap; gap: $space-3`

### Logo
- `#logo img`: `max-height: 52px; width: auto`
- `#logo h1`: fallback text — `$font-size-xl`, `$color-text-heading`, `$font-weight-heading-semi`

### Search (`#search`)
- Input (`.form-control`): `background-color: $color-surface-alt`, no right border, `border-radius: $radius-full 0 0 $radius-full`, height 46px
- Focus: gold border, white bg
- Button: `background-color: $color-primary`, `border-radius: 0 $radius-full $radius-full 0`, 46×46px circle
- Button hover: `$color-primary-dark`

### Header Actions (`.header-action__btn`)
- `display: flex; flex-direction: column; align-items: center; gap: 3px`
- Icon: `font-size: 1.375rem` (≈23px)
- Label: `font-size: 0.647rem` (≈11px), uppercase, `letter-spacing: 0.06em`
- Color: `$color-text-heading` → `$color-primary` on hover

### Account dropdown menu
- `background-color: $color-surface; border: 1px solid $color-border; border-radius: $radius-md`
- `box-shadow: $shadow-hover; min-width: 160px`
- Links: `$color-text-base` → gold hover + `$color-surface-alt` bg

### Cart widget (`.header-action--cart #cart > .btn`)
- Stripped of Bootstrap `.btn` styling: `background: none; border: none; box-shadow: none`
- Same flex-column icon+label layout as `.header-action__btn`
- `#cart-total`: same label styling (0.647rem, uppercase)
- Cart dropdown: `min-width: 340px`, `right: 0`, `$radius-md`, `$shadow-hover`
- Product rows: `$font-size-sm`, thumbnails max 48px, odd rows `$color-surface-alt`
- Totals row: `$color-surface-alt` bg, gold link text
- Empty state: `$color-text-muted`, centred

### Navigation (`#menu.navbar`)
- Background: `$color-surface` (white)
- Border: `1px solid $color-border` (bottom only); no border-radius
- Links: `$color-text-heading`, uppercase, `letter-spacing: 0.06em`, `$font-size-sm`, `$font-weight-heading-md`
- Hover/Active: `color: $color-primary`, `border-bottom: 2px solid $color-primary`
- Dropdown: white bg, `$radius-md` bottom corners, `$shadow-hover`
- Mobile: white collapse panel, `box-shadow: $shadow-card`, links with `border-bottom: 1px solid $color-border`

## Fonts (čžš support)
- Both Open Sans and DM Sans load two woff2 files per weight:
  1. `latin-ext` (covers čžš: U+0100-02BA range) — listed first
  2. `latin` (covers ASCII) — listed second
- Browser uses `unicode-range` to pick the right file per character
- Files live in `stylesheet/dist/fonts/`, copied by `npm run fonts:copy`
- 6 files total: `open-sans-latin[-ext]-{500,600}-normal.woff2`, `dm-sans-latin[-ext]-400-normal.woff2`

## Notes
- Currency/language `#top` bar was removed — single currency/language store
- `{{ wishlist-total }}` innerHTML is replaced by OC JS after page load — the `.header-action__label` "Želje" is overwritten; wishlist shows count text from OC instead
- Sticky scroll-compact behaviour (height reduction on scroll) — not yet implemented
