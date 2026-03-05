# Completed Tasks

## Log

| Date       | Task ID    | What was done                                                                 | Notes |
|------------|------------|-------------------------------------------------------------------------------|-------|
| 2026-03-03 | SETUP      | Created `claude/` folder with all organization files                          | Project brain established |
| 2026-03-03 | SETUP      | Created `CLAUDE.md` at project root                                           | Claude Code session instructions |
| 2026-03-03 | SCSS-001   | 7-1 folder structure created under `stylesheet/src/`                          | All partials stubbed |
| 2026-03-03 | SCSS-002   | All design tokens defined in `abstracts/_variables.scss`                      | Based on `claude/design/tokens.md` |
| 2026-03-03 | SCSS-003   | Mixins written: respond-to, respond-below, flex-center, flex-between, visually-hidden | `sass:map` module used |
| 2026-03-03 | SCSS-004   | Base resets: `_fonts.scss`, `_reset.scss`, `_body.scss`, `_typography.scss`   | Open Sans + DM Sans self-hosted via @fontsource |
| 2026-03-03 | TWIG-001   | `header.twig` — Google Fonts CDN removed, default theme stylesheet removed    | |
| 2026-03-03 | TWIG-002   | `theme.min.css` injected in `header.twig` after Font Awesome                  | |
| 2026-03-03 | BUILD      | `fonts:copy` script added to `package.json`; `npm run build` confirmed working | 1,565 bytes minified CSS |
| 2026-03-03 | COMP-003   | `_header.scss` — sticky `.site-header`, `#top` dark bar, `header` main bar   | |
| 2026-03-03 | COMP-004   | `_nav.scss` — Bootstrap navbar override, gold hover, dropdown, mobile menu    | |
| 2026-03-03 | SCSS-XL    | xl breakpoint (1550px) added to mixins; container widened to 1520px           | `_grid.scss` created |
| 2026-03-04 | DATA       | Imported 8000 products, categories, filters, options, manufacturers, Slovenian language | Via phpMyAdmin / CSV |
| 2026-03-04 | GIT        | Initial commit + push to private GitHub repo `jabolko/opencart-theme_v01`    | `opencart/image/izdelki/` excluded (1.4GB) |
| 2026-03-04 | COMP-002   | `_button.scss` — Bootstrap 3 btn overrides: primary (gold), default, inverse, danger, link | |
| 2026-03-04 | COMP-005   | `footer.twig` + `_footer.scss` — dark charcoal footer, 4-column grid, mobile stack | |
| 2026-03-04 | BASE       | `html { font-size: 17px }` in `_reset.scss` — anchors 1rem sitewide          | User chose 17px |
| 2026-03-04 | BASE       | `_bootstrap-overrides.scss` — `.btn`, `.form-control`, `.input-group-btn > .btn` font-size | Fixes BS3 14px hardcode |
| 2026-03-04 | COMP-003   | `_header.scss` complete rewrite — flex layout, pill search, icon action buttons, mobile wrap | Removed #top bar |
| 2026-03-04 | TWIG-001   | `header.twig` complete rewrite — `.header-inner` flex, `.header-actions`, no #top | |
| 2026-03-04 | COMP-004   | `_nav.scss` rewrite — white navbar, uppercase links, gold border-bottom on hover/active | |
| 2026-03-04 | FONTS      | `_fonts.scss` — added `latin-ext` @font-face rules for čžš support (unicode-range) | 6 woff2 files total |
| 2026-03-04 | BUILD      | `package.json` fonts:copy updated to copy 6 woff2 files (latin + latin-ext per weight) | |
