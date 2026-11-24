# Git: Conventional Commits, in English

Applies to every commit. **Commit messages are written in English**, even though the product is French.

## Format
```
<type>(<scope>): <imperative summary, lower case, no final period, ≤ 72 chars>

<optional body: why the change was made, wrapped at 100 cols>

<optional footer: BREAKING CHANGE: ..., Refs: #123>
```

## Types
`feat` new behaviour · `fix` bug fix · `refactor` no behaviour change · `test` tests only · `docs` documentation · `chore` maintenance/tooling · `build` dependencies/build system · `ci` pipelines · `perf` performance · `style` formatting only · `revert`.

## Scopes
`backend` (PHP side), `frontend` (Vue/Inertia pages), `docs`, `ci`, `claude`, or a feature/context: `finance`, `eleves`, `absences`, `imports`, `notes`, `auth`, `tenancy`, `pwa`, `i18n`.
- `feat(finance): generate gapless receipt numbers under row lock`
- `test(tenancy): prove school A cannot read school B students`
- `fix(imports): reject rows with duplicate matricule`
- `chore(claude): configure permissions and project rules`

## Discipline
- **Commit after every change that leaves the suite green**; one logical change per commit. Never batch unrelated work.
- A `feat`/`fix` commit includes its tests (TDD). A separate `test:` commit for the red step is optional.
- Never commit with failing tests, unformatted PHP (`pint --test`) or type errors.
- Never commit `.env`, secrets, `node_modules`, `vendor`, `dist`, `.claude/settings.local.json`, or real student data.
- No `--no-verify`, no `--amend` on pushed commits, no force-push, no `reset --hard` without explicit user approval.
- Commit on `dev` (see `05-branches-and-push.md`); remote `origin` is `git@github.com:Randriantahina/ecolio.git`. After each feature or fix, once its tests and the gates are green and the commit is made, push `dev` (`git push origin dev`) without asking. Pushing any other branch, or `main`, still needs the user's approval.
- End messages with the attribution line(s) the Claude Code session requires, when provided.
