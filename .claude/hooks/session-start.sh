#!/usr/bin/env bash
# Why a hook and not only a rule: injecting the status is deterministic, so a fresh session
# gets the project state even if it would otherwise skip reading it.
set -euo pipefail
ROOT="${CLAUDE_PROJECT_DIR:-$(pwd)}"
MEM="$ROOT/.claude/memory"

[ -f "$MEM/status.md" ] || exit 0

echo "=== PROJECT MEMORY (injected by SessionStart hook; protocol: .claude/rules/process/00-session-protocol.md) ==="
cat "$MEM/status.md"
echo
[ -f "$MEM/roadmap.md" ] && cat "$MEM/roadmap.md"
echo "=== END PROJECT MEMORY: verify against git, then follow the session protocol ==="
