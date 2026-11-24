---
paths:
  - "app-modules/**/*.php"
  - "app/Providers/**/*.php"
  - "tests/**/*.php"
---

# Filament 5 + modular DDD (reference guide, follow it)

Source of truth for the structure: https://filamentphp.com/docs/5.x/advanced/modular-architecture
(Filament "Modular architecture (DDD)", built on the InterNACHI/Modular package). Read it before adding or moving a module.

## Rules taken from the guide
- One **bounded context = one module** = one Composer package under `app-modules/<name>/` with its own `composer.json`, `src/`, `database/`, `routes/`, `resources/`, `tests/`. PSR-4 namespace `Modules\<Name>\` maps to `src/`; the module service provider is registered through `extra.laravel.providers` of its `composer.json`, never in `bootstrap/providers.php`.
- Filament code of a module lives in `src/Filament/` (`Resources/<Name>/` with its `Pages/`, `Pages/`, `Widgets/`, `Clusters/`).
- Each module ships **one plugin** (`src/<Name>Plugin.php`, implements `Filament\Contracts\Plugin`: `getId()`, `make()`, `register()`, `boot()`). `register()` calls `discoverResources/discoverPages/discoverWidgets(in: __DIR__.'/Filament/...', for: 'Modules\\<Name>\\Filament\\...')`.
- The **module service provider** attaches the plugin with `Panel::configureUsing(...)` (match on `$panel->getId()`), so the panel provider is never edited when a module is added or removed.
- A resource shared by several panels is registered with `$panel->resources([...])` from the plugin, with per-panel options exposed on the plugin (e.g. `canManageRoles()`).
- Custom Livewire components of a module are registered in the plugin `boot()` with `Livewire::component(...)`.

## Layers inside a module (our DDD, same direction as `backend-laravel-ddd.md`)
`src/Domain` (models, enums, value objects, exceptions, repository interfaces) ← `src/Infrastructure` (Eloquent repositories, adapters); `src/Application` (Services, DTOs); `src/Http` (API controllers, FormRequests, Resources); `src/Filament` (UI, as thin as a controller: call a Service, never business logic).
A module talks to another through its Services, DTOs or domain events, never through its tables.
The shared kernel (tenancy: `BelongsToEcole`, `TenantContext`, `Money`) lives in its own module and is the only thing every module may depend on.

## Tests
Module tests live in `app-modules/<name>/tests/` and run with the root `pest` command. Each Filament resource or page has a `Livewire::test(...)` test (render, action through the Service, role matrix, tenant isolation).
