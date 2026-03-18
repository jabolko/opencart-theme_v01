# Active Work

## Current Focus
**Phase 3 — Page Templates**

## In Progress
Nothing in progress — homepage complete, awaiting next session.

## Recently Completed (2026-03-13) — Session 2
- Footer: collapsible Informacije / Odkrijte / Moj račun link groups on mobile
  - Chevron SVG on each col-title toggle, aria-expanded, CSS adjacent-sibling show/hide
  - `initFooterAccordion()` in theme.js (ES5, click + keyboard Enter/Space)
- Footer: `site-footer__proof-inner` padding + gap → $space-4 on mobile
- Footer: `site-footer__grid` padding → $space-4 0 on mobile
- Footer: both `<br>` in quote text hidden on mobile via `.site-footer__quote-break`
- Home: `_home.scss` token cleanup — replaced all hardcoded #eeeeee, #ffffff, #565656,
  rgba(247,247,248,1) and raw 1.88rem/0.71rem/0.94rem spacing with correct design tokens
- Home: `home-about__body` copy updated — 10+ years, 250.000+ articles
- Lighthouse run: Mobile Perf 83 / A11y 90 / BP 100 / SEO 100
  Desktop (DevTools): Perf 99 / A11y 94 / BP 100 / SEO 100

## Recently Completed (2026-03-13) — Session 1
- Mobile homepage pass — all sections adjusted for <768px
  - home-categories: top row 140px, bottom row 2-col, brands tile hidden, name font 1.06rem, image bottom 0.59rem, content pinned top, sub hidden
  - home-arrivals: arrows 32px, header two-row (heading line 1 / link+arrows line 2), padding-bottom removed
  - home-brands: `__more` converted to `<a>` link with underline style pointing to manufacturers page
  - home-reviews: badge centered, "Poglej vse ocene" link below scroller (mobile only), padding-bottom 24px
  - home-sell-cycle + home-about: `__text` split into `__text-head` + `__text-body`; CSS grid explicit placement on desktop, `order` on mobile → image appears between heading and body text on mobile
  - home-sell-cycle: heading + body text color → $color-text-base; padding-bottom $space-2 on mobile
  - home-values: `__text` font-weight 600

## Recently Completed (2026-03-10)
- PAGE-001 — Homepage fully complete (all 9 sections, S1–S9)
- Product card fixes: price 700 weight, info bottom-align for cards without manufacturer
- Brand logos updated, copy polished throughout
- Shadow clipping fix + build pipeline fix (always use `npm run build`, not `scss:build`)

## Decisions Made
- Always run `npm run build` (not `scss:build`) — browser loads `theme.min.css`
- `$color-surface-section: #eeeeee` (updated from #ececec)
- Product card hover shadow: `0 8px 20px rgba(0,0,0,0.10)` (reduced from 0 16px 40px)

## Next Up (Phase 3)
1. PAGE-002 — Category page — product grid, sort bar, sub-category row
2. PAGE-003 — Product page — image gallery, add-to-cart block
3. PAGE-004 — Cart page
4. Homepage deferred: category strip design decision (handoff active/category-strip.md),
   Latest Products module admin wiring, A11y violations, mobile Perf 83→90

## Session Notes
_Clear this section at the start of each session. Add working notes here during the session._
