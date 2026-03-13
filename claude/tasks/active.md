# Active Work

## Current Focus
**Phase 3 — Page Templates**

## In Progress
Nothing in progress — homepage mobile pass complete, awaiting next session.

## Recently Completed (2026-03-13)
- Mobile homepage pass — all sections adjusted for <768px
  - home-categories: top row 140px, bottom row 2-col, brands tile hidden, name font 1.06rem, image bottom 0.59rem, content pinned top, sub hidden
  - home-arrivals: arrows 32px, header two-row (heading line 1 / link+arrows line 2), padding-bottom removed
  - home-brands: `__more` converted to `<a>` link with underline style pointing to manufacturers page
  - home-reviews: badge centered, "Poglej vse ocene" link below scroller (mobile only), padding-bottom 24px
  - home-sell-cycle + home-about: `__text` split into `__text-head` + `__text-body`; CSS grid explicit placement on desktop, `order` on mobile → image appears between heading and body text on mobile

## Recently Completed (2026-03-10)
- PAGE-001 — Homepage fully complete (all 9 sections, S1–S9)
- Product card fixes: price 700 weight, info bottom-align for cards without manufacturer
- Brand logos updated, copy polished throughout
- Shadow clipping fix + build pipeline fix (always use `npm run build`, not `scss:build`)
- Lighthouse: Perf 83 / A11y 85 / BP 100 / SEO 100

## Decisions Made
- Always run `npm run build` (not `scss:build`) — browser loads `theme.min.css`
- `$color-surface-section: #eeeeee` (updated from #ececec)
- Product card hover shadow: `0 8px 20px rgba(0,0,0,0.10)` (reduced from 0 16px 40px)

## Next Up (Phase 3)
1. PAGE-002 — Category page — product grid, sort bar, sub-category row
2. PAGE-003 — Product page — image gallery, add-to-cart block
3. PAGE-004 — Cart page
4. A11y — investigate remaining 15-point gap (target 90+)

## Session Notes
_Clear this section at the start of each session. Add working notes here during the session._
