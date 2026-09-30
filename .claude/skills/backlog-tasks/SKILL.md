---
name: backlog-tasks
description: Gérer les tâches du projet LethalBingo stockées dans .backlog/tasks avec le CLI Backlog.md. À utiliser dès que l'utilisateur parle d'une tâche (TASK-xx, « la tâche », « les tâches », « le backlog ») ou demande de créer, consulter, lister, rechercher, modifier, démarrer ou terminer une tâche.
---

# Tâches Backlog.md

Dans ce projet, une « tâche » désigne toujours une tâche Backlog.md, stockée dans `.backlog/tasks/`.

## Règles

- Toujours passer par le CLI : `pnpm exec backlog …` (version épinglée dans `package.json`). Jamais `pnpm dlx` ni `npx`.
- Ne pas lire ni modifier les fichiers `.backlog/tasks/*.md` à la main : le CLI garde le format, les identifiants et les métadonnées cohérents.
- Utiliser `--plain` pour obtenir une sortie texte lisible.
- La branche `main` est la seule source de vérité pour l'état des tâches.
- Rédiger titres, descriptions et critères d'acceptation en français.

## Consulter

```bash
pnpm exec backlog task list --plain                    # toutes les tâches, groupées par statut
pnpm exec backlog task list -s "To Do" --plain         # filtrer par statut (To Do, In Progress, Done)
pnpm exec backlog task list --ready --plain            # tâches débloquées (dépendances terminées)
pnpm exec backlog task view <id> --plain               # détail d'une tâche (ex. TASK-18 ou 18)
pnpm exec backlog search "<texte>" --type task --plain # recherche plein texte
```

## Créer

```bash
pnpm exec backlog task create "<titre>" \
  -d "<description>" \
  --ac "<critère d'acceptation 1>" --ac "<critère 2>" \
  [--priority high|medium|low] [-l <labels>] [-p <tâche parente>] [--dep <ids>]
```

Afficher ensuite la tâche créée avec `task view` pour confirmer son identifiant.

## Modifier

```bash
pnpm exec backlog task edit <id> -t "<nouveau titre>"
pnpm exec backlog task edit <id> -d "<nouvelle description>"
pnpm exec backlog task edit <id> --ac "<nouveau critère>"   # ajouter un critère
pnpm exec backlog task edit <id> --remove-ac <n>            # retirer le critère n (1-based)
pnpm exec backlog task edit <id> --append-notes "<note>"    # notes d'implémentation
```

## Démarrer et terminer (automatique)

- Au démarrage de l'implémentation d'une tâche, sans attendre de demande :
  `pnpm exec backlog task edit <id> -s "In Progress"`
- À la fin, une fois `just test` et `just sf lint:container` passés, cocher les critères remplis et terminer :
  `pnpm exec backlog task edit <id> --check-ac 1 --check-ac 2 -s "Done"`
- Les changements de `.backlog/` sont commités avec le code de la tâche.

Pour toute option non listée ici : `pnpm exec backlog task <commande> --help`.
