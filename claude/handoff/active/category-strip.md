# Category Strip (S3) — Design Handoff

## Status

| | |
|---|---|
| Design | COMPLETE |
| Prototype file | `prototypes/category-strip-v1.html` |
| Chosen variation | — AWAITING USER DECISION — |
| Implementation | PENDING |
| Implemented by | — |
| Date completed | — |

---

## What This Component Does

The category strip is the first section below the hero. It displays the 5 main store categories
(Deklice, Fantje, Mamice, Znamke, Novo prispelo) as visual tiles. Its job is to immediately route
shoppers to the section most relevant to them.

---

## Chosen Design

**Variation:** [USER TO PICK: V1, V2, or V3]

### V1 — "Photo Cards"
Full-bleed tiles, dark overlay, white text pinned bottom-left. 3px gap, no border-radius.
Editorial, photo-forward. Needs real category photos to look its best.

### V2 — "Split Tile"
No photos needed. Colour top zone (60%) with giant ghost letter, white bottom (40%) with text.
12px radius, 16px gap, gold bottom border on hover. Works great right now without photos.

### V3 — "Editorial Row"
Portrait tiles (2:3 ratio), magazine-style. Decorative numbers 01–05, gold left border rhythm.
`translateY` micro-interaction on hover. Most distinctive visually, best with photos.

**Why Opus recommended V1:** Full-bleed photo tiles have the most visual impact for a lifestyle store.
The dark overlay technique is proven for text contrast. When real photos arrive, it will feel premium.
V2 is the safe fallback if photos are delayed.

**User decision needed:** Pick V1, V2, or V3 — or mix elements (e.g. V1 grid + V2 ghost letter).

---

## Twig Location

- **Template file:** `template/common/home.twig`
- **Section class:** `.home-categories`
- **Position:** After `.home-hero`, before `.home-latest`

---

## SCSS Location

- **Partial:** `stylesheet/src/pages/_home.scss`
- **BEM block:** `.home-categories`

---

## Implementation Notes for Sonnet

### If V1 (Photo Cards):
- [ ] CSS Grid: 5 equal columns, `gap: 3px`, no border-radius on tiles
- [ ] `aspect-ratio: 4 / 3` on each tile
- [ ] `background-color: var(--cat-bg)` as colour fallback; future photos via `background-image`
- [ ] Dark overlay via `::before` pseudo-element: `rgba(0,0,0,0.30)`, `z-index: 1`
- [ ] Text block: `position: absolute; bottom: 0; left: 0; z-index: 2; padding: $space-6 $space-6`
- [ ] Hover: `transform: scale(1.025)` on tile, `z-index: 2`, overlay darkens to `rgba(0,0,0,0.50)`
- [ ] "Novo prispelo" tile: `::before` uses gold overlay `rgba(249,185,74,0.35)` not dark; text `#2b2e35`
- [ ] Gold "NOVO" badge pill: top-right corner, `position: absolute; z-index: 3`
- [ ] Mobile (below 768px): 2 columns, last tile `grid-column: 1 / -1`

### If V2 (Split Tile):
- [ ] CSS Grid: 5 equal columns, `gap: $space-4`, `border-radius: $radius-lg` on tiles
- [ ] `aspect-ratio: 4 / 3`, `overflow: hidden`
- [ ] Top zone: `flex: 0 0 60%`, `background: var(--cat-bg)`, centered ghost letter
- [ ] Ghost letter: 140px, `color: rgba(255,255,255,0.35)`, `user-select: none`
- [ ] Bottom zone: `flex: 0 0 40%`, white bg, category name + price
- [ ] Hover: `border-bottom: 3px solid $color-primary` + `box-shadow: $shadow-hover`
- [ ] Section background: `$color-surface-alt` (#f7f7f7) with `padding: $space-12 $space-6`

### If V3 (Editorial Row):
- [ ] CSS Grid: 5 equal columns, `gap: $space-2`
- [ ] `aspect-ratio: 2 / 3` (portrait), `border-radius: $radius-md`, `overflow: hidden`
- [ ] Background: `background-color: var(--cat-bg)` (colour fills + future photos)
- [ ] Gold left border: `border-left: 2px solid $color-primary`, expands to `4px` on hover
- [ ] Decorative number: `position: absolute; top: 16px; right: 18px; font-size: 100px; color: rgba(255,255,255,0.18)`
- [ ] Text: bottom-left, `z-index: 2`; name uses `text-shadow` for readability on any bg
- [ ] Hover name: `transform: translateY(-4px)`
- [ ] "Novo prispelo" gold tile: `background-color: $color-primary`, text `$color-text-heading`

---

## Design Tokens Used

| Token | Variable | Value | Used for |
|-------|----------|-------|----------|
| color-primary | `$color-primary` | #f9b94a | Badge bg, gold tile, left border (V3), hover border (V2) |
| color-text-heading | `$color-text-heading` | #2b2e35 | Text on gold tile, category name (V2) |
| color-text-on-dark | `$color-text-on-dark` | #f5f5f5 | Category name (V1, V3) |
| color-text-muted | `$color-text-muted` | #9aa0af | Price line |
| color-surface-alt | `$color-surface-alt` | #f7f7f7 | V2 section background |
| shadow-hover | `$shadow-hover` | 0 4px 16px rgba(0,0,0,0.12) | V2 tile hover |
| radius-lg | `$radius-lg` | 12px | V2 tile corners |
| radius-md | `$radius-md` | 8px | V3 tile corners |
| radius-full | `$radius-full` | 9999px | Badge pill |
| space-12 | `$space-12` | 48px | V2 section padding top/bottom |
| transition-base | `$transition-base` | 200ms ease | Hover transitions |

---

## Assets Needed

- [ ] Category photos (optional — colour fallbacks work without them):
  - `image/catalog/assets/categories/deklice.jpg`
  - `image/catalog/assets/categories/fantje.jpg`
  - `image/catalog/assets/categories/mamice.jpg`
  - `image/catalog/assets/categories/znamke.jpg`
  - `image/catalog/assets/categories/novo.jpg`
- [ ] Real category path IDs from OpenCart admin (replace placeholder IDs 60–63 in Twig)

---

## Acceptance Criteria

- [ ] All 5 category tiles render correctly
- [ ] Hover states match prototype
- [ ] "Novo prispelo" tile has distinct gold treatment + NOVO badge
- [ ] Mobile: 2-column grid below 768px, last tile full-width
- [ ] Category links use correct OpenCart route URLs
- [ ] No raw px values (use rem or spacing tokens)
- [ ] Build passes (`npm run build`)
