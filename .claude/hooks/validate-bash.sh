#!/usr/bin/env bash
# Bloque les commandes dangereuses et celles que les règles du projet interdisent.
cmd=$(jq -r '.tool_input.command // empty')
[ -n "$cmd" ] || exit 0

deny() {
  echo "Commande refusée : $1" >&2
  exit 2
}

# Destruction irréversible
echo "$cmd" | grep -qE 'rm[[:space:]]+(-[a-zA-Z]*[rf][a-zA-Z]*[[:space:]]+)+(/|~|\$HOME|\.|\*)([[:space:]]|$)' && deny "suppression récursive de la racine, du home ou du projet."
echo "$cmd" | grep -qE 'docker[[:space:]]+compose[[:space:]].*down.*(-v|--volumes)' && deny "supprime le volume de la base de dev."
echo "$cmd" | grep -qE 'doctrine:(database|schema):drop' && deny "suppression de la base ; utiliser just fixtures pour la recharger."
echo "$cmd" | grep -qE 'git[[:space:]]+(reset[[:space:]]+--hard|clean[[:space:]]+-[a-zA-Z]*f|checkout[[:space:]]+--[[:space:]]|restore[[:space:]]+[^-])' && deny "écrase des modifications non commitées ; demander à l'utilisateur."

# Git : jamais de push, de commit de merge ni de trailer
echo "$cmd" | grep -qE 'git[[:space:]]+push' && deny "jamais de git push, l'utilisateur pousse lui-même."
echo "$cmd" | grep -qE 'git[[:space:]]+merge([[:space:]]|$)' && ! echo "$cmd" | grep -q -- '--ff-only' && deny "merge uniquement en --ff-only après rebase."
echo "$cmd" | grep -qE 'git[[:space:]]+commit' && echo "$cmd" | grep -qiE 'co-authored-by' && deny "pas de trailer Co-Authored-By dans les commits."

# Build d'assets (le HMR de Vite suffit)
echo "$cmd" | grep -qE '(pnpm|npm)[[:space:]]+(run[[:space:]]+)?build|just[[:space:]]+build-assets|vite[[:space:]]+build' && deny "pas de build d'assets sans demande explicite (l'utilisateur peut la lancer avec ! dans le prompt)."

# Backlog.md : uniquement via pnpm exec
echo "$cmd" | grep -qE '(npx|pnpm[[:space:]]+dlx)[[:space:]]+backlog|(^|[;&|][[:space:]]*)backlog[[:space:]]' && deny "Backlog.md uniquement via pnpm exec backlog."

# Secrets
echo "$cmd" | grep -qE '(^|[;&|(][[:space:]]*)(cat|less|more|head|tail|bat|sed|awk|grep|rg|source|\.|cp|mv|scp|base64|xxd|od|strings|curl)[[:space:]][^;&|]*(\.env\.local|\.env\.[a-z]+\.local|prod\.decrypt\.private|cert-key\.pem)' && deny "accès à un fichier de secrets."

exit 0
