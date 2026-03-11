# Design System — Otroski Koticek

## Brand Personality
Warm, playful, trustworthy. A store parents feel comfortable buying from.
Not cartoonish — friendly and modern. Scandinavian children's design aesthetic.
Gold-on-charcoal palette. Gold used sparingly — only on elements that need attention.

## Color System
See `tokens.md` for exact hex values. Conceptually:

- **Primary gold** (`#f9b94a`): Add to Cart buttons, sale badges, focus rings, highlights
- **Charcoal** (`#565656`): Body text, secondary buttons, icons, nav background
- **Text heading** (`#2b2e35`): H1–H6, page titles (near-black)
- **Surface** (`#ffffff`): Main page background
- **Section bg** (`#eeeeee`): Homepage bands, category tiles, product card bg
- **Feedback:** success `#7d9478` / warning `#e99f20` / error `#d55e6f`

Text on gold buttons must be `#2b2e35` (NOT white — fails contrast on yellow).

## Typography
- **Heading font:** Open Sans — weight 600 (h1–h3), weight 500 (h4–h6)
- **Body font:** DM Sans — weight 400 only
- **Both self-hosted** via `@fontsource` npm packages — NO Google Fonts CDN (render-blocking)
- **Root font size:** `17px` on `<html>` — 1rem = 17px sitewide
- **Scale:** sm (0.875rem ≈15px), base (1rem = 17px), lg (1.125rem), xl (1.25rem), 2xl (1.5rem), 3xl (2rem)
- **Line height:** 1.6 body, 1.2 headings

> All rem values in SCSS use ÷17 conversion. Never hardcode px.

## Spacing System
4px base unit. Token scale: `$space-1`=4px, `$space-2`=8px, `$space-3`=12px, `$space-4`=16px,
`$space-6`=24px, `$space-8`=32px, `$space-12`=48px, `$space-16`=64px.
Always use token variables in SCSS — never raw pixel values.

## Breakpoints
| Name | Min-width | Container | Notes |
|------|-----------|-----------|-------|
| sm   | 768px     | 750px     | Bootstrap 3 — unchanged |
| md   | 992px     | 970px     | Bootstrap 3 — unchanged |
| lg   | 1200px    | 1170px    | Bootstrap 3 — unchanged |
| xl   | 1550px    | 1520px    | Custom addition for wide screens |

Use `respond-below('sm')` mixin — no raw `@media` inline.

## Button Patterns
| Type      | Background          | Text                    | Hover                   |
|-----------|---------------------|-------------------------|-------------------------|
| Primary   | `$color-primary`    | `$color-text-on-primary`| `$color-primary-light`  |
| Secondary | `$color-secondary`  | `$color-text-on-dark`   | `$color-secondary-dark` |
| Ghost     | transparent         | `$color-secondary`      | `$color-surface-alt`    |

## Tone of Voice (UI copy)
- Friendly, clear, Slovenian
- No jargon
- Action buttons: direct verbs ("Dodaj v košarico", "Razišči", "Poglej vse")
- Eyebrows: short, uppercase, gold — set context before heading

## Accessibility Baseline
- Minimum contrast ratio: 4.5:1 for body text, 3:1 for large text
- All interactive elements must have visible focus styles (`$color-primary` outline)
- Images: meaningful alt text (OpenCart admin provides via product fields)
- No color-only information — always pair color with label or icon
- Current Lighthouse A11y score: 85/100 (target: 90+)
