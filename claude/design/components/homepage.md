# Component Spec: Homepage

## Files
- Template: `template/common/home.twig`
- SCSS: `stylesheet/src/pages/_home.scss`
- Latest products module controller: `opencart/catalog/controller/extension/module/latest.php`
- Latest products module twig: `template/extension/module/latest.twig`

**Status: COMPLETE** — All sections built and live as of 2026-03-10.
Lighthouse: Perf 83 / A11y 85 / BP 100 / SEO 100

---

## Strategic Context
Second-hand children's clothing. Key trust problem: parents need to believe in quality,
cleanliness, and safety before buying. Every section either builds that case or it doesn't belong.

Three silent questions a visitor asks in the first 5 seconds:
1. "Can I trust this?" → USP strip, Google reviews
2. "Is this for me?" → category grid, brands
3. "Why here and not Vinted?" → 14 years, curation, community, sell-back flywheel

---

## Section Stack (actual built order, top → bottom)

| # | Section | Block name | Status |
|---|---------|------------|--------|
| — | Header | (shared component) | ✓ done |
| S1 | Hero | `.home-hero` | ✓ done |
| S2 | USP strip | `.home-usp` | ✓ done |
| S3 | Category grid | `.home-categories` | ✓ done |
| S4 | New arrivals | `.home-arrivals` | ✓ done |
| S5 | Popular brands | `.home-brands` | ✓ done |
| S6 | Sell your clothes | `.home-sell` | ✓ done |
| S7 | Google reviews | `.home-reviews` | ✓ done |
| S8 | Sell cycle explainer | `.home-sell-cycle` | ✓ done |
| S9 | About | `.home-about` | ✓ done |
| S10 | Values | `.home-values` | ✓ done |
| — | Footer | (shared component) | ✓ done |

Newsletter section was scoped out — not built.

---

## Section Specs (as built)

---

### S1 — Hero `.home-hero`

**Purpose:** First impression. Brand identity + value prop in under 3 seconds.

**Layout:** Full-width, 2-column on desktop (text left, image right). Single column on mobile.

**Content (actual):**
```
Eyebrow:   Otroški kotiček  (small, gold, uppercase)
Headline:  Nežno rabljena oblačila za otroke in mamice
Tagline:   Tvoje najljubše znamke, za delček cene.
CTA 1:     Brskaj po ponudbi  →  category
CTA 2:     Prodaj oblačila    →  information page (text link)
```

**Image:** Static hero photo asset. `fetchpriority="high"`, `loading="eager"`.

**Design:**
- Background: `$color-surface` (white)
- Headline: Open Sans 600, ~2.35rem desktop
- Tagline: DM Sans 400, muted
- No JS, no animation on load

---

### S2 — USP strip `.home-usp`

**Purpose:** Instant credibility below the fold. Answers "can I trust this?" with hard numbers.

**Layout:** Horizontal strip, 3 equal columns, full-width, light background band.

**Content (actual):**
```
14 let        →  izkušenj
250.000+      →  oblačil v dobrem domu
5.000+        →  zadovoljnih strank
```

**Design:**
- Background: `$color-surface-section` (`#eeeeee`)
- Number: Open Sans 600, large, `$color-primary` (gold)
- Label: DM Sans 400, small, `$color-text-muted`
- Padding: `$space-8` vertical

---

### S3 — Category grid `.home-categories`

**Purpose:** Get visitors to product in one click. Fastest path to conversion.

**Layout:** 2+3 asymmetric editorial grid on desktop. 2-column on mobile.

**Tiles (actual):**
```
1. Deklice    →  /category/deklice
2. Fantje     →  /category/fantje
3. Mamice     →  /category/mamice
4. Znamke     →  /brands
5. Novo       →  /new-arrivals
```

**Content per tile:**
- Category photo (lifestyle)
- Eyebrow: short label (uppercase, gold)
- Category name (large, white)
- Arrow animation on hover

**Design:**
- `aspect-ratio` varies per tile (asymmetric grid)
- `object-fit: cover` on images
- Hover: diagonal stripe overlay + arrow animates right
- Text always white with text-shadow for legibility

**Performance:** `loading="lazy"` on all tile images.

---

### S4 — New arrivals `.home-arrivals`

**Purpose:** Demonstrates active, fresh inventory. Gives repeat visitors a reason to return.

**Layout:** Section heading + horizontal scroll product card row. Extends to viewport right edge.

**Content (actual):**
```
Eyebrow:  Sveže prispelo
Heading:  Novi kosi čakajo nate
CTA:      Poglej vse →
```

**Technical:**
- Scroller: `overflow-x: auto`, `margin-right: calc(-50vw + 50%)` (right edge extends to viewport)
- Cards: `flex: 0 0 Xrem; scroll-snap-align: start`
- JS prev/next navigation arrows; prev hidden via `.home-arrivals__arrow--hidden` on load
- Product cards use `.product-card` BEM block — see `claude/design/components/product-card.md`
- Module: OC "Latest Products" (controller override adds `manufacturer` + `minimum` fields)
- `padding-bottom: $space-8` (32px) provides clearance for hover shadow `0 8px 20px`

