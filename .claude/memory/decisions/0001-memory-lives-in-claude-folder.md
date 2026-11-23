# 0001 · Project memory lives in `.claude/memory/`, process in `.claude/rules/process/`

- Date: 2026-11-23 · Status: accepted
- Context: sessions start with no context; one big progress file would grow unreadable. A reusable method is wanted without installing a framework per project.
- Decision: split memory by lifespan (status / roadmap / phases / changelog / decisions / architecture). Generic process rules in `rules/process/`, stack rules in `rules/stack/`. Enforcement by two hooks (inject status at SessionStart, block commits without a memory change). Reset for a new project with `/bootstrap-project` and `.claude/templates/`.
- Why: copying only `.claude/` to a new project carries the whole method; hooks make the discipline deterministic instead of relying on model recall.
- Rejected: a single `PROGRESS.md` (too long); an external framework (extra install, user wants own method); memory outside the repo (not versioned, not portable).
- Inspiration: the public `ai-driven-dev/framework` layout (`memory/`, `tasks/`), re-implemented as plain rules.
