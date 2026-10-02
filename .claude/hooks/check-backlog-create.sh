#!/usr/bin/env bash
# Bloque toute création de tâche Backlog.md sans type ni label.
cmd=$(jq -r '.tool_input.command // empty')
echo "$cmd" | grep -qE 'backlog[[:space:]]+task[[:space:]]+create' || exit 0

missing=()
echo "$cmd" | grep -qE '(^|[[:space:]])--type([[:space:]]|=)' || missing+=('--type <bug|feature|enhancement|task|chore|docs|spike>')
echo "$cmd" | grep -qE '(^|[[:space:]])(-l|--labels?)([[:space:]]|=)' || missing+=('-l <labels>')

if [ ${#missing[@]} -gt 0 ]; then
  echo "Création de tâche refusée : option(s) manquante(s) ${missing[*]}. Toute nouvelle tâche reçoit un type et au moins un label (skill backlog-tasks)." >&2
  exit 2
fi
exit 0
