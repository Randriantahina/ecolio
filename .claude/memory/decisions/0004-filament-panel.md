# 0004 · Filament 5 replaces Inertia + Vue (supersedes 0003 for the UI)

Date: 2026-11-24 · Status: accepted

## Context
The Inertia/Vue UI was slow to build for a solo owner and the user asked for Filament, using the sibling
project `uccello/mintizy-v2` (Filament tenancy + roles per tenant) and the Filament modular/DDD guide
(https://filamentphp.com/docs/5.x/advanced/modular-architecture) as references.

## Decision
- UI = one Filament 5 panel `ecole` (`app/Providers/Filament/EcolePanelProvider.php`), `->tenant(Ecole::class, slugAttribute: 'slug')`.
- `BindEcoleContext` (persistent tenant middleware) copies Filament's tenant into `TenantContext`, so the `BelongsToEcole` global scope still guards every query.
- `Utilisateur` implements `FilamentUser`, `HasTenants`, `HasName`; only active staff (directeur, secrétaire, enseignant) enter the panel.
- Login is a custom Filament page delegating to `AuthService::authenticate` (lockout RG-A3, same error for unknown account and bad password).
- Each bounded context contributes ONE Filament plugin under `app/Filament/<Context>/` (resources, pages, widgets discovered with `in:`/`for:`), registered for the panel in `FilamentModulesServiceProvider` via `Panel::configureUsing`. The panel provider never lists resources.
- Layers stay `Domain`, `Application`, `Infrastructure`; Filament classes are thin: they call Application Services and Policies, never Eloquent queries of their own beyond the table query.
- The JSON API (`/api/v1`) stays for offline/mobile later. Vue, Inertia, Vite, pnpm, Vitest removed; CI is the single `php` job.

## Why
Admin CRUD, tables, filters, imports/exports and tenancy come ready; the effort goes into domain rules and tests.

## Rejected
- Moving everything to `app-modules/` Composer packages (InterNACHI/Modular): too risky for 198 existing tests; the plugin-per-context pattern gives the same boundaries.
- Keeping Inertia: slower to deliver the whole spec.
