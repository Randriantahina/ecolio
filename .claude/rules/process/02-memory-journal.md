# Memory journal: what goes where, and how to keep it small

The memory is split by **lifespan** so that the always-read part stays tiny and nothing is duplicated. One fact lives in exactly one file.

| File | Holds | Lifespan | Size cap |
|------|-------|----------|----------|
| `memory/status.md` | Where we are **right now**: active phase, in progress, last done, next action, blockers, environment notes | rewritten at every change | ≤ 60 lines |
| `memory/roadmap.md` | Phases with checkboxes, goal, definition of done, link to the phase file | ticked when a phase is done | ≤ 80 lines |
| `memory/phases/phase-N-*.md` | Task checklist of one phase, grouped (backend, frontend, tests, docs), with status | created when the phase starts, frozen when done | ≤ 150 lines |
| `memory/changelog/YYYY-MM.md` | Append-only history: one line per change | one file per month | split by month |
| `memory/decisions/NNNN-title.md` | A decision, its reason and rejected alternative | write once, supersede never edit | ≤ 30 lines each |
| `memory/architecture.md` | Map of what exists and how pieces connect, pinned versions, tooling facts | updated when structure changes | ≤ 100 lines |
| `memory/project-brief.md` | What the product is, who it is for, source-of-truth documents | rarely | ≤ 40 lines |

## Entry formats
Changelog line (newest at the bottom of the month file):
```
- 2026-11-23 · feat(finance) · gapless receipt numbers under row lock · 3f9a1c2 · pest 14 passed, pint ok
- 2026-11-24 · fix(imports) · reject duplicate matricule rows · 8bd02aa · root cause: unique check ignored case
```
A `fix` line states the **root cause**. Always absolute dates, the commit short SHA, and the proof (test counts).

Phase task line: `- [x] Tenant scope + trait (feat, done 2026-11-23, 3f9a1c2)` / `- [~] in progress` / `- [ ] todo` / `- [!] blocked: reason`.

Decision file: title, date, status (accepted/superseded by NNNN), context, decision, why, alternatives rejected.

## What to record (non-derivable only)
Record the **why**, the **state** and the **traps**. Never copy code, never restate what `git log` or the file tree already shows; point to the file instead (`app/Domain/Finance/...`). Environment quirks, rejected approaches, spec items still marked `[À confirmer]`, and anything that cost time to discover belong here.

## Hygiene
- Keep within the size caps; when a cap is hit, compress (summarise old status, archive done phase tasks into one line) instead of adding.
- Before adding a line, check whether another file already holds it; merge, don't duplicate.
- Memory is written in English; domain terms stay French.
- Memory edits are committed with the change they describe (`04-commits.md`).
- Never store secrets, tokens, real student data.
