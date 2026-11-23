# Status (read first; rewrite after every change)

Updated: 2026-11-23 · Branch: `dev` · Active phase: **0 – Fondations** · Phases done: 0/7 (+ bootstrap done)

## In progress
- Phase 0 backend: tenancy, identity, roles, auth API done; backend of phase 0 is COMPLETE (93 tests); frontend shell nearly done (login, router, i18n, indicator). Left: PWA icons/install prompt, Dexie bootstrap, CI check, phase-0 DoD.

## Last done
- 2026-11-23 · Repo bootstrap: Laravel 13 + Pest 5, Vue 3 + Vite + Tailwind 4 + shadcn-vue + PWA, pnpm, CI workflows, rules, `main`/`dev` branches pushed.
- 2026-11-23 · Project memory system with hooks. Packages (Sanctum, permission, activitylog) installed.
- 2026-11-23 · Tenancy (`BelongsToEcole`, `TenantContext`, `Ecole`) and identity (`Utilisateur`, roles, lockout): 30 tests green.
- 2026-11-23 · Auth API and team management (policy-protected): 93 backend tests green (incl. settings, password reset, audit).

## Next action
Phase 0 FRONTEND, remaining: (1) PWA: generate maskable icons 192/512 (use `pnpm dlx @vite-pwa/assets-generator` or hand-made SVG->PNG; no brand yet, a simple placeholder is fine and flagged), fill `manifest.icons` in `vite.config.ts`, install prompt shown on 2nd visit (`useInstallPrompt` composable + test); (2) Dexie bootstrap `shared/offline/db.ts` with empty `queue` table schema + test using fake-indexeddb; (3) watch the Frontend CI run on GitHub; (4) end-to-end smoke by running backend + frontend and logging in for real with a seeded directeur (create `database/seeders/DemoSeeder` guarded to local env); then tick phase 0 DoD in `roadmap.md` and start phase 1 (create `phases/phase-1-registre-ecolage.md` from `templates/phase.md`).

## Blockers / open questions
- None technical. Spec items `[À confirmer]` (real Excel file, bulletin format, fees) are not available: build with the spec's hypotheses, keep rules parametrable.

## Environment notes (non-derivable)
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
