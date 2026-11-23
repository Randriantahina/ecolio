# Status (read first; rewrite after every change)

Updated: 2026-11-23 · Branch: `dev` · Active phase: **0 – Fondations** · Phases done: 0/7 (+ bootstrap done)

## In progress
- Phase 0 backend: tenancy, identity, roles, auth API done; next is audit (activitylog), then the frontend shell.

## Last done
- 2026-11-23 · Repo bootstrap: Laravel 13 + Pest 5, Vue 3 + Vite + Tailwind 4 + shadcn-vue + PWA, pnpm, CI workflows, rules, `main`/`dev` branches pushed.
- 2026-11-23 · Project memory system with hooks. Packages (Sanctum, permission, activitylog) installed.
- 2026-11-23 · Tenancy (`BelongsToEcole`, `TenantContext`, `Ecole`) and identity (`Utilisateur`, roles, lockout): 30 tests green.
- 2026-11-23 · Auth API and team management (policy-protected): 84 backend tests green (incl. school settings, password reset).

## Next action
Phase 0, TDD: (1) activitylog on `Utilisateur` and `Ecole` (`LogsActivity`, log only meaningful attributes never `mot_de_passe`, causer = acting user, store `ecole_id` on the row; read-only endpoint `GET /api/v1/audit` for directeur only, per spec M10 audit table); (2) then backend phase-0 is complete: tick the backend section and start the FRONTEND shell (router, i18n, API client, auth feature MVVM, login view, PWA icons, offline indicator, Dexie).

## Blockers / open questions
- None technical. Spec items `[À confirmer]` (real Excel file, bulletin format, fees) are not available: build with the spec's hypotheses, keep rules parametrable.

## Environment notes (non-derivable)
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
