# Component Spec: Homepage

## Files
- Template: `template/common/home.twig` (OC renders this via `common/home` controller)
- Homepage sections: `template/extension/module/` (each OC module = one section)
- SCSS: `stylesheet/src/layout/_home.scss` (to be created)

## Strategic Context
Second-hand children's clothing. Key trust problem: parents need to believe in quality,
cleanliness, and safety before buying. Every section either builds that case or it doesn't belong.

Three silent questions a visitor asks in the first 5 seconds:
1. "Can I trust this?" → trust bar, founder, Google reviews
2. "Is this for me?" → category strip, brands
3. "Why here and not Vinted?" → 14 years, curation, community, sell-back flywheel

---

## Section Stack (final, ordered top → bottom)

| # | Section | Block name | Priority | Status |
|---|---------|------------|----------|--------|
| — | Header | (done) | — | ✓ done |
| 1 | Hero | `.home-hero` | Critical | to build |
| 2 | Trust bar | `.home-trust` | Critical | to build |
| 3 | Category strip | `.home-cats` | High | to build |
| 4 | New arrivals | `.home-arrivals` | High | to build |
| 5 | Popular brands | `.home-brands` | Medium | to build |
| 6 | Prodaj svoja oblačila | `.home-sell` | High | to build |
| 7 | How it works | `.home-how` | Medium | to build |
| 8 | Google reviews | `.home-reviews` | Critical | to build |
| 9 | Values / sustainability | `.home-values` | Medium | to build |
| 10 | Newsletter | `.home-newsletter` | Low | to build |
| — | Footer | (done) | — | ✓ done |

---

## Section Specs

---

### 1 — Hero `.home-hero`

**Purpose:** First impression. Communicate brand identity + value prop in under 3 seconds.

**Layout:** Full-width, 2-column on desktop (text left, image right). Single column on mobile.

**Content:**
```
Eyebrow:   Otroški kotiček  (small, gold, uppercase)
Headline:  Nežno rabljena oblačila za otroke in mamice
Tagline:   Tvoje najljubše znamke, za delček cene.
CTA 1:     Brskaj po ponudbi  →  /catalog/category path
CTA 2:     Prodaj oblačila    →  information_id=9 (text link, lighter)
```

**Image:** Hero photo — lifestyle, not product. Child wearing quality clothes.
Must be provided as static asset. No CMS/slider.

**Performance rules:**
- `<img>` with `fetchpriority="high"` and `loading="eager"`
- `<link rel="preload">` in `<head>` for the hero image
- Explicit `width` / `height` attributes to prevent CLS
- No JavaScript, no animation on load

**Design:**
- Background: `$color-surface` (white) or very light warm neutral
- Headline: Open Sans 600, large (~40px desktop)
- Tagline: DM Sans 400, italic, muted (`$color-text-muted`)
- No carousel, no auto-play, no JS required

---

### 2 — Trust bar `.home-trust`

**Purpose:** Instant credibility. Answers "can I trust this?" with hard numbers.
Placed directly below hero so it's visible on first scroll.

**Layout:** Horizontal strip, 3 equal columns, full-width, light background band.

**Content (3 pillars):**
```
14 let        →  izkušenj
250.000+      →  oblačil v dobrem domu
5.000+        →  zadovoljnih strank
```

