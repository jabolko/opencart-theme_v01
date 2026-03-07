# CLAUDE.md — otroskikoticek Theme Project

## What This Project Is
Custom OpenCart 3.0.5.0 theme for a Slovenian children's e-commerce store.
Theme name: otroskikoticek. Language: Slovenian. Platform: PHP 8.4, MySQL 8.0, Apache, Docker.

## How to Orient Yourself
- Read `claude/project/overview.md` for goals and scope
- Read `claude/design/design-system.md` for visual rules before writing any CSS
- Read `claude/architecture/scss-architecture.md` before touching any .scss file
- Read `claude/architecture/twig-conventions.md` before touching any .twig file
- Check `claude/tasks/active.md` before starting any work — it shows the current focus
- Check `claude/handoff/active/` for pending design handoffs from Opus — implement these first

## Design ↔ Code Workflow (Opus + Sonnet)
This project uses two Claude models in tandem:
- **Opus** (Mac app): design sprints — HTML prototypes in `prototypes/`, design decisions
- **Sonnet** (VS Code / Claude Code): implementation — SCSS, Twig, JS

**Handoff system:** `claude/handoff/`
- `opus-context.md` — paste into every Opus session to give it project context
- `template.md` — copy to `active/[component].md` to start a new handoff
- `active/` — components ready for Sonnet to implement
- `done/` — completed handoffs

When a user says "implement [component]", read `claude/handoff/active/[component].md` first.

## Where Things Live

### Theme Source Files
- SCSS source:    `opencart/catalog/view/theme/otroskikoticek/stylesheet/src/`
- Compiled CSS:   `opencart/catalog/view/theme/otroskikoticek/stylesheet/dist/`
- JS source:      `opencart/catalog/view/theme/otroskikoticek/javascript/src/`
- Compiled JS:    `opencart/catalog/view/theme/otroskikoticek/javascript/dist/`
- Twig templates: `opencart/catalog/view/theme/otroskikoticek/template/`

### Documentation
- All planning, specs, and decisions: `claude/`

### Performance Reports
- Lighthouse HTML reports: `reports/`
- Score log: `claude/performance/log.md`

## Build Commands
Run from: `opencart/catalog/view/theme/otroskikoticek/`

| Command              | What it does                                         |
|----------------------|------------------------------------------------------|
| `npm run dev`        | Watch SCSS and recompile on save                     |
| `npm run build`      | Full production build: SCSS + CSS minify + JS minify |
| `npm run scss:build` | Compile SCSS to CSS (compressed)                     |
| `npm run css:minify` | Minify CSS → theme.min.css                           |
| `npm run js:minify`  | Minify JS → theme.min.js                             |

## Dev Environment
- Web (OpenCart): http://localhost:8080
- phpMyAdmin:     http://localhost:8081
- Docker start:   `docker compose up -d`
- Docker stop:    `docker compose down`

## Critical Rules — Read Before Writing Code

### SCSS
- All variables MUST be defined as tokens in `claude/design/tokens.md` before use in code
- Follow the 7-1 pattern defined in `claude/architecture/scss-architecture.md`
- Never write styles in `theme.scss` directly — it is the entry point only, use partials
- No `#id` selectors for styling, no nesting >3 levels deep, no `!important`
- All media queries use the `respond-to()` mixin — no raw `@media` inline

### Twig
- Never duplicate logic that OpenCart controllers already provide
- Always reference `claude/architecture/twig-conventions.md` for available variables per template
- Keep templates logic-light: conditionals yes, business logic no
- OpenCart uses Twig 1.x — do not use Twig 3 features

### JavaScript
- jQuery 3.7.1 is already loaded by OpenCart — do not load it again
- All custom JS goes in `javascript/src/theme.js`
- Write ES5 only (no `let`/`const`, no arrow functions)
- Follow patterns in `claude/architecture/js-conventions.md`

### Git
- Commit message format: `[area] Short description`
- Areas: `scss`, `twig`, `js`, `build`, `docs`, `perf`
- Example: `[scss] Add product card hover state`

## Performance Targets
See `claude/performance/targets.md` — do not ship changes that drop Lighthouse below targets.

## What Claude Should NOT Do
- Do not modify any file under `opencart/admin/` (admin panel — separate concern)
- Do not modify `opencart/catalog/view/theme/default/` (default theme — reference only)
- Do not alter `docker-compose.yml` without explicit instruction
- Do not add npm packages without discussing trade-offs first
