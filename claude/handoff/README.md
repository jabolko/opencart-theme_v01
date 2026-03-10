# Design Handoff System

## Roles

| Model | Tool | Job |
|-------|------|-----|
| Claude Opus 4.6 | Claude.ai (Mac app / web, in a Project) | Design sprints — HTML prototypes |
| Claude Sonnet 4.6 | Claude Code (VS Code) | Implementation — SCSS, Twig, JS, build |

---

## Workflow

### Step 1 — Opus design session
1. Open the **"Otroški kotiček Design" Project** in Claude.ai
   - Project Instructions = contents of `opus-context.md` (check `## Current Build State` is current)
2. Start a new chat, state your task: *"Design section X — 3 variations"*
3. Opus outputs a self-contained HTML prototype with a `=== SONNET HANDOFF ===` comment at the bottom

### Step 2 — Save the prototype
Save the HTML to `prototypes/[component]-v[N].html`
That file is the complete handoff — no separate brief needed.

### Step 3 — Tell Sonnet to implement
In VS Code:
> "Implement `prototypes/category-strip-v1.html` — use V2"

Sonnet reads the prototype + the `SONNET HANDOFF` comment block, then writes SCSS + Twig.

### Step 4 — Sonnet updates build state
After a successful build, Sonnet updates `## Current Build State` in `opus-context.md`
so the next Opus session knows what's already done.

---

## Folder Structure

```
claude/handoff/
├── README.md           ← this file
├── opus-context.md     ← Opus project instructions (paste into Claude.ai Project)
└── template.md         ← optional: use for complex components that need extra notes
```

Prototypes live in `prototypes/` at the project root (not in this folder).

---

## Rules
- `opus-context.md` → `## Current Build State` must be updated by Sonnet after every completed section
- Opus must always include the `=== SONNET HANDOFF ===` comment block in its HTML output
- Sonnet must confirm the build passes before marking a section as DONE
- One prototype file per component — don't batch sections
