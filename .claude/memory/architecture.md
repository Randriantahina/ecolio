# Architecture map (what exists, not what is planned)

Planned design: `CLAUDE.md` (big picture) and `rules/stack/`. This file records the **real** state; update it when structure changes.

## Pinned toolchain (2026-11-23)
- Backend: PHP 8.5.12, Laravel 13.43, Pest 5.3.7, PHPUnit 13.4, Pint 1.32. DB: SQLite (tests, dev default), PostgreSQL 18 targeted for prod.
- Frontend: Node 26.8.1 (`.mise.toml`), pnpm 11.25, Vite 8, Vue 3.5, TypeScript 6 (no `baseUrl`, use `paths`), Tailwind 4, Vitest 5, shadcn-vue style `reka-nova`.
- UI components live in `frontend/src/components/ui` (generated, excluded from ESLint/Prettier).

## Backend (`backend/app`)
- `Domain/Tenancy`: `TenantContext` (scoped binding; school id or platform mode), `EcoleScope`, `BelongsToEcole`, exceptions. Unresolved context THROWS, never returns all rows.
- `Domain/Identity`: `Utilisateur` (Authenticatable + Sanctum + spatie roles; NOT tenant-scoped on purpose, repositories filter `ecole_id` explicitly), `Role` enum. Guard name `web`; password column `mot_de_passe` via `getAuthPasswordName()`.
- Auth slice (reference for every future feature): `Domain/Identity/Contracts/UtilisateurRepository` -> `Infrastructure/Persistence/Eloquent/EloquentUtilisateurRepository` (bound in `Providers/DomainServiceProvider`), `Application/Identity/{DTOs,Services}`, `Http/{Controllers/Auth,Requests/Auth,Resources,Middleware/ResolveTenant}`. HTTP status for domain exceptions is mapped in `bootstrap/app.php`. Routes in `routes/api.php` under `v1`; protected group = `auth:sanctum` + `tenant`.
- Team slice: `Policies/UtilisateurPolicy` (registered in DomainServiceProvider::boot, needed because models live under Domain/), `Application/Identity/Services/TeamService`, `Http/Controllers/Team`. FormRequest::authorize() runs the policy so forbidden roles get 403 before validation.
- Ecole settings slice: `EcoleRepository`, `EcoleSettingsService`, `UpdateEcoleDto`, `EcolePolicy::modifier`, `Http/Controllers/Ecole`. Receipt texts stored in `ecoles.parametres` JSON.
- Audit slice: `Domain/Audit/Models/JournalAudit` (extends spatie Activity; `ecole_id` stamped from subject/causer on create; configured in `config/activitylog.php`), `JournalAuditRepository`, `JournalAuditPolicy::voir`, `AuditController`. Models opt in with `LogsActivity` + a logOnly whitelist.
- `Domain/Platform/Models/Ecole` (not scoped; uuid public id).
- Other layers (`Application`, `Infrastructure`, `Http`) are created as phase 0 needs them.

## Frontend (`frontend/src`)
- Vite starter + `lib/utils.ts` (`cn`). Target slices: `features/<name>/{domain,data,ui}`, `shared/`.

## CI
- `.github/workflows/backend.yml` (Pint, Pest + coverage) and `frontend.yml` (lint, format, types, Vitest coverage, build) on `main` and `dev`.

## Memory and automation
- `.claude/hooks/session-start.sh` injects status + roadmap at session start; `guard-commit-journal.sh` blocks commits without a `.claude/memory/` change.
