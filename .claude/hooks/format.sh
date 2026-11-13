#!/usr/bin/env bash
# PostToolUse hook: format the file Claude just edited.
# Reads the hook JSON on stdin; never blocks (always exits 0).
file=$(php -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d["tool_input"]["file_path"] ?? "";' 2>/dev/null)
[ -z "$file" ] || [ ! -f "$file" ] && exit 0
cd "${CLAUDE_PROJECT_DIR:-.}" || exit 0
case "$file" in
  */vendor/*|*/node_modules/*) exit 0 ;;
  *.php)
    [ -x vendor/bin/pint ] && vendor/bin/pint --quiet "$file" >/dev/null 2>&1 ;;
  *.ts|*.tsx|*.js|*.jsx|*.css|*.json)
    [ -x node_modules/.bin/vp ] && node_modules/.bin/vp fmt --write "$file" >/dev/null 2>&1 ;;
esac
exit 0
