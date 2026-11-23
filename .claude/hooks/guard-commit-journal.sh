#!/usr/bin/env bash
# PreToolUse(Bash) guard: a commit must carry a memory update (rule 02-memory-journal.md).
# Exit 2 blocks the tool call and shows stderr to Claude so it can fix and retry.
set -uo pipefail
ROOT="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$ROOT" || exit 0

cmd="$(jq -r '.tool_input.command // empty' 2>/dev/null)"
[ -n "$cmd" ] || exit 0

# Only guard real commits; amend is left alone because it reuses the previous commit's content.
echo "$cmd" | grep -Eq '(^|[;&| ])(rtk )?git commit' || exit 0
echo "$cmd" | grep -q -- '--amend' && exit 0

if echo "$cmd" | grep -Eq 'git add'; then
  # Staging happens inside the same command, so the index is not final yet: look at the worktree.
  changed="$(git status --porcelain -- .claude/memory 2>/dev/null)"
else
  changed="$(git diff --cached --name-only -- .claude/memory 2>/dev/null)"
fi

if [ -z "$changed" ]; then
  cat >&2 <<'MSG'
Commit blocked: no change under .claude/memory/ is included.
Update, in this same commit: the phase checklist, one line in memory/changelog/YYYY-MM.md
(feat/fix first, with commit proof) and memory/status.md (next action).
See .claude/rules/process/02-memory-journal.md.
MSG
  exit 2
fi
exit 0
