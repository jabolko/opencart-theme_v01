# ADR-003: Asset Build Pipeline

**Date:** 2026-03-03
**Status:** Accepted

## Decision
Dart Sass + clean-css-cli + uglify-js, orchestrated via npm scripts.

## Rationale
- Dart Sass is the canonical modern Sass implementation (LibSass is deprecated)
- No webpack/Vite/Parcel — the asset output is a single CSS and single JS file per page.
  A bundler adds complexity with zero benefit for this use case.
- clean-css-cli gives deterministic minification without a complex config file
- uglify-js handles ES5 JS minification with `-c` (compress) `-m` (mangle) flags

## Build Outputs
- `stylesheet/dist/theme.css` — compiled, unminified (for debugging)
- `stylesheet/dist/theme.min.css` — production CSS (what OC loads)
- `javascript/dist/theme.min.js` — production JS (what OC loads)

## Consequences
- No tree-shaking (not needed — no modules)
- No source maps by default — add `--source-map` to sass command if deep debugging needed
- `npm run dev` watches SCSS only — JS changes require manual `npm run js:minify`