**Performance:** `loading="lazy"` on all product card images.

---

### S5 — Popular brands `.home-brands`

**Purpose:** Brand recognition — "tvoje najljubše znamke" reinforced visually.

**Layout:** Section heading + horizontal logo strip. Logos: grayscale → color on hover.

**Content (actual — as of 2026-03-10):**
```
S.Oliver / Next / H&M / Gap / Zara / Adidas / Nike / +30 drugih
```
"+30 drugih" is a text badge (`.home-brands__more`) — not an image.

**Design:**
- Logos: `<img>` tags, grayscale default, full color on hover (CSS `filter`)
- Logo height: consistent ~40px
- Background: white
- Horizontal row, scroll on mobile

---

### S6 — Sell your clothes `.home-sell`

**Purpose:** Revenue driver + community flywheel. Sellers become buyers.
Differentiator from Vinted — we remove the hassle of selling.

**Layout:** Full-width green band, 2-column. Left: text + CTA. Right: 3-step visual.

**Content (actual):**
```
Eyebrow:  Za prodajalce
Heading:  Prodaj svoja oblačila
Body:     Otroci rastejo hitro. Oblačila, ki jih ne potrebujete več,
          naj dobijo nov dom — in vi prejmite plačilo.
CTA:      Kako deluje →

3 steps:
1. Pripravi oblačila    (step label above, icon below)
2. Kurir prevzame paket
3. Prejmi plačilo
   "Pripraviš paket, mi skrbimo za ostalo."
```

**Design:**
- Background: `#528e6d` (green band)
- Text: white; Eyebrow: white, uppercase, muted opacity
- Steps: numbered circles, white text
- CTA: `$color-primary` (gold) pill button

---

### S7 — Google reviews `.home-reviews`

**Purpose:** Strongest social proof. Static HTML — no third-party JS embed.

**Layout:** Section heading + horizontal scroll review cards (`.home-reviews__scroller`).

**Content (actual):**
```
Eyebrow:  Kaj pravijo stranke
Count:    150+ ocen  (`.home-reviews__count`)
Stars:    Povprečna 4,9 ★
```

**Card anatomy:**
```
[ G ]  Ime Priimek     ★★★★★
       X tednov nazaj

"Review text..."
```

**Design:**
- Google-styled avatar circle (coloured initial)
- Cards: white, `border-radius: $radius-lg`, subtle `box-shadow`
- Static HTML — 3–4 real reviews pasted as Twig content

---

### S8 — Sell cycle explainer `.home-sell-cycle`

**Purpose:** Explain the full sell-buy loop. Educate first-time sellers.

**Layout:** Centered section on `$color-surface-section` background.

**Content:**
```
Eyebrow:  [contextual label — color overridden to $color-text-base inside this section]
Heading:  [sell cycle heading, font-size: 2.35rem, same style as .home-sell__heading]
```

**Design note:**
- `.home-eyebrow` inside `.home-sell-cycle` has color override to `$color-text-base` (`#565656`)
  because gold eyebrow is unreadable on the section background
- Heading matches `.home-sell__heading` style: `font-size: 2.35rem`; mobile: `1.88rem`

---

### S9 — About `.home-about`

**Purpose:** Brand story, founder credibility, "14 let izkušenj" in narrative form.

**Layout:** Section on white or light background.

**Design note:**
- `.home-eyebrow` inside `.home-about` also overridden to `$color-text-base` (same reason as S8)

---

### S10 — Values `.home-values`

**Purpose:** Makes the customer feel good about choosing second-hand.
Positions lower price as smart + values-driven — not a compromise.

**Content:**
```
Heading:  #RabljenoJeZakon
Tags:     value pill tags (`.home-values__tag`)
```

**Design:**
- Background: `$color-surface-section` (`#eeeeee`)
- `__tag`: `font-weight: 700` (increased from 600 for legibility)
- `#RabljenoJeZakon` in gold (`$color-primary`)

---

## Resolved Decisions

| Decision | Resolution |
|----------|------------|
| Newsletter section | Dropped — not built |
| Category block name | `.home-categories` (not `.home-cats`) |
| SCSS file location | `pages/_home.scss` (not `layout/_home.scss`) |
| Brands list | S.Oliver, Next, H&M, Gap, Zara, Adidas, Nike + "+30 drugih" |
| Reviews count | 150+ ocen |
| Footer review count | 153 ocen |
| Hover shadow | `0 8px 20px rgba(0,0,0,0.10)` (not 40px spread — was clipping) |
| Build command | Always `npm run build` (not `scss:build`) — updates `theme.min.css` |
| Section background | `$color-surface-section: #eeeeee` |
| Eyebrow color (sell-cycle, about) | Overridden to `$color-text-base` — gold unreadable on `#eeeeee` |

---

## Performance (2026-03-10)
- Lighthouse Performance: 83/100
- Lighthouse Accessibility: 85/100
- Lighthouse Best Practices: 100/100
- Lighthouse SEO: 100/100
- LCP: 4.2s (Docker-latency driven — not a real-world concern)
- CLS: 0.004 | TBT: 0ms
