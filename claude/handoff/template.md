# [Component Name] — Design Handoff

> Copy this file to `claude/handoff/active/[component-name].md` and fill it in.

---

## Status

| | |
|---|---|
| Design | COMPLETE / IN PROGRESS |
| Prototype file | `prototypes/[filename]-v[N].html` |
| Chosen variation | V[N] — [brief description] |
| Implementation | PENDING / IN PROGRESS / DONE |
| Implemented by | — |
| Date completed | — |

---

## What This Component Does

[1–2 sentences. What is it, where does it live on the page, what job does it do for the user?]

---

## Chosen Design

**Variation:** V[N] — [name]

**Why this one:**
[1–2 sentences from Opus on why this variation was recommended]

**User decision needed:**
[Any open choices the user still needs to make, or "none"]

---

## Twig Location

- **Template file:** `template/common/home.twig` (or other)
- **Section class:** `.home-[component]`
- **Position:** After `.home-[previous]`, before `.home-[next]`

---

## SCSS Location

- **Partial:** `stylesheet/src/pages/_home.scss` (or new partial if complex)
- **BEM block:** `.home-[component]`

---

## Implementation Notes for Sonnet

> Bullet notes from Opus describing the CSS structure. Sonnet should read these before writing any code.

- [ ] [Note 1 — e.g. "Use CSS Grid, 5 columns equal width, 3px gap, no border-radius"]
- [ ] [Note 2 — e.g. "Dark overlay via ::before pseudo-element, z-index: 1"]
- [ ] [Note 3 — e.g. "Text z-index: 2 to sit above overlay"]
- [ ] [Note 4 — e.g. "Mobile: 2 columns, last tile spans full width via grid-column: 1 / -1"]
- [ ] [Note 5 — e.g. "Hover: scale(1.025) on tile, brightness(0.88) on image, darken overlay"]

---

## Design Tokens Used

List only the tokens this component actually uses (from `claude/design/tokens.md`):

| Token | Variable | Value | Used for |
|-------|----------|-------|----------|
| color-primary | `$color-primary` | #f9b94a | [e.g. badge background, number color] |
| color-text-heading | `$color-text-heading` | #2b2e35 | [e.g. category name] |
| [add rows as needed] | | | |

---

## Assets Needed

- [ ] Real category photos: `image/catalog/assets/categories/[category].jpg`
- [ ] [Any other images or icons]

---

## Acceptance Criteria

- [ ] Matches chosen variation in prototype
- [ ] Hover states work
- [ ] Mobile: 2-column grid below 768px
- [ ] No raw px values (use rem or spacing tokens)
- [ ] Build passes (`npm run build` in theme root)
- [ ] [Any other specific checks]
