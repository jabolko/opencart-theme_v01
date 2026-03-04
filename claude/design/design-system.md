# Design System — Otroski Koticek

## Brand Personality
Warm, playful, trustworthy. A store parents feel comfortable buying from.
Not cartoonish — friendly and modern. Think Scandinavian children's design aesthetic.

## Color System
See `tokens.md` for the exact hex values. Conceptually:

- **Primary:** A warm, rich color — the brand's main action color (buttons, links, highlights)
- **Secondary:** A soft complementary accent — used for badges, tags, hover states
- **Neutral:** Grays — used for text, borders, backgrounds
- **Feedback:** Standard green (success), amber (warning), red (error)
- **Surface:** Off-white page background — not pure white, slightly warm

## Typography
- **Body font:** System UI stack — fast, no external font request for body
- **Heading font:** TBD — define before first heading component build
- **Base size:** 16px (1rem)
- **Scale:** 1.25 modular scale (sm, base, lg, xl, 2xl, 3xl)
- **Line height:** 1.6 for body, 1.2 for headings

## Spacing System
8px base unit. Scale: 4, 8, 12, 16, 24, 32, 48, 64, 96px.
Always use token variables in SCSS — never raw pixel values.

## Breakpoints
| Name | Width  | Target device       |
|------|--------|---------------------|
| xs   | <576px | Mobile portrait     |
| sm   | 576px  | Mobile landscape    |
| md   | 768px  | Tablet              |
| lg   | 992px  | Small desktop       |
| xl   | 1200px | Desktop             |

Bootstrap 3 breakpoints (inherited from OpenCart). Do not change.

## Tone of Voice (UI copy)
- Friendly, clear, Slovenian
- No jargon
- Action buttons: direct verbs ("Dodaj v košarico" not "Klikni tukaj")

## Accessibility Baseline
- Minimum contrast ratio: 4.5:1 for body text, 3:1 for large text
- All interactive elements must have visible focus styles
- Images must have meaningful alt text (OpenCart provides via admin)
- No color-only information — always pair color with a label or icon
