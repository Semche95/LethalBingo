@AGENTS.md

# Claude Code

- Règles par sujet dans `.claude/rules/` (PHP, Symfony, Twig, frontend, UI/UX, tests, base de données), chargées selon les fichiers touchés ; `design.md` (DRY, SRP, conception) toujours chargée.
- Skills : `lethalbingo-checks` (vérifications avant commit), `lethalbingo-update-deps`, `lethalbingo-entity-change` ; tâches via la skill globale `backlog-tasks`.
- Sous-agents : `code-reviewer` (relecture d'un diff), `security-auditor` (audit de sécurité).
- Hooks : `validate-bash.sh` bloque push, merge sans `--ff-only`, trailer `Co-Authored-By`, build d'assets, `backlog` hors `pnpm exec` et lecture des secrets ; `php-cs-fix.sh` applique PHP-CS-Fixer à chaque fichier PHP écrit.
- Vérification visuelle avec le MCP Playwright sur https://lethalbingo.localhost.
