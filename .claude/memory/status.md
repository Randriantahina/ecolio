# Status (read first; rewrite after every change)

Updated: 2026-11-24 · Branch: `dev` · Active phase: **1 – Registre et écolage (UI rebuilt on Filament)** · Phases done: 1/7 (+ bootstrap)

## In progress
- Architecture (decisions 0004 + 0005): Filament 5 panel `ecole`, every bounded context is an InterNACHI/Modular module in `app-modules/` (tenancy, platform, identity, audit, scolarite, eleves, finance, absences), each with its plugin, provider, migrations, tests. Eleves, Scolarite, Finance, Absences (roll-call page) UI and dashboard widgets (headcount, money) done and tested (257 tests). Remaining Phase 1: grouped sibling payments, real PostgreSQL concurrency check of `compteurs`, Playwright-free browser check of the demo. Then Phases 2-6 + Transverse, everything real, tested, CI green.

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
User wants EVERYTHING in the spec (tested together at the end). Done: Phase 0, Phase 1, dashboard charts, custom fields (module `dynamique`). Next, in order, one commit each: 1) saved views (`vues_tableaux`: save/load table state per user, shared flag) + Excel/CSV export of the register (maatwebsite/excel, needs composer require: packagist only). 2) module `imports`: 5-step Filament wizard + staging `import_lignes` + 7-day rollback. 3) Phase 3 tutors/siblings, re-enrolment, mass promotion, grouped payments, discounts. 4) Phase 4 notes/bulletins. 5) Phase 5 communications + parent portal. 6) Phase 6 offline sync. 7) super_admin back-office. Tuteur entity will need its own `LecteurValeursChamps` tagged 'champs.lecteurs'.

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
- Filament Livewire tests: Livewire swallows aborts, assert `->assertNotFound()` not exceptions. Panel pages under `/ecole/{slug}`; `Utilisateur::canAccessPanel` admits active directeur/secretaire/enseignant only.
- `composer require` of Filament needed `COMPOSER_AUTH` (GitHub token); the user ran it in their own shell.
- Filament array-record tables (registre): after an action, build a fresh `Livewire::test` in tests (records are cached per component); after failed action validation use a new component too. Custom Blade is limited (no Node, so no Tailwind build): prefer Filament components.
- Modules migration (2026-11-24): moved 211 files with `git mv` (history kept) using a class map; activity-log/permission rows of old dev DBs reference old class names: use `migrate:fresh --seed`. Module tests are discovered by Pest through `in('Feature','../app-modules/*/tests/Feature')`.
- Locale is forced to `fr` in config/app.php (the .env says `en` and CI copies .env.example): never read it from the environment. Filament widgets are lazy: HTTP tests cannot assert their text, use `Livewire::test(Widget::class)`.
- Fee model (explained to the user 2026-11-24): type de frais (periodicite) -> tarif (amount per year/level/class) -> echeances generated per enrolment. Register tabs: Écolages = mensuel/trimestriel (class + month filter), Inscriptions = unique/annuel (class only), derived from `Periodicite::estPonctuelle()`. Filament injects closure params by NAME: a filter ->query() closure must name its builder `$query` (a `$q` gets a fresh empty builder and silently filters nothing).
