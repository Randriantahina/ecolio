# Phase 0 · Fondations

Goal: a directeur can log in; school data is isolated by tests; PWA skeleton installs. Legend: `[ ]` todo · `[~]` in progress · `[x]` done · `[!]` blocked. Every task is test-first and committed on `dev`.

## Backend
- [x] Install Sanctum 4.5, spatie/permission 8.5, spatie/activitylog 5.1 (build, done 2026-11-23)
- [x] `Ecole` model + migration + factory (feat, platform, done 2026-11-23)
- [x] `BelongsToEcole` + `EcoleScope` + `TenantContext` (feat, tenancy, done 2026-11-23); fail-closed, platform mode, cross-tenant write refused. Reusable probe: `tests/Support/TenantProbe.php`. Per-model isolation tests still to add with each model
- [x] `Utilisateur` model on `utilisateurs` (`ecole_id` null = super_admin), factory, lockout (5 fails = 15 min), e-mail-or-phone rule (feat, identity, done 2026-11-23)
- [x] `Role` enum + idempotent `RolesSeeder` for the 5 roles (feat, identity, done 2026-11-23). Fine-grained permissions come via Policies per feature, not DB permissions
- [x] `POST /api/v1/auth/login` (email or phone), logout, `GET /me`; token 30 days; lockout after 5 failures; `tenant` middleware (feat, auth, done 2026-11-23). Layers: UtilisateurRepository, AuthService, LoginDto, LoginRequest, AuthController
- [x] Password reset by e-mail (US1.2) (feat, auth, done 2026-11-23): forgot/reset endpoints, link to SPA `/reinitialiser-mot-de-passe`, no enumeration, sessions revoked. SMS reset deferred to phase 5
- [x] Directeur creates/deactivates team accounts with a role (US1.3) (feat, team, done 2026-11-23): `UtilisateurPolicy::gererEquipe`, `TeamService`, 404 for other-school users, tokens revoked on deactivation
- [x] API conventions: `/api/v1`, error format `{message, errors}`, API Resources, cursor pagination (done 2026-11-23)
- [x] Activity log on Utilisateur and Ecole (whitelist, no password), `GET /api/v1/audit` directeur-only and read-only, role changes logged explicitly (feat, audit, done 2026-11-23)
- [x] School settings `GET/PUT /api/v1/ecole` (US2.1) (feat, platform, done 2026-11-23). Logo upload NOT done yet (needs file storage decision)

## Frontend
- [x] App shell: router + `authGuard`, i18n (fr), Pinia, Vue Query plugin, composition root `app/create-app.ts`, `App.vue` with online indicator (feat, frontend, done 2026-11-23)
- [x] API client `shared/api/http-client.ts` with bearer token, typed errors (Validation 422, Unauthorized 401 + onUnauthorized hook, Locked 423, Network, ApiError) (feat, frontend, done 2026-11-23). shadcn button/input/label/card added
- [x] Auth feature: schemas, repository port + HTTP adapter, persisted session store, login ViewModel, `LoginView.vue`, route guard, safe `redirect` param (done 2026-11-23)
- [x] Online/offline indicator `shared/network` (done 2026-11-23). Pending-operations counter comes with the offline queue (phase 6)
- [x] PWA: manifest icons (192/512/maskable PLACEHOLDER art, replace when branding exists), install prompt from 2nd visit, `index.html` fr (feat, pwa, done 2026-11-23)
- [ ] Dexie database bootstrap (empty queue schema)

## Quality
- [x] Tenant isolation proven for every model of this phase: trait probe, Utilisateur (list + 404), JournalAudit (read), Ecole (caller-only) (done 2026-11-23)
- [ ] CI green on `dev` for both workflows
- [ ] Update `architecture.md` with the real layer map

Definition of done: see `roadmap.md`.
