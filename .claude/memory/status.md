# Status (read first; rewrite after every change)

Updated: 2026-11-23 · Branch: `dev` · Active phase: **1 – Registre et écolage (planned, not started)** · Phases done: 1/7 (+ bootstrap)

## In progress
- Phase 1 task 4 (niveaux et classes) about to start. Phase 0 closed; phase 1 paused on purpose: the user is building the separate side project **Cairn** (`~/chezmoi/dev/sideProjects/cairn`, remote `Randriantahina/cairn`) first, then resumes phase 1.

## Last done
- 2026-11-23 · Repo bootstrap: Laravel 13 + Pest 5, Vue 3 + Vite + Tailwind 4 + shadcn-vue + PWA, pnpm, CI workflows, rules, `main`/`dev` branches pushed.
- 2026-11-23 · Project memory system with hooks. Packages (Sanctum, permission, activitylog) installed.
- 2026-11-23 · Tenancy (`BelongsToEcole`, `TenantContext`, `Ecole`) and identity (`Utilisateur`, roles, lockout): 30 tests green.
- 2026-11-24 · Phase 1 task 3: `AnneeScolaire` slice, 140 backend tests green.
- 2026-11-23 · Phase 1 task 2: `compteurs` + `CompteurRepository`, 116 backend tests green.
- 2026-11-23 · Phase 1 task 1: `Money` value object, 108 backend tests green.
- 2026-11-23 · Auth API and team management (policy-protected): 93 backend tests green (incl. settings, password reset, audit).

## Next action
Phase 1 task 4: TDD `Niveau` and `Classe` (`app/Domain/Scolarite`). Spec lines ~388-396 (`niveaux`, `classes` UQ(ecole_id, annee_scolaire_id, nom), `effectif_max` only WARNS, RG-I2). Same slice layout as `AnneeScolaire` (Contract, Eloquent repo, Service, DTOs, Requests, Resource, Policy, routes, tenant isolation test, 404 on foreign uuid). Classes belong to a year (`annee_scolaire_id`), so resolve the year by uuid inside the actor's school.

## Blockers / open questions
- None technical. Spec items `[À confirmer]` (real Excel file, bulletin format, fees) are not available: build with the spec's hypotheses, keep rules parametrable.

## Environment notes (non-derivable)
- SQLite (tests) ignores `lockForUpdate`: concurrency of `compteurs` is not proven by the suite; run a real two-process check on PostgreSQL before phase 1 closes.
- This project's commit-guard hook checks Écolio's memory even for commits in other repos opened from here (fixed in Cairn's version of the hook).
- Project settings deny `curl`/`wget` and reading `backend/.env*`: live HTTP smoke tests are not possible for Claude; rely on Pest Feature tests. Never grep `.env`; use `php artisan env`.
- Frontend TS: `erasableSyntaxOnly` bans constructor parameter properties and enums; if `pnpm typecheck` shows phantom errors, `rm -rf frontend/node_modules/.tmp`. Promise<unknown>.catch loses types: use the `rejectionOf<E>` helper pattern in tests.
- `.env.example` cannot be edited by Claude (deny rule `Read(./backend/.env.*)`): it still says `APP_LOCALE=en`; code default is `fr`. Ask the user to switch it, or relax the rule.
- Mail in dev/test: config `app.frontend_url` (env `FRONTEND_URL`) builds reset links.
- A Unit test cannot touch Eloquent models with dates (app not booted): such tests go in `tests/Feature`.
- The commit-guard hook runs BEFORE the command: update memory files in a separate tool call, then commit.
- Node pinned via `.mise.toml` (26.8.1); use `pnpm` only. The `mise` shim for pnpm fails on other Node versions.
- Composer hits GitHub rate limits without auth: export `COMPOSER_AUTH` from `gh auth token` inline for one command; never print or store the token.
- Backend tests use in-memory SQLite (`phpunit.xml`); production target is PostgreSQL, so avoid SQLite-only SQL and verify JSON queries on both.
- Pest 5 runs on PHPUnit 13; Pest prints a star-the-repo prompt on `--init`, answer nothing.
- Laravel's generated `backend/CLAUDE.md` / `AGENTS.md` were removed on purpose (they ask for curl installs).
- The user frequently interrupts tool calls; keep steps small and commit often.
