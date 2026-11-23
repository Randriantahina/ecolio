---
paths:
  - "frontend/**/*.ts"
  - "frontend/**/*.vue"
---

# Frontend architecture: Vue 3, MVVM, feature slices

Stack: Vue 3 (`<script setup lang="ts">`), strict TypeScript, Vite, Tailwind CSS 4, shadcn-vue (Reka UI), Pinia, Vue Router, TanStack Query + Table, Zod, vue-i18n, Dexie, vite-plugin-pwa, Vitest, Playwright. Package manager: **pnpm only** (never npm or yarn).

## MVVM per feature
```
src/features/<feature>/
├── domain/               Model: types, Zod schemas, pure rules (no Vue, no fetch)
├── data/                 Model: repository (API client + Dexie cache/queue), mappers
├── <name>.viewmodel.ts   ViewModel: composable/store with state, derived state, commands
├── ui/                   View: presentational *.vue components and pages
└── *.test.ts             colocated tests
src/shared/               api client, i18n, offline sync, utils
src/components/ui/        shadcn-vue components
```
- **View**: no business logic, no fetching. Binds to a ViewModel, renders, forwards events.
- **ViewModel**: owns state and commands (`encaisser()`, `marquerAbsent()`). Receives a **repository interface** by injection so tests use a fake. No DOM or router access except through injected adapters.
- **Model/data**: the repository is the only code that knows endpoints or IndexedDB. Parse responses with Zod at the boundary, map to domain types.
- Server state through TanStack Query; UI state in the ViewModel. Never duplicate server state in Pinia.

## Patterns and SOLID
Repository, Adapter (API, IndexedDB, clock), Factory (ViewModel with its dependencies), Observer (Vue reactivity), Strategy (column renderers, validators), Command (offline queue operations carrying `client_uuid`). Depend on types/interfaces, inject dependencies, keep each composable single-purpose.

## UI rules
- shadcn-vue first (`pnpm dlx shadcn-vue@latest add <name>`), Tailwind utilities for layout, no ad-hoc CSS unless unavoidable.
- Mobile first from 360px, touch targets ≥ 44px, labelled fields, AA contrast.
- All user text through `vue-i18n` (fr default, mg second); no hard-coded strings.
- Money is integer ariary through one shared formatter; never floats.
- Checking a tuition box runs the "encaisser" command and shows the server/queue result; it never flips local state alone (RG-P1).
- Offline: absences and tuition go through the Dexie queue with `client_uuid`; always show online/offline/pending status.

## TypeScript
No `any`; use `unknown` + Zod narrowing. Payload types come from `z.infer`. Alias `@/` → `src/`. Files kebab-case or `name.viewmodel.ts`; components PascalCase.

## Tests (Vitest, mandatory)
ViewModels with a fake repository, schemas with valid and invalid cases, components with `@vue/test-utils`, repositories with mocked HTTP. Playwright e2e: "encaisser en 3 interactions", import wizard, offline mode. Run `pnpm test && pnpm typecheck && pnpm lint` before committing. Comments follow `01-comments-why-not-how.md`.
