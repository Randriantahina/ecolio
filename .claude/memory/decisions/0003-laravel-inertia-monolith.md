# 0003 · One Laravel + Inertia + Vue application at the repository root

- Date: 2026-11-24 · Status: accepted (supersedes the `backend/` + `frontend/` split described in the original CLAUDE.md and spec section 6)

## Context
The project started as two folders: a Laravel JSON API (`backend/`) and a Vue SPA/PWA (`frontend/`, Vite, Pinia, vue-router, TanStack Query, hand-written HTTP client with bearer tokens). The user, who owns the stack choice, wants a single Laravel application with Inertia + Vue, not two projects.

## Decision
- One application at the **repository root** (`git mv` of `backend/*`, history kept). `docs/`, `.claude/`, `.github/` stay beside it. `frontend/` is deleted.
- Pages are Inertia pages in `resources/js/` (Vue 3 + TypeScript, Tailwind 4, shadcn-vue). Web routes in `routes/web.php`, thin controllers return `Inertia::render` and call the same Application Services, Policies and Resources as before. Authentication on pages is the Laravel session (guard `web`), not bearer tokens.
- The JSON API under `/api/v1` and its tests are kept: Phase 6 (offline queue with `client_uuid`) and future mobile need an API; business logic stays in `Application/` services shared by both entry points.
- Laravel Boost (dev dependency) and its MCP server are installed for the Laravel docs and app introspection.

## Why
One deploy unit, one auth model, no CORS or token plumbing, server-side props instead of a client cache, Laravel conventions throughout. The domain layers (`Domain`, `Application`, `Infrastructure`) are untouched, so the move costs the UI only.

## Alternatives rejected
- Keep the SPA and serve it from Laravel's `public/`: still two codebases and a token/CORS model.
- Keep Laravel in `backend/`: misleading name once the UI lives inside it, and an extra path prefix everywhere.

## Consequences
- Frontend MVVM rules (`rules/stack/frontend-vue-mvvm.md`) become Inertia page conventions; ViewModels survive as composables for local UI state only, server state comes from props.
- Dexie/PWA/offline work moves to `resources/js` when Phase 6 starts.
- `.claude/settings.json` deny rules point at `./.env*` and `./composer.lock` (same protection, new path).
