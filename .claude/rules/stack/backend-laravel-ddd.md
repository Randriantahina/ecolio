---
paths:
  - "app/**/*.php"
  - "routes/**/*.php"
  - "tests/**/*.php"
---

# Backend architecture: Laravel 13, DDD, SOLID

Stack: PHP 8.5, Laravel 13, Pest 5, Sanctum, spatie/laravel-permission, spatie/laravel-activitylog, PostgreSQL. Every PHP file starts with `declare(strict_types=1);`.

## Layers (dependencies point inward only)
```
Http  →  Application  →  Domain  ←  Infrastructure
```
- `app/Domain/<Context>/`: models, enums, value objects, domain events, domain exceptions, **repository interfaces** (`Contracts/`). No HTTP, no concrete infra.
- `app/Application/<Context>/`: `Services/` (business logic), `DTOs/`, `Actions/`.
- `app/Infrastructure/`: `Persistence/Eloquent/` repository implementations, `Pdf/`, `Excel/`, `Sms/` adapters, bound to interfaces in a dedicated service provider.
- `app/Http/`: Controllers, FormRequests, API Resources, Middleware. Policies in `app/Policies`.

Bounded contexts: Platform, Identity, Scolarite, Eleves, Finance, Absences, Notes, Imports, Communications. A context talks to another through its Service or domain events, never through its models or tables.

## Mandatory patterns
- **Repository** for every query. Controllers and Services never call `Model::where()` or `DB::` directly. Interface in Domain, Eloquent implementation in Infrastructure. Filters, JSON-field queries and pagination live only there.
- **Service** for business logic. Controllers stay thin: validate → map to DTO → call one Service → return a Resource.
- **DTO**: `final readonly class`, built from the FormRequest via `toDto()`. DTOs (not arrays, requests or models) cross layer boundaries.
- **Value Objects**: `Money` (integer ariary, never float), `Matricule`, `NumeroRecu`, `Periode`. Immutable, self-validating, unit-tested.
- **Strategy** for varying rules (matricule format, grade calculation, payment mode); **Factory** for entities with invariants; **Domain events/Observer** for side effects (audit, notifications); **Adapter** for third parties (SMS, PDF, mobile money); **Unit of Work** = `DB::transaction` inside the Service.
- Native backed Enums for statuses; no magic strings.

## SOLID, applied
- **S**: one reason to change per class; split a Service when it spans several use-case families.
- **O**: add a Strategy/implementation instead of growing `if`/`switch` chains.
- **L**: every repository implementation honours its interface (shared contract test).
- **I**: small role-based interfaces (`PaiementRepository`, `RecuNumberGenerator`), no god interface.
- **D**: depend on interfaces, inject via constructor; no `app()` or facades in Domain/Application. Sole exception: Eloquent boot hooks and global scopes (`BelongsToEcole`, `EcoleScope`) resolve `TenantContext` from the container, since they cannot take constructor injection.

## Multi-tenancy and security
- Every business model uses `BelongsToEcole` (global scope + automatic `ecole_id`). Composite unique indexes start with `ecole_id`. Jobs carry `ecole_id`.
- Authorize every controller action through a Policy (roles in spec section 1); no endpoint without a policy test.
- Each model has a tenant-isolation test (school A never reads or writes school B).
- Validate with FormRequests only; never `$request->all()` into a model; `ecole_id` is never mass-assignable.
- Audit (activitylog) payments, voids, departures, grades, imports, matricule changes.

## Finance invariants (non-negotiable)
- Receipt number: gapless per school+year, taken from `compteurs` with a row lock inside the payment transaction. Receipts are never updated or deleted, only voided.
- Money columns are integers suffixed `_ar`. Echeance balance/status is recomputed in the same transaction as the payment or void. A payment total equals the sum of its lines; a line never exceeds the remaining balance.
- Financial writes accept `client_uuid` / `Idempotency-Key`; a replay never duplicates.

## Conventions
- Names: French domain nouns (`Eleve`, `Paiement`, `Echeance`); tables/columns French without accents; technical classes English (`EloquentPaiementRepository`).
- API under `/api/v1`, JSON Resources, cursor pagination, errors `{message, errors{field:[...]}}`.
- Migrations reversible; encode spec invariants as DB `CHECK` constraints.
- Run Pint before committing. Comments follow `03-comments.md`.
