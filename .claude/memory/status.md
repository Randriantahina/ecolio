# Status (read first; rewrite after every change)

Updated: 2026-11-23 · Branch: `dev` · Active phase: **0 – Fondations** · Phases done: 0/7 (+ bootstrap done)

## In progress
- Phase 0 backend: tenancy, identity and roles done; next is the auth API.

## Last done
- 2026-11-23 · Repo bootstrap: Laravel 13 + Pest 5, Vue 3 + Vite + Tailwind 4 + shadcn-vue + PWA, pnpm, CI workflows, rules, `main`/`dev` branches pushed.
- 2026-11-23 · Project memory system with hooks. Packages (Sanctum, permission, activitylog) installed.
- 2026-11-23 · Tenancy (`BelongsToEcole`, `TenantContext`, `Ecole`) and identity (`Utilisateur`, roles, lockout): 30 tests green.

## Next action
Phase 0, TDD: auth API under `/api/v1/auth`: `POST login` (email or phone + password, 30-day Sanctum token, uses `Utilisateur::enregistrerEchec/estVerrouille`, 423 when locked, same 422 message for unknown user and wrong password), `POST logout`, `GET /api/v1/me`. Structure: `Http/Controllers/Auth`, `Http/Requests`, `Application/Identity/Services/AuthService` + `DTOs/LoginDto`, `Domain/Identity/Contracts/UtilisateurRepository` + Eloquent impl in `Infrastructure/Persistence/Eloquent`. Middleware to resolve `TenantContext` from the authenticated user. Feature tests first.

## Blockers / open questions
- None technical. Spec items `[À confirmer]` (real Excel file, bulletin format, fees) are not available: build with the spec's hypotheses, keep rules parametrable.

## Environment notes (non-derivable)
- Node pinned via `.mise.toml` (26.8.1); use `pnpm` only. The `mise` shim for pnpm fails on other Node versions.
- Composer hits GitHub rate limits without auth: export `COMPOSER_AUTH` from `gh auth token` inline for one command; never print or store the token.
- Backend tests use in-memory SQLite (`phpunit.xml`); production target is PostgreSQL, so avoid SQLite-only SQL and verify JSON queries on both.
- Pest 5 runs on PHPUnit 13; Pest prints a star-the-repo prompt on `--init`, answer nothing.
- Laravel's generated `backend/CLAUDE.md` / `AGENTS.md` were removed on purpose (they ask for curl installs).
- The user frequently interrupts tool calls; keep steps small and commit often.
