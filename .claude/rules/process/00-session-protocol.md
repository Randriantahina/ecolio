# Session protocol: read the project memory before anything else

Applies to every session, every task, no exception. The project's state lives in `.claude/memory/`, not in your head: a new session starts with zero context, and this folder is what gives it back.

## 1. Start of session (before reading code, before answering, before any tool use on the task)
1. Read `.claude/memory/status.md` (a SessionStart hook also injects it; read it anyway if it is not in your context) and `.claude/memory/roadmap.md`.
2. Open the file of the active phase in `.claude/memory/phases/` and the last entries of the current month in `.claude/memory/changelog/`.
3. Open `.claude/memory/architecture.md` when touching structure, and `.claude/memory/decisions/` when a choice looks already settled.
4. Verify against reality: `git branch --show-current` (must be `dev`), `git status --short`, `git log --oneline -5`. **If memory and git disagree, git wins**: fix the memory first, then continue.
5. Tell the user in one or two lines where the project stands (active phase, last done, next action) and what you are about to do.

## 2. During the task
- Before starting a task, mark it in progress in `status.md` and in its phase file.
- A decision that a future session could wrongly reverse goes into `decisions/` (see `02-memory-journal.md`).
- Anything unexpected (blocker, workaround, trap, environment quirk) goes into `status.md` under "Blockers" or "Environment notes" the moment you learn it, not at the end.

## 3. After each finished change (feat, fix, refactor, test, docs, chore, ci)
Update the memory **in the same commit as the change**:
1. Tick the task in the phase file; tick the phase in `roadmap.md` only when its definition of done is met.
2. Add one line to the current month's `changelog/`, with the change type (`feat` or `fix` first).
3. Rewrite `status.md`: last done, in progress, and a precise **next action** (a future session must be able to resume from that line alone).
A commit without a memory update is incomplete; a hook refuses `git commit` when `.claude/memory/` has no change.

## 4. End of session or when context is getting long
Make `status.md` self-sufficient: someone with no context must know what to do next. Never rely on "I will remember".

## Never
Start coding on a hunch from the previous conversation, trust a status line you did not check against git, or leave memory edits uncommitted at the end of a task.
