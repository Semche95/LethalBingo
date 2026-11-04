---
name: lethalbingo-update-deps
description: Met à jour toutes les dépendances Composer et pnpm de LethalBingo, majeures comprises, vérifie et commite. À utiliser quand l'utilisateur demande de mettre à jour les dépendances ou de corriger une alerte de sécurité.
---

# Mise à jour des dépendances

Demande directe : pas de tâche Backlog.md.

## Composer (dans le conteneur, `vendor/` appartient à root)

1. `docker compose exec -T php composer outdated --direct` puis `composer outdated` pour les transitives.
2. Mineures : `docker compose exec -T php composer update -W`.
3. Majeures bloquées par une contrainte (`^0.6`, `^4`…) : élargir la contrainte dans `composer.json`, `composer update <paquet> -W`, lire le changelog (UPGRADE.md dans `vendor/`) et adapter le code.
4. Recipes : `docker compose exec -T php composer recipes` ; appliquer les mises à jour via `composer recipes:update <paquet>`, jamais de config écrite à la main.
5. `docker compose exec -T php composer audit`.

## pnpm (sur l'hôte)

1. `pnpm outdated`.
2. `pnpm update --latest` ; `backlog.md` reste épinglé sans `^` (mettre la version exacte dans `package.json`).
3. `pnpm audit`.
4. Les paquets `file:vendor/...` (Symfony UX) suivent Composer : rien à faire.
5. Pas de build d'assets.

## Vérifier et commiter

1. Skill `lethalbingo-checks` ; une règle Rector disparue ou renommée se retire de `rector.php`.
2. Vérification visuelle sur https://lethalbingo.localhost (console sans erreur) si le frontend a bougé.
3. Commit seulement si l'utilisateur l'a demandé : fichiers ajoutés un par un (`composer.json`, `composer.lock`, `config/reference.php`, `symfony.lock`, `package.json`, `pnpm-lock.yaml`, `rector.php`…), message d'une ligne générique, sans liste de paquets (`chore(config): update composer and pnpm dependencies`, ou seulement l'écosystème touché ; une raison précise comme une alerte de sécurité peut être citée brièvement), sans trailer. Jamais de push.
