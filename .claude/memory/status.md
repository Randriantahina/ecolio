# Status (read first; rewrite after every change)

Updated: 2026-11-24 · Branch: `dev` · Active phase: **1 – Registre et écolage (restructure to Inertia in progress)** · Phases done: 1/7 (+ bootstrap)

## In progress
- RESTRUCTURE (decision 0003): DONE so far: Laravel moved to repo root, Inertia 3 + Vue + shadcn + vue-i18n wired, Laravel Boost + MCP installed, session login/logout + shared props + server-side menu, CI merged into `ci.yml`, rules and CLAUDE.md rewritten. TO DO: port screens from the old SPA (read them with `git show 673c07a:frontend/<path>`): élèves list/detail/edit/départ, registre écolage + paiement + reçu PDF, absences, réglages (années, niveaux, classes, types de frais, tarifs); then DELETE `frontend/` (user asked); then Phase 1 leftovers and ALL later phases. User wants everything REAL (no prototype shortcuts), picky UI (no generic AI look, no "facture", no cantine).

## Last done
- 2026-11-23 · Repo bootstrap: Laravel 13 + Pest 5, Vue 3 + Vite + Tailwind 4 + shadcn-vue + PWA, pnpm, CI workflows, rules, `main`/`dev` branches pushed.
- 2026-11-23 · Project memory system with hooks. Packages (Sanctum, permission, activitylog) installed.
- 2026-11-23 · Tenancy (`BelongsToEcole`, `TenantContext`, `Ecole`) and identity (`Utilisateur`, roles, lockout): 30 tests green.
- 2026-11-24 · Phase 1 tasks 13-14: absences and departure, 185 backend tests green.
- 2026-11-24 · Phase 1 tasks 4-12: levels/classes, élèves+inscriptions, finance (receipts, void, register), 175 backend tests green.
- 2026-11-24 · Phase 1 task 3: `AnneeScolaire` slice, 140 backend tests green.
- 2026-11-23 · Phase 1 task 2: `compteurs` + `CompteurRepository`, 116 backend tests green.
- 2026-11-23 · Phase 1 task 1: `Money` value object, 108 backend tests green.
- 2026-11-23 · Auth API and team management (policy-protected): 93 backend tests green (incl. settings, password reset, audit).

## Next action
1) Port `Eleves/Index` (list + search + create dialog + duplicate confirm) as the reference Inertia CRUD page: web controller + FormRequest reuse + Pest `assertInertia` tests, add `eleves` to `MenuPrincipal`. 2) Registre écolage, absences, réglages, élève detail/edit/départ. 3) `git rm -r frontend` when nothing is left to port. Run the app: `php artisan serve` on :8000 after `pnpm build` (or `pnpm dev` for HMR); logins directeur|secretaire|enseignant@demo.ecolio.test / password.

## Blockers / open questions
- None technical. Spec items `[À confirmer]` (real Excel file, bulletin format, fees) are not available: build with the spec's hypotheses, keep rules parametrable.

## Environment notes (non-derivable)
- SQLite (tests) ignores `lockForUpdate`: concurrency of `compteurs` is not proven by the suite; run a real two-process check on PostgreSQL before phase 1 closes.
- This project's commit-guard hook checks Écolio's memory even for commits in other repos opened from here (fixed in Cairn's version of the hook).
- Project settings deny `curl`/`wget` and reading `./.env*`: live HTTP smoke tests are not possible for Claude; rely on Pest Feature tests. Never grep `.env`; use `php artisan env`.
- TS: `erasableSyntaxOnly` bans constructor parameter properties and enums; if `pnpm typecheck` shows phantom errors, `rm -rf node_modules/.tmp`. Promise<unknown>.catch loses types: use the `rejectionOf<E>` helper pattern in tests.
- `.env.example` cannot be edited by Claude (deny rule `Read(./.env.*)`): it still says `APP_LOCALE=en`; code default is `fr`. Ask the user to switch it, or relax the rule.
- Mail in dev/test: config `app.frontend_url` (env `FRONTEND_URL`) builds reset links.
- A Unit test cannot touch Eloquent models with dates (app not booted): such tests go in `tests/Feature`.
- The commit-guard hook runs BEFORE the command: update memory files in a separate tool call, then commit.
- Node pinned via `.mise.toml` (26.8.1); use `pnpm` only. The `mise` shim for pnpm fails on other Node versions.
- Composer hits GitHub rate limits without auth: export `COMPOSER_AUTH` from `gh auth token` inline for one command; never print or store the token.
- Backend tests use in-memory SQLite (`phpunit.xml`); production target is PostgreSQL, so avoid SQLite-only SQL and verify JSON queries on both.
- Pest 5 runs on PHPUnit 13; Pest prints a star-the-repo prompt on `--init`, answer nothing.
- Laravel's generated `backend/CLAUDE.md` / `AGENTS.md` were removed on purpose (they ask for curl installs).
- The user frequently interrupts tool calls; keep steps small and commit often.
- The Claude session exports `APP_ENV=local`; `phpunit.xml` forces `testing` (env + server) so CSRF and test behaviour stay hermetic.
- Laravel Boost MCP (`.mcp.json`) needs approval at session start; `search-docs` is the reference for Inertia 3 / Laravel 13 APIs. `boost.json` lists skills (cloud skill removed on purpose, hosting undecided). Boost wrote files for other agents on install: deleted, Claude Code only.
- Deleted the stock `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` (Laravel scaffolds asserting the old welcome page; superseded by `tests/Feature/Web/AuthTest.php`).
