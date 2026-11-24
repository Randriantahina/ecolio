# 0002 · One working branch (`dev`), push only after green tests

- Date: 2026-11-23 · Status: accepted
- Context: one developer, phased delivery, wish to avoid branch bookkeeping.
- Decision: all feat/fix/refactor work is committed on `dev`; `main` receives `dev` by PR with green CI. After each finished feature or fix: run gates, commit, push `dev` without asking.
- Why: linear history, minimal ceremony, `main` only ever holds CI-validated code.
- Rejected: per-feature branches; pushing "to save progress" with red tests.
- Consequence: `git push origin dev` is allowlisted in `.claude/settings.json`; `main` and force pushes need explicit approval.
