# Claude Project Brain — Navigation Guide

This folder is the single source of truth for how this theme is built.
Read this before reading anything else in the `claude/` folder.

## Folder Map

| Folder          | Contains                                                    |
|-----------------|-------------------------------------------------------------|
| `project/`      | Goals, scope, tech stack, dev environment details           |
| `design/`       | Visual design system, tokens, per-component specs           |
| `architecture/` | SCSS/Twig/JS conventions and architecture decision records  |
| `tasks/`        | Backlog, active work, completed work                        |
| `performance/`  | Score targets and historical tracking log                   |

## The Workflow Loop
1. Check `tasks/active.md` → know what to work on
2. Read the relevant component spec in `design/components/`
3. Follow the rules in `architecture/` for the file type you are editing
4. After finishing, update `tasks/active.md` and `tasks/done.md`
5. After any visual change, note the impact in `performance/log.md`

## Where to Go For…

| Question | File |
|----------|------|
| What are we building? | `project/overview.md` |
| What tech versions are we using? | `project/tech-stack.md` |
| How do I start Docker? | `project/dev-environment.md` |
| What color goes here? | `design/tokens.md` |
| What does this page look like? | `design/components/<name>.md` |
| How do I structure my SCSS? | `architecture/scss-architecture.md` |
| What Twig variables are available? | `architecture/twig-conventions.md` |
| Why did we make this decision? | `architecture/decisions/ADR-*.md` |
| What should I work on next? | `tasks/backlog.md` |
| What's the Lighthouse target? | `performance/targets.md` |
