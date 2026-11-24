---
paths:
  - "resources/js/**/*.ts"
  - "resources/js/**/*.vue"
  - "resources/views/**/*.php"
---

# Frontend architecture: Inertia + Vue 3 inside Laravel

Decision record: `.claude/memory/decisions/0003-laravel-inertia-monolith.md`. One Laravel application at the repository root; no separate frontend project, no bearer tokens for pages (session guard `web`).

Stack: Inertia 3 (`@inertiajs/vue3`, Laravel adapter `inertiajs/inertia-laravel`), Vue 3 (`<script setup lang="ts">`), strict TypeScript, Vite (`laravel-vite-plugin`), Tailwind CSS 4, shadcn-vue (Reka UI), vue-i18n, Vitest, Playwright. Package manager: **pnpm only** (never npm or yarn). Use Laravel Boost (`search-docs`) for Inertia and Laravel specifics before relying on memory.

## Layout
```
resources/js/
├── app.ts            Inertia bootstrap (resolve pages, i18n, default layout)
├── pages/            One Vue file per Inertia::render() component name (Auth/Login.vue, Eleves/Index.vue ...)
├── layouts/          AppLayout.vue (shell, menu from the server-shared `navigation`)
├── components/ui/    shadcn-vue (generated, excluded from ESLint/Prettier)
├── components/app/   Écolio-specific presentational components
├── composables/      Local UI state and commands (`useEncaissement()`), no fetching
├── lib/              Pure helpers: formatAr, dates, download
├── i18n/             fr.ts (default), mg.ts later
└── types/            Shared prop types
```
Server side: `routes/web.php` → thin controllers in `app/Http/Controllers/Web/` → the same Application Services, Policies and API Resources as the JSON API. Controllers return `Inertia::render('Page', props)` or a redirect; they never hold business logic.

## Rules
- **Server state is props.** Never duplicate it in a client store. Mutations go through `useForm` / `<Form>` / `router`, and the server answers with a redirect (flash message) or validation errors.
- **A page is presentational**: it renders props and forwards events. Business rules live in `Application/` services; local-only logic (selection, dialog state, derived totals) lives in a composable with its own Vitest test.
- **Authorization lives on the server.** The menu comes from `HandleInertiaRequests` (`navigation`), buttons are hidden by a prop computed from a Policy, and the Policy still guards the route. Never rely on hiding.
- Props are typed in `resources/js/types`; shared props (`auth`, `navigation`, `flash`) come from `HandleInertiaRequests`. Money is integer ariary through `formatAr`; never floats.
- Checking a tuition box submits the "encaisser" request and shows the server result; it never flips local state alone (RG-P1).
- Offline (Phase 6): absences and tuition writes will go through a Dexie queue carrying `client_uuid` and call the JSON API (`/api/v1`); always show online/offline/pending status.

## UI rules
- shadcn-vue first (`pnpm dlx shadcn-vue@latest add <name>`), Tailwind utilities for layout, no ad-hoc CSS unless unavoidable.
- Mobile first from 360px, touch targets ≥ 44px, labelled fields, AA contrast, visible focus.
- All user text through `vue-i18n` (fr default, mg second); no hard-coded strings in pages. Never the word "facture" (RG-R6): receipts are « reçus ».
- Design bar set by the user: distinctive, not generic AI-looking (no gradient blobs, no icon-in-every-input, no checklist hero).

## TypeScript
No `any`; `unknown` + narrowing. `erasableSyntaxOnly` is on: no enums, no constructor parameter properties. Alias `@/` → `resources/js/`. Files kebab-case or `useName.ts`; components PascalCase.

## Tests
- Pest Feature tests assert the Inertia contract (`assertInertia`: component name and props) and the redirects/errors of every mutation.
- Vitest for composables, `lib/` helpers and components with `@vue/test-utils`. Playwright journeys: "encaisser en 3 interactions", import wizard, offline mode.
- Run `pnpm test && pnpm typecheck && pnpm lint && pnpm format:check` and `pest` + `pint` before committing. Comments follow `03-comments.md`.
