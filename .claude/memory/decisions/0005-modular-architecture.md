# 0005 · Bounded contexts as InterNACHI/Modular modules (refines 0004)

Date: 2026-11-24 · Status: accepted

## Context
The user asked to follow the Filament modular guide faithfully
(https://filamentphp.com/docs/5.x/advanced/modular-architecture) instead of the lighter plugin-per-folder layout of 0004.

## Decision
- Package `internachi/modular`; one module per context in `app-modules/`: tenancy (shared kernel), platform, identity, audit, scolarite, eleves, finance, absences. Namespace `Modules\<Name>\`.
- Inside `src/`: `Domain`, `Application`, `Infrastructure`, `Http`, `Policies`, `Filament`, `Providers`, plus `<Name>Plugin.php`. Migrations, factories, seeders and tests live in the module; module tests run with the root `pest` (Pest.php `in('Feature', '../app-modules/*/tests/Feature')`, phpunit `Modules` suite).
- Each module service provider (registered by Composer `extra.laravel.providers`) binds its repository interfaces, declares its policies and, if it has UI, attaches its plugin with `Panel::configureUsing`. `bootstrap/providers.php` only lists `AppServiceProvider` and `EcolePanelProvider`; `DomainServiceProvider` and `FilamentModulesServiceProvider` are gone.
- Kept in `app/`: panel provider, API routes (`routes/api.php` imports module controllers), `DemoSeeder`/`DatabaseSeeder`, panel/CORS/seeder tests, framework migrations (cache, jobs).
- Module controllers extend `Illuminate\Routing\Controller` (no dependency on `App\`).

## Why
It is the structure the Filament guide prescribes, gives hard context boundaries (own composer.json, own tests) and lets a module be added or removed without touching the panel.

## Consequences
- Class names changed (`App\Domain\X` → `Modules\X\Domain\X`): activity-log `subject_type` and permission model rows written before 2026-11-24 do not match; only fresh dev databases exist, so `migrate:fresh --seed` is the fix.
- Adding a context: `php artisan make:module <name>`, then `composer update modules/<name>` (needs the user's GitHub auth only if Composer must download).
- Routes are still centralized in `routes/api.php` (the guide does not cover them); move per module when the API is versioned.
