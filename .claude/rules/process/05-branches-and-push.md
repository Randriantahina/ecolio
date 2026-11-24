# Git workflow: all work happens on `dev`

Applies to every task. Commit message format is in `04-commits.md`.

## Branches
- `main`: stable, deployable. **Never work or commit directly on it** once the project structure is in place (the initial scaffolding was the only exception).
- `dev`: **the single working branch.** Every `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `ci` is committed here.
- **Do not create a branch per feature or fix.** No `feat/...`, `fix/...`, `hotfix/...` branches unless the user explicitly asks for one.

## Daily flow
1. Make sure you are on `dev` (`git branch --show-current`); if not, stop and switch (ask if the tree is dirty).
2. Work with TDD, commit after every green change (one logical change per commit).
3. **Test gate, then push.** After each finished feature or fix, run the gates (`pint --test` and `pest`). Only if everything is green: commit, then `git push origin dev` without asking. If anything fails, fix it first; never commit or push a red state, and never push to "save progress".
4. Promote to `main` only when the user asks: open a PR `dev → main` (`gh pr create --base main --head dev`) and let CI pass. Merge with a merge commit or squash as the user decides; never force-push `main`.

## Why a single branch
The project has one developer working in phases. A permanent `dev` keeps history linear to read, avoids branch bookkeeping, and `main` only ever receives code that passed CI.

## Never
Force-push, rewrite pushed history, delete `main` or `dev`, or merge into `main` without CI green and the user's approval.
