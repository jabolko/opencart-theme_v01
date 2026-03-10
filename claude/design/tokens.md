# Design Tokens

Authoritative values. Every SCSS variable MUST appear here first.
When a token changes here, update the corresponding SCSS variable in `abstracts/_variables.scss`.

## Colors

> Palette: gold accent on charcoal/white neutrals. Gold (#f9b94a) used sparingly —
> only on elements that need attention. Charcoal (#565656) handles text and UI structure.
> White (#ffffff) provides breathing room.

### Brand
| Token name            | SCSS variable           | Value   | Usage                                          |
|-----------------------|-------------------------|---------|------------------------------------------------|
| color-primary         | `$color-primary`        | #f9b94a | Add to Cart buttons, sale badges, focus rings  |
| color-primary-light   | `$color-primary-light`  | #ffd369 | Hover on gold elements, active nav underline   |
| color-primary-dark    | `$color-primary-dark`   | #e0a030 | Pressed/active state on gold buttons           |
| color-secondary       | `$color-secondary`      | #565656 | Secondary buttons, icons, nav background       |
| color-secondary-dark  | `$color-secondary-dark` | #3a3a3a | Hover on charcoal buttons, footer              |
| color-accent          | `$color-accent`         | #f9b94a | Sale tags, "Novo"/"Akcija" badges              |

### Text
| Token name            | SCSS variable           | Value   | Usage                                          |
|-----------------------|-------------------------|---------|------------------------------------------------|
| color-text-heading    | `$color-text-heading`   | #2b2e35 | H1–H6, page titles (near-black)                |
| color-text-base       | `$color-text-base`      | #565656 | Body text (reuses charcoal — cohesive)         |
| color-text-muted      | `$color-text-muted`     | #9aa0af | Labels, meta, helper text                      |
| color-text-link       | `$color-text-link`      | #566b6f | Default link color (menu)                      |
| color-text-on-primary | `$color-text-on-primary`| #2b2e35 | Text ON gold buttons (white fails on yellow)   |
| color-text-on-dark    | `$color-text-on-dark`   | #f5f5f5 | Text on dark backgrounds (footer, dark nav)    |

### Surfaces & Backgrounds
| Token name            | SCSS variable           | Value   | Usage                                          |
|-----------------------|-------------------------|---------|------------------------------------------------|
| color-surface         | `$color-surface`        | #ffffff | Main page background                           |
| color-surface-alt     | `$color-surface-alt`    | #f7f7f7 | Product cards, input fields                    |
| color-surface-section | `$color-surface-section`| #eeeeee | Homepage bands, category tiles bg              |
| color-surface-dark    | `$color-surface-dark`   | #3a3a3a | Footer (tied to charcoal family)               |

### Borders
| Token name            | SCSS variable           | Value   | Usage                                          |
|-----------------------|-------------------------|---------|------------------------------------------------|
| color-border          | `$color-border`         | #d6d9e0 | Dividers, input borders, card borders          |
| color-border-dark     | `$color-border-dark`    | #4a4a4a | Borders on dark backgrounds                    |

### Feedback
| Token name      | SCSS variable     | Value   | Usage                                        |
|-----------------|-------------------|---------|----------------------------------------------|
| color-success   | `$color-success`  | #7d9478 | Success alerts, in-stock indicator           |
| color-warning   | `$color-warning`  | #e99f20 | Warning alerts (amber, distinct from primary)|
| color-error     | `$color-error`    | #d55e6f | Error alerts, out-of-stock, remove actions   |

## Button Patterns

| Button type | Background              | Text                    | Hover background        |
|-------------|-------------------------|-------------------------|-------------------------|
| Primary     | `$color-primary`        | `$color-text-on-primary`| `$color-primary-light`  |
| Secondary   | `$color-secondary`      | `$color-text-on-dark`   | `$color-secondary-dark` |
| Ghost       | transparent             | `$color-secondary`      | `$color-surface-alt`    |

## Typography

> Two-font pairing. Open Sans (headings) + DM Sans (body). Self-host both —
> do NOT use Google Fonts CDN (render-blocking). Load via @fontsource npm packages.
> Open Sans: weights 500 (h4–h6) and 600 (h1–h3). DM Sans: weight 400 only.

| Token name               | SCSS variable                | Value                              |
|--------------------------|------------------------------|------------------------------------|
| font-family-heading      | `$font-family-heading`       | 'Open Sans', system-ui, sans-serif |
| font-family-body         | `$font-family-body`          | 'DM Sans', system-ui, sans-serif   |
| font-weight-heading-semi | `$font-weight-heading-semi`  | 600                                |
| font-weight-heading-md   | `$font-weight-heading-md`    | 500                                |
| font-weight-body         | `$font-weight-body`          | 400                                |
| html-root                | `html { font-size }`         | 17px — anchors 1rem = 17px sitewide |
| font-size-base           | `$font-size-base`            | 1rem = 17px                        |
| font-size-sm             | `$font-size-sm`              | 0.875rem ≈ 15px                    |
| font-size-lg             | `$font-size-lg`              | 1.125rem ≈ 19px                    |
| font-size-xl             | `$font-size-xl`              | 1.25rem                            |
| font-size-2xl            | `$font-size-2xl`             | 1.5rem                             |
| font-size-3xl            | `$font-size-3xl`             | 2rem                               |
| line-height-body         | `$line-height-body`          | 1.6                                |
| line-height-heading      | `$line-height-heading`       | 1.2                                |

## Spacing
| Token name  | SCSS variable  | Value |
|-------------|----------------|-------|
| space-1     | `$space-1`     | 4px   |
| space-2     | `$space-2`     | 8px   |
| space-3     | `$space-3`     | 12px  |
| space-4     | `$space-4`     | 16px  |
| space-6     | `$space-6`     | 24px  |
| space-8     | `$space-8`     | 32px  |
| space-12    | `$space-12`    | 48px  |
| space-16    | `$space-16`    | 64px  |

## Border Radius
| Token name   | SCSS variable    | Value  |
|--------------|------------------|--------|
| radius-sm    | `$radius-sm`     | 4px    |
| radius-md    | `$radius-md`     | 8px    |
| radius-lg    | `$radius-lg`     | 12px   |
| radius-full  | `$radius-full`   | 9999px |

## Breakpoints

> Bootstrap 3 breakpoints (sm/md/lg) unchanged. xl added for wide-screen product grids.
> Container at xl: 1520px (1550 viewport − 30px gutters).

| Token name    | SCSS var (in $breakpoints map) | Value  | Container     |
|---------------|-------------------------------|--------|---------------|
| breakpoint-sm | `'sm'`                        | 768px  | 750px (BS3)   |
| breakpoint-md | `'md'`                        | 992px  | 970px (BS3)   |
| breakpoint-lg | `'lg'`                        | 1200px | 1170px (BS3)  |
| breakpoint-xl | `'xl'`                        | 1550px | 1520px (ours) |

## Shadows
| Token name    | SCSS variable     | Value                          |
|---------------|-------------------|--------------------------------|
| shadow-card   | `$shadow-card`    | 0 2px 8px rgba(0,0,0,0.08)    |
| shadow-hover  | `$shadow-hover`   | 0 4px 16px rgba(0,0,0,0.12)   |

## Transitions
| Token name       | SCSS variable        | Value       |
|------------------|----------------------|-------------|
| transition-base  | `$transition-base`   | 200ms ease  |
| transition-slow  | `$transition-slow`   | 350ms ease  |
