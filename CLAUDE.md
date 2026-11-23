# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Écolio**: multi-tenant PWA for private-school management (student registry by matricule, tuition tracking with numbered receipts, absences, Excel import, later grades and report cards), sold by subscription. The product language is **French** (UI, domain vocabulary, table and column names without accents, e.g. `eleves`, `paiements`, `echeances`). Technical identifiers, code comments and commit messages are **English**.

The functional and technical source of truth is `docs/SPEC_ecolio_2026-11-18_v2.md` (business rules tagged `RG-*`, acceptance criteria `CA`). Items marked `[Hypothèse]` or `[À confirmer]` there are not validated: never present them as facts. The build order follows the phases in spec section 11; only the phase currently requested is in scope.

## Repository layout

Monorepo with no shared tooling at the root:

- `backend/`: Laravel 13 JSON API (PHP 8.5, Pest 5; PostgreSQL in prod, in-memory SQLite in tests)
- `frontend/`: Vue 3 + TypeScript SPA/PWA (Vite, Tailwind 4, shadcn-vue on Reka UI, **pnpm**)
- `docs/`: spec, OpenAPI, architecture decisions
- `.claude/rules/`: engineering rules (TDD, DDD, SOLID, MVVM, commits, comments). **Read them before writing code.**

Node is pinned in `.mise.toml`. Use pnpm only; lockfiles (`pnpm-lock.yaml`, `composer.lock`) are committed.

## Commands

Backend (from `backend/`):

```bash
composer install
php artisan migrate
php artisan serve
./vendor/bin/pest                                              # whole suite
./vendor/bin/pest tests/Feature/Finance/PaiementTest.php       # one file
./vendor/bin/pest --filter="refuses a payment above the balance"  # one test
./vendor/bin/pest --coverage --min=80
./vendor/bin/pint                                              # format
./vendor/bin/pint --test                                       # format check
```

Frontend (from `frontend/`):

```bash
pnpm install
pnpm dev
pnpm test                                    # vitest run
pnpm vitest run src/features/paiements/paiement.viewmodel.test.ts   # one file
pnpm vitest run -t "encaisse"                # one test by name
pnpm typecheck
pnpm lint
pnpm build                                   # vue-tsc + vite build + service worker
pnpm e2e                                     # Playwright
pnpm dlx shadcn-vue@latest add button        # add a UI component
```

## Architecture (big picture)

**Multi-tenancy (spec 6.3).** One database; every business table carries `ecole_id`. A `BelongsToEcole` trait adds a global scope and fills `ecole_id` on create; unique indexes are composite (`ecole_id`, ...); queued jobs carry `ecole_id` explicitly. Every model needs a test proving school A never sees school B. `super_admin` users have `ecole_id = null` and live outside the school app.

**Backend: layered DDD under `backend/app/`.** `Http` → `Application` → `Domain` ← `Infrastructure`.
- `Domain/<Context>/`: models, enums, value objects (`Money` in integer ariary), events, exceptions, repository **interfaces**.
- `Application/<Context>/`: Services (business logic), DTOs (the only thing crossing layers), Actions.
- `Infrastructure/`: Eloquent repositories, PDF/Excel/SMS adapters, bound to interfaces in a service provider.
- `Http/`: thin Controllers, FormRequests (→ DTO), API Resources, Policies.
Bounded contexts: Platform, Identity, Scolarite, Eleves, Finance, Absences, Notes, Imports, Communications.

**Frontend: feature-sliced MVVM under `frontend/src/`.** Each `features/<feature>/` holds the Model (`domain/` types, Zod schemas, pure rules; `data/` repository over the API client and Dexie), the ViewModel (`*.viewmodel.ts`, state and commands, repository injected), and the View (`ui/*.vue`, presentational only). Shared code in `shared/`; UI kit in `components/ui`. Server state goes through TanStack Query.

**Invariants that span files (spec M4, M5, section 8).**
- Receipt numbers are gapless per school and year, allocated from `compteurs` under `SELECT ... FOR UPDATE` inside the payment transaction. Receipts are never edited or deleted, only voided.
- Checking a box creates a payment; unchecking voids it with a mandatory reason and an audit entry. No "paid" state exists without a receipt.
- Amounts are integer ariary (`*_ar`), never floats. Echeance status is derived and recomputed in the same transaction as any payment or void.
- Offline writes (absences, tuition) carry a `client_uuid` as idempotency key; offline receipts get a provisional `PROV-xxxx` number until sync.
- Custom fields (JSON `champs_perso`) never feed financial or grade computation.

## Conventions

- **TDD is mandatory**: failing test first, then code, then refactor. Every class, composable and component has a Pest (Feature or Unit) or Vitest test.
- **Comments explain why, never how** (business rule, constraint, trade-off). See `.claude/rules/01-comments-why-not-how.md`.
- **Commits**: English Conventional Commits (`feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `build`, `ci`, `perf`, `style`), one logical change each, committed after every green change. See `.claude/rules/git-commits.md`.
- Remote `origin` is `git@github.com:Randriantahina/ecolio.git`, branch `main`. Pushing requires user confirmation.
