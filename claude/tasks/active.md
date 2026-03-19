# Active Work

## Current Focus
**Phase 3 — Page Templates**

## In Progress
PAGE-002 — Category page (mobile filter system built, desktop pass pending)

## Recently Completed (2026-03-18)
- Category page: mobile bottom sheet filter (Kategorije + Filtri dual buttons)
- Filter sheet: OC filter groups (Starost/Velikost), manufacturer/brand filter, category tree
- Active filter chips inside filter sheet with remove + clear all
- Custom `column_left.twig` — removes Bootstrap `hidden-xs` wrapper
- Custom `filter.twig` — bottom sheet UI replacing OC's default Bootstrap panel
- Default sort changed to "Zadnje dodano" (newest first, `p.date_added DESC`)
- Manufacturer multi-select filter (`filter_manufacturer` URL param, comma-separated IDs)
- Category tree in filter: parents + first-level children under active parent
- Description toggle (Pokaži opis / Skrij opis) on mobile
- Mobile toolbar: sort inline with count, Kategorije + Filtri buttons full-width
- Category controller: manufacturer query, active filter chips, clear URL, sort persistence
- Product model: `filter_manufacturer_ids` support in `getProducts()` + `getTotalProducts()`
- Lighthouse category mobile: Perf 82 / A11y 84 / BP 100 / SEO 100

## Recently Completed (2026-03-13)
- Homepage mobile pass complete (all sections S1–S9)
- Footer mobile accordion
- Lighthouse homepage: Mobile 83/90/100/100, Desktop 99/94/100/100

## Decisions Made
- Always run `npm run build` (not `scss:build`) — browser loads `theme.min.css`
- `$color-surface-section: #eeeeee` (updated from #ececec)
- Product card hover shadow: `0 8px 20px rgba(0,0,0,0.10)` (reduced from 0 16px 40px)
- Category page default sort: `p.date_added DESC` ("Zadnje dodano")
- Filter UX: dual buttons (Kategorije opens category-only sheet, Filtri opens full filter sheet)
- Category description hidden behind "Pokaži opis" toggle on mobile (SEO: stays in DOM for crawlers)

## Next Up
1. PAGE-002 — Category page polish (desktop layout, A11y improvements)
2. PAGE-003 — Product page — image gallery, add-to-cart block
3. PAGE-004 — Cart page
4. Homepage deferred: category strip design decision (handoff active/category-strip.md),
   Latest Products module admin wiring, A11y violations, mobile Perf 83→90

## Session Notes
_Clear this section at the start of each session. Add working notes here during the session._
