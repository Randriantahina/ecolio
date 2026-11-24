# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Écolio**: multi-tenant web app (installable PWA later) for private-school management (student registry by matricule, tuition tracking with numbered receipts, absences, Excel import, later grades and report cards), sold by subscription. The product language is **French** (UI, domain vocabulary, table and column names without accents, e.g. `eleves`, `paiements`, `echeances`). Technical identifiers, code comments and commit messages are **English**.

The functional and technical source of truth is `docs/SPEC_ecolio_2026-11-18_v2.md` (business rules tagged `RG-*`, acceptance criteria `CA`). Items marked `[Hypothèse]` or `[À confirmer]` there are not validated: never present them as facts. The build order follows the phases in spec section 11; only the phase currently requested is in scope.

## Repository layout

One Laravel 13 application at the repository root (decisions `0003`, `0004`): PHP 8.5, Pest 5, **Filament 5** panel (Livewire) for the whole web UI; no Node toolchain. PostgreSQL in prod, in-memory SQLite in tests. Laravel Boost (dev) provides the MCP server (`.mcp.json`) and the guidelines block at the end of this file.

- `app/`, `routes/`, `database/`, `tests/`: the application shell (panel provider, API routes, demo seeder, panel/CORS/seeder tests). `routes/web.php` only redirects to the panel, `routes/api.php` keeps the JSON API (`/api/v1`) for the offline queue and future mobile.
- `app-modules/<context>/`: one Composer module per bounded context (InterNACHI/Modular, decision `0005`): `src/Domain`, `src/Application`, `src/Infrastructure`, `src/Http`, `src/Filament`, its plugin `src/<Name>Plugin.php`, `database/`, `tests/`. The panel is `app/Providers/Filament/EcolePanelProvider.php` (`/ecole/{slug}`); `app/` holds only the shell.
- `docs/`: spec, OpenAPI, architecture decisions
- `.claude/rules/process/`: portable workflow rules (session protocol, TDD, memory journal, comments, commits, branches, CI). `.claude/rules/stack/`: Laravel DDD and Filament rules. **Read them before writing code.**
- `.claude/memory/`: **project memory** (status, roadmap, per-phase checklists, changelog, decisions). **Read `status.md` and `roadmap.md` at the start of every session and update them in every commit**; a SessionStart hook injects them and a PreToolUse hook blocks commits without a memory change.

`composer.lock` is committed. Composer needs a GitHub token for Filament downloads: the user runs `composer` with `COMPOSER_AUTH` themselves.

## Commands

```bash
composer install                                               # needs COMPOSER_AUTH (GitHub token), run by the user
php artisan migrate --seed                                     # demo school (local only)
php artisan serve                                              # app on :8000, panel at /ecole
./vendor/bin/pest                                              # whole suite
./vendor/bin/pest tests/Feature/Filament/LoginTest.php         # one file
./vendor/bin/pest --filter="locks the account"                 # one test
./vendor/bin/pint                                              # format PHP
php artisan make:module notes                                  # new bounded context (then composer update modules/notes)
php artisan modules:list
```

## Architecture (big picture)

**Multi-tenancy (spec 6.3).** One database; every business table carries `ecole_id`. A `BelongsToEcole` trait adds a global scope and fills `ecole_id` on create; unique indexes are composite (`ecole_id`, ...); queued jobs carry `ecole_id` explicitly. Every model needs a test proving school A never sees school B. `super_admin` users have `ecole_id = null` and live outside the school app. Web routes run `auth` then `tenant` (the school comes from the signed-in user only).

**Backend: layered DDD inside each module under `app-modules/<context>/src/`.** `Http` → `Application` → `Domain` ← `Infrastructure`.
- `Domain/<Context>/`: models, enums, value objects (`Money` in integer ariary), events, exceptions, repository **interfaces**.
- `Application/<Context>/`: Services (business logic), DTOs (the only thing crossing layers), Actions.
- `Infrastructure/`: Eloquent repositories, PDF/Excel/SMS adapters, bound to interfaces in `DomainServiceProvider`.
- `Http/`: thin Controllers for the JSON API, FormRequests (→ DTO), API Resources, Policies. `Filament/<Context>/`: panel plugins, as thin as controllers (call a Service, never business logic).
Bounded contexts: Platform, Identity, Scolarite, Eleves, Finance, Absences, Notes, Imports, Communications.

**UI: one Filament panel, one plugin per module** (structure imposed by the Filament modular guide: https://filamentphp.com/docs/5.x/advanced/modular-architecture, rules in `.claude/rules/stack/filament-modular-ddd.md`). The panel `ecole` is tenant-aware (`->tenant(Ecole::class)`); `BindEcoleContext` copies the tenant into `TenantContext` so the global scope still applies. Resources, pages and widgets of a module live in `src/Filament/` and are discovered by that module's plugin, attached to the panel by the module's own service provider; mutations go through Application Services, domain exceptions become Filament notifications. Login is a custom page over `AuthService` (lockout RG-A3). Staff only: parents get their own portal later.

**Invariants that span files (spec M4, M5, section 8).**
- Receipt numbers are gapless per school and year, allocated from `compteurs` under `SELECT ... FOR UPDATE` inside the payment transaction. Receipts are never edited or deleted, only voided.
- Checking a box creates a payment; unchecking voids it with a mandatory reason and an audit entry. No "paid" state exists without a receipt.
- Amounts are integer ariary (`*_ar`), never floats. Echeance status is derived and recomputed in the same transaction as any payment or void.
- Offline writes (absences, tuition) carry a `client_uuid` as idempotency key; offline receipts get a provisional `PROV-xxxx` number until sync.
- Custom fields (JSON `champs_perso`) never feed financial or grade computation.

## Conventions

- **TDD is mandatory**: failing test first, then code, then refactor. Every class has a Pest (Feature or Unit) test; every composable and component with logic has a Vitest test.
- **Comments explain why, never how** (business rule, constraint, trade-off). See `.claude/rules/process/03-comments.md`.
- **Commits**: English Conventional Commits (`feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `build`, `ci`, `perf`, `style`), one logical change each, committed after every green change. See `.claude/rules/process/04-commits.md`.
- **Branching**: all work (feat, fix, everything) is committed on `dev`; no per-feature branches. `main` only receives `dev` via PR after green CI. See `.claude/rules/process/05-branches-and-push.md`.
- **CI** (`.github/workflows/ci.yml`): one `php` job, Pint then Pest. Run the same gates locally before committing. See `.claude/rules/process/06-ci.md`.
- **Test gate before push**: after each feature or fix, all tests and gates must be green; then commit and `git push origin dev` without asking. Never push a red state. Pushing `main` or other branches needs user approval.
- Remote `origin` is `git@github.com:Randriantahina/ecolio.git`.

**Precedence:** the project rules above and in `.claude/rules/` win over the generated Laravel Boost block below when they conflict (comments explain why, pages live in `resources/js/pages`, deployment target not chosen yet). Boost stays authoritative for Laravel and Inertia API usage (`search-docs`).

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
