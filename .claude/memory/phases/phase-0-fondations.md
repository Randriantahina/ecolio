# Phase 0 · Fondations

Goal: a directeur can log in; school data is isolated by tests; PWA skeleton installs. Legend: `[ ]` todo · `[~]` in progress · `[x]` done · `[!]` blocked. Every task is test-first and committed on `dev`.

## Backend
- [ ] Install Sanctum, spatie/permission, spatie/activitylog (build)
- [ ] `Ecole` model + migration + factory
- [ ] `BelongsToEcole` trait: global scope + auto `ecole_id` (feat, tenancy) + isolation test helper reusable per model
- [ ] `Utilisateur` model on table `utilisateurs` (`ecole_id` nullable = super_admin), factory
- [ ] Roles/permissions seeder: super_admin, directeur, secretaire, enseignant, parent (spec section 1)
- [ ] `POST /api/v1/auth/login` (email or phone), logout, `GET /me`; token 30 days; lockout after 5 failures (RG-A2, RG-A3)
- [ ] Password reset by e-mail (US1.2)
- [ ] Directeur creates team accounts with a role (US1.3)
- [ ] API conventions: `/api/v1`, error format `{message, errors}`, API Resources
- [ ] Activity log wired for sensitive models; audit entries not editable via API
- [ ] School settings: `GET/PUT /ecole` (US2.1)

## Frontend
- [ ] App shell: router, layouts, i18n (fr), Pinia, TanStack Query provider
- [ ] API client with token injection and error mapping (data layer)
- [ ] Auth feature: repository, ViewModel, login view, route guard
- [ ] Online/offline indicator component (spec 8.3)
- [ ] PWA: manifest icons (192/512 maskable), install prompt on second visit
- [ ] Dexie database bootstrap (empty queue schema)

## Quality
- [ ] Tenant-isolation test for every model created in this phase
- [ ] CI green on `dev` for both workflows
- [ ] Update `architecture.md` with the real layer map

Definition of done: see `roadmap.md`.
