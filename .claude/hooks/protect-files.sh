#!/usr/bin/env bash
# PreToolUse hook for Edit/Write: block edits to files Claude must not touch.
# Exit code 2 blocks the tool call and shows the message to Claude.
file=$(php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["tool_input"]["file_path"] ?? "";' 2>/dev/null)
[ -z "$file" ] && exit 0
root="${CLAUDE_PROJECT_DIR:-$(pwd)}"
rel="${file#"$root"/}"

case "$rel" in
  .env|.env.*)
    [ "$rel" = ".env.example" ] && exit 0
    echo "Blocked: $rel holds secrets. Edit .env.example and tell Bruno which value to set." >&2; exit 2 ;;
  vendor/*|node_modules/*)
    echo "Blocked: $rel is a dependency. Change our code or ask Bruno about the package." >&2; exit 2 ;;
  composer.lock|package-lock.json)
    echo "Blocked: lock files change only through composer/npm commands." >&2; exit 2 ;;
  database/migrations/*)
    # A migration already on main must never change; write a new migration instead.
    if [ -f "$file" ] && git -C "$root" cat-file -e "main:$rel" 2>/dev/null; then
      echo "Blocked: $rel is already on main. Create a new migration instead of editing it." >&2; exit 2
    fi ;;
esac
exit 0
