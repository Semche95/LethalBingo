#!/usr/bin/env bash
# Applique PHP-CS-Fixer au fichier PHP qui vient d'être écrit (conteneur php).
file=$(jq -r '.tool_input.file_path // empty')
case "$file" in
  "$CLAUDE_PROJECT_DIR"/*.php) ;;
  *) exit 0 ;;
esac
rel=${file#"$CLAUDE_PROJECT_DIR"/}
cd "$CLAUDE_PROJECT_DIR" || exit 0
docker compose ps --status running --services 2>/dev/null | grep -qx php || exit 0
docker compose exec -T php ./vendor/bin/php-cs-fixer fix --quiet "$rel" >/dev/null 2>&1 || true
exit 0