**Design:**
- Background: `$color-surface-alt` (#f7f7f7) or a subtle warm tint
- Number: Open Sans 600, large (~36px), `$color-primary` (gold)
- Label: DM Sans 400, small, `$color-text-muted`
- Dividers between pillars: `1px solid $color-border`
- Padding: `$space-8` vertical

**Note:** No price pillar here. Hero tagline "za delček cene" already carries the price message.
Keeping 3 pillars is cleaner than 4.

---

### 3 — Category strip `.home-cats`

**Purpose:** Get visitors to product in one click. Fastest path to conversion.

**Layout:** 4–5 large clickable tiles in a row. Desktop: single row. Mobile: 2×2 grid + 1.

**Tiles:**
```
1. Deklice    →  /category/deklice
2. Fantje     →  /category/fantje
3. Mamice     →  /category/nosecnost
4. Znamke     →  /catalog/brands or filtered view
5. Novo       →  /catalog/new-arrivals (optional 5th)
```

**Content per tile:**
- Category photo (lifestyle or flat-lay)
- Category name (large, white, on image)
- Optional floor price: `od 2€` (small, below name) — honest, always true

**Design:**
- `aspect-ratio: 4/3` on desktop tiles
- `object-fit: cover` on images
- Hover: slight scale + darker overlay
- Text always white with `text-shadow` for legibility on any photo

**Performance:** Lazy-load all tile images (`loading="lazy"`).

---

### 4 — New arrivals `.home-arrivals`

**Purpose:** Demonstrates active, fresh inventory. Gives repeat visitors a reason to come back.

**Layout:** Section heading + horizontal product card row (8–12 cards).
On desktop: 4-per-row grid. On mobile: horizontal scroll or 2-per-row.

**Content:**
```
Eyebrow:  Sveže prispelo
Heading:  Novi kosi čakajo nate
CTA:      Poglej vse →
```

**Product cards:** Pulled from OC "Latest Products" module.
Card spec → see `claude/design/components/product-card.md`

**Price anchoring:** Product card handles this — show RRP crossed out where available.
This is the product card's responsibility, NOT the homepage section's.

---

### 5 — Popular brands `.home-brands`

**Purpose:** Brand recognition builds instant "this is quality" trust.
Reinforces "tvoje najljubše znamke" from the hero.

**Layout:** Section heading + horizontal logo strip. No cards, just logos.

**Content:**
```
Heading:  Znamke, ki jih poznate
Logos:    Zara / H&M / Next / Mayoral / Boboli / Lindex / Name It / ...
```

**Design:**
- Logos: grayscale by default, color on hover
- Height: ~40px per logo, consistent
- Background: white
- No carousel — static row, overflow hidden on mobile (or wrap)

**Performance:** SVG logos preferred. Explicit dimensions on all `<img>` tags.

---

### 6 — Prodaj svoja oblačila `.home-sell`

**Purpose:** Revenue driver + community flywheel. Sellers become buyers.
This is a differentiator from Vinted — you take the hassle out of selling.

**Layout:** Full-width band, 2-column. Left: text + CTA. Right: simple 3-step visual.

**Background:** Green band — `#528e6d` (nav CTA end color). White text on green.

**Content:**
```
Eyebrow:  Za prodajalce
Heading:  Prodaj svoja oblačila
Body:     Otroci rastejo hitro. Oblačila, ki jih ne potrebujete več,
          naj dobijo nov dom — in vi prejmite plačilo.
CTA:      Kako deluje →  information_id=9

3 steps (right side):
1. Zberi oblačila    — ikona: škatla
2. Pošlji na naslov  — ikona: pošta / dostava
3. Prejmi plačilo    — ikona: denar / kovanec
```

**Design:**
- Background: `#528e6d` (green)
- Text: white
- Eyebrow: white, 60% opacity, uppercase
- Steps: white circles with number, text below
- CTA: `$color-primary` (gold) pill button — high contrast on green

---

### 7 — How it works `.home-how`

**Purpose:** Reduce purchase anxiety for first-time buyers of second-hand clothing.
Primarily for new visitors who've never bought online second-hand before.

**Layout:** 3-column icon + text strip. Light background.

**Content:**
```
Heading:  Nakup je preprost

Step 1:  Izberi oblačila     → icon: magnifier / browse
         Prebrskaj stotine kosov znanih znamk.

Step 2:  Naroči              → icon: cart
         Varno plačilo, hitra dostava.

Step 3:  Uživaj              → icon: heart / star
         Kakovostna oblačila. Srečen otrok.
```

**Design:**
- Background: `$color-surface` (white)
- Icons: simple line icons (`fa` or inline SVG), `$color-primary`
- Step number: subtle, small, `$color-text-muted`

---

### 8 — Google reviews `.home-reviews`

**Purpose:** Strongest social proof. Authentic presentation is critical — looks like
actual Google reviews, not a generic marketing carousel.

**Layout:** Section heading + 3–4 review cards in a row.

**Content:**
```
Eyebrow:  Kaj pravijo stranke
Heading:  207 ocen na Googlu
Sub:      Povprečna ocena 4,9 ★
Link:     Preberi vse ocene →  Google My Business URL
```

**Card anatomy (styled to look like Google):**
```
[ G ]  Ana K.          ★★★★★
       2 tedna nazaj

"Odlična izkušnja! Oblačila so bila čista in točno
kot opisano. Priporočam vsem mamicam."
```

- `[ G ]` = coloured circle with initial (authentic Google avatar look)
- Name in bold, date in muted grey
- Stars: gold, filled SVGs (no font icon for accuracy)
- Review text: DM Sans, regular
- Card: white, `border-radius: $radius-lg`, subtle `box-shadow`

**Performance:** Static HTML only. Zero third-party JS. No Google widget embed.
Paste 3–4 real reviews as static content. Link to live Google page for full list.

**Why static:** Google review embeds are 3rd-party JS that blocks rendering, adds cookies,
and scores poorly on best-practices audit. Static cards convert just as well.

---

### 9 — Values / sustainability `.home-values`

**Purpose:** The "against the current" story. Makes the customer feel good about the choice.
Positions lower price as a smart, values-driven decision — not a compromise.

**Layout:** Centered text block or 2-column (text + simple graphic/counter).

**Content (draft):**
```
Heading:  #RabljenoJeZakon

Body:     Vsako oblačilo, ki ga kupite pri nas, je oblačilo manj na odlagališču.
          Skupaj smo podaljšali življenje že 250.000 kosom oblačil.
          To ni samo nakup — to je odločitev.
```

**Design:**
- Background: `$color-section` (#ececec) or a soft warm tint
- Heading: Open Sans 600, medium-large
- `#RabljenoJeZakon` hashtag in gold — doubles as brand hashtag

**Optional:** A simple counter "250.000 oblačil rešenih" with a leaf/recycle icon.

---

### 10 — Newsletter `.home-newsletter`

**Purpose:** Build owned audience. Low urgency — placed last before footer.

**Layout:** Single centered line. Minimal.

**Content:**
```
Heading:  Bodite prvi obveščeni
Body:     Prijavite se in izveste takoj, ko prispejo novi kosi vaših najljubših znamk.
Input:    [email placeholder]  [Prijavi se]
```

**Design:**
- Background: same as trust bar (`$color-surface-alt`)
- Input: outline pill style (matches search bar)
- Button: `$color-primary` pill

---

## Build Order

| Sprint | Sections | Why first |
|--------|---------|-----------|
| 1 | Hero + Trust bar | Above the fold — highest visible impact |
| 2 | Category strip + Prodaj | Drive navigation + business flywheel |
| 3 | Google reviews + Values | Conversion trust layer |
| 4 | New arrivals | Requires product card component (Phase 3) |
| 5 | Brands + How it works + Newsletter | Polish pass |

---

## Performance Checklist (homepage-specific)

- [ ] Hero `<img>` has `fetchpriority="high"` + `loading="eager"`
- [ ] Hero image has `<link rel="preload">` in `<head>`
- [ ] All below-fold images have `loading="lazy"`
- [ ] All images have explicit `width` + `height` (prevents CLS)
- [ ] Brand logos use SVG
- [ ] Reviews section is static HTML — no third-party JS
- [ ] No carousel/slider anywhere
- [ ] Each section is CSS-only (no JS unless unavoidable)

---

## Open Decisions / Placeholders

- Hero image: needs final photo asset
- Category tile photos: need assets per category
- Brand logo list: confirm which brands to show (Zara, H&M, Next, Mayoral, Boboli, ...)
- Google reviews: need 3–4 real review texts from Google My Business
- Google My Business URL: needed for "Preberi vse ocene" link
- OC module setup: which sections use OC modules vs. static Twig
