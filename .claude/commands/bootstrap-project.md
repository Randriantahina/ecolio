---
description: Reset the project memory from templates and prepare this .claude/ for a new project
argument-hint: <project name> [path to spec]
---

You are bootstrapping a **new project** with this portable `.claude/` folder. Arguments: $ARGUMENTS

1. Read `.claude/rules/process/00-session-protocol.md` and `02-memory-journal.md` so the method is fresh.
2. Ask the user (one question block) only for what is missing: project name, one-line purpose, stack, spec or brief location, test commands.
3. **Reset memory**: delete everything under `.claude/memory/` except `README.md`, then recreate `status.md`, `roadmap.md`, `project-brief.md`, `architecture.md`, `changelog/<current YYYY-MM>.md` and `phases/phase-0-*.md` from `.claude/templates/`, filling the placeholders. Derive phases and their definition of done from the spec; do not invent scope.
4. **Adapt the stack rules**: keep `rules/process/` as is; replace or edit `rules/stack/*` for the new stack (paths, layers, test runner); update `permissions` in `.claude/settings.json` for the package manager and test commands.
5. Tell the user to run `/init` to generate the root `CLAUDE.md`, and to add `@` pointers there only if needed (memory is already injected by the SessionStart hook).
6. Make sure `.claude/hooks/*.sh` are executable, then commit on `dev` (create it if missing): `chore(claude): bootstrap project memory and rules`.

Never keep the previous project's decisions, changelog or status.
