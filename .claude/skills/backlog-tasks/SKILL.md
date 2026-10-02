---
name: backlog-tasks
description: Gérer les tâches du projet LethalBingo stockées dans .backlog/tasks avec le CLI Backlog.md. À utiliser dès que l'utilisateur parle d'une tâche (TASK-xx, « la tâche », « les tâches », « le backlog ») ou demande de créer, consulter, lister, rechercher, modifier, démarrer ou terminer une tâche.
---

# Tâches Backlog.md

Dans ce projet, une « tâche » désigne toujours une tâche Backlog.md, stockée dans `.backlog/tasks/`. Toutes les notes du projet (constats d'investigation, détails d'implémentation, TODO, bugs, plans, questions ouvertes, décisions) vivent dans Backlog.md (`.backlog/` : `tasks/`, `docs/`, `decisions/`, `completed/`, `archive/`), jamais dans un fichier Markdown libre ailleurs.

## Règles générales

- Toujours passer par le CLI : `pnpm exec backlog …` (version épinglée dans `package.json`). Jamais `pnpm dlx` ni `npx`.
- Ne pas lire ni modifier les fichiers `.backlog/tasks/*.md` à la main : le CLI garde le format, les identifiants et les métadonnées cohérents.
- Utiliser `--plain` pour obtenir une sortie texte lisible.
- La branche `main` est la seule source de vérité pour l'état des tâches : il ne se lit et ne se modifie que sur `main`.
- Rédiger titres, descriptions et critères d'acceptation en français.
- Un sous-agent qui doit lire ou modifier Backlog.md charge lui aussi cette skill avant d'agir.
- Avant d'investiguer un bug ou de planifier une nouvelle fonctionnalité, chercher les constats antérieurs dans Backlog.md (tâches, documents, décisions) : beaucoup de comportements subtils y sont déjà documentés.
- Les connaissances transverses vont dans un document Backlog.md (par ex. les règles de scoring à définir, doc-1), et les choix métier ou techniques tranchés dans une décision Backlog.md.
- Dans les réponses, citer une tâche par son sujet suivi de son identifiant (« le timer synchronisé avec l'heure de validation des cases (TASK-12) »), jamais par l'identifiant seul.

## Sections d'une tâche

Un sujet de travail (bug, demande, point à vérifier, optimisation) est une tâche Backlog.md. Chaque section a son usage et son moment :

- **Description** (`--description` / `-d`) : le pourquoi et le contexte (constat, état actuel, ce qui bloque éventuellement), rédigés à la création. Tant que la tâche n'est pas commencée, tous ses constats et pistes envisagées, y compris un plan déjà esquissé, vont ici.
- **Acceptance Criteria** (`--ac`) : les résultats attendus, vérifiables, rédigés à la création.
- **Implementation Plan** (`--plan`) : les étapes techniques, rédigées au démarrage (passage en « In Progress »).
- **Implementation Notes** (`--notes` / `--append-notes`) : ce qui a été fait, constats et écueils rencontrés, pendant et après le travail. Jamais remplies sur une tâche pas encore commencée.
- **Final Summary** (`--final-summary`) : le bilan, à la clôture.

Rédaction :

- La description se lit seule, en prose claire, par quelqu'un qui ne connaît pas l'historique : ce que la fonctionnalité apporte et ce qu'elle doit faire. Chemins de fichiers, numéros de ligne, noms de fonctions, exemples de code et remarques de validation vont dans les notes d'implémentation, pas dans la description.
- Les commentaires de statut et de validation tiennent en une ligne courte. Le détail technique plus long va dans les notes d'implémentation ou dans un document Backlog.md thématique.
- Quand l'utilisateur dicte ce qu'il faut noter dans une tâche, écrire exactement cela, sans ajouter l'historique de la session (implémentation commencée puis annulée, fichiers restaurés, etc.).
- Ne pas ajouter de champ vide à remplir plus tard (« Commentaire : » sans contenu) : seulement des lignes qui ont un contenu réel.

Les tâches liées entre elles sont listées dans une section `**Renvois**` en fin de description (`- TASK-x : titre`), des deux côtés.

## Statuts et priorités

- Le statut dit seulement où en est la tâche (« To Do », « In Progress », « Done ») : ne pas créer d'autre statut (pas de « Pour plus tard » ni de « Bloqué »).
- Une tâche peu urgente reçoit la priorité `low` ; une tâche bloquée reste « To Do » et ce qui la bloque est écrit dans sa description ou dans un critère d'acceptation.

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
  --priority medium --type <type> -l <labels> [-p <tâche parente>] [--dep <ids>]
```

- Toute nouvelle tâche est créée en priorité `medium` (Backlog.md n'a pas de réglage de priorité par défaut). `low` ou `high` ne servent qu'à ajuster au cas par cas quand c'est justifié.
- Toute nouvelle tâche reçoit un type (`--type`, parmi `bug`, `feature`, `enhancement`, `task`, `chore`, `docs`, `spike`) et au moins un label (`-l`). Réutiliser en priorité les labels existants (`timer`, `gestion-parties`, `dashboard-arbitre`, `regles-scoring`, `ui`, `historique`, `rounds`, `preparation`, `contenu`) et n'en créer un nouveau, court et en minuscules avec tirets, que si aucun ne convient.

Afficher ensuite la tâche créée avec `task view` pour confirmer son identifiant.

## Modifier

```bash
pnpm exec backlog task edit <id> -t "<nouveau titre>"
pnpm exec backlog task edit <id> -d "<nouvelle description>"
pnpm exec backlog task edit <id> --ac "<nouveau critère>"   # ajouter un critère
pnpm exec backlog task edit <id> --remove-ac <n>            # retirer le critère n (1-based)
pnpm exec backlog task edit <id> --append-notes "<note>"    # notes d'implémentation
```

Dès qu'un critère d'acceptation est ajouté à une tâche « To Do » ou « In Progress », décocher tous les éléments de sa Definition of Done (`--uncheck-dod` pour chaque élément coché), même si seuls certains étaient cochés : la Definition of Done porte sur l'ensemble des points de la tâche et n'est cochée qu'une fois vérifiée sur tous, y compris les critères ajoutés.

## Démarrer et terminer (automatique)

- Au démarrage d'une tâche, passer son statut à « In Progress » avant toute autre action (lecture de code, plan, délégation à un sous-agent, modification), sans attendre de demande :
  `pnpm exec backlog task edit <id> -s "In Progress"`
- À la fin, une fois les vérifications passées (`just test`, `just sf lint:container`, `just phpstan`, `just cs`, `just rector`), cocher les critères d'acceptation remplis (s'il y en a) et tous les éléments de la Definition of Done, puis terminer :
  `pnpm exec backlog task edit <id> --check-ac <n> [--check-ac <m> …] --check-dod <n> [--check-dod <m> …] -s "Done"`
- Jamais de passage en « Done » tant qu'un élément de la Definition of Done reste décoché : cocher chaque élément seulement après l'avoir réellement vérifié, contrôler avec `task view` avant de changer le statut. Si un élément ne peut pas être coché, la tâche reste « In Progress » et la raison est indiquée à l'utilisateur.
- Si une vérification manuelle de l'utilisateur reste nécessaire avant de considérer la tâche finie, la tâche reste « In Progress » et il faut dire ce qu'il reste à vérifier.
- Passer une tâche en « Done » n'attend pas de commit, et ne déclenche jamais de commit : attendre une demande explicite. Une tâche ou un fichier de suivi qui décrit le commit comme faisant partie du workflow n'est pas une autorisation implicite.
- Les changements de fichiers `.backlog/` sont commités avec le code de la tâche.

## Points restants

- Quand le travail sur une tâche fait ressortir des points restants (limite ouverte, cas limite non testé, vérification qui dépend de la production, etc.), ne pas la passer en « Done » : elle garde son statut actuel et chaque point est ajouté explicitement comme critère d'acceptation non coché (`--ac`), avec décochage de la Definition of Done (voir « Modifier »). Les mentionner seulement dans les notes d'implémentation ne suffit pas : un point restant à vérifier fait partie de la tâche.
- Exception : un point que personne ne peut vérifier à court terme (typiquement ce qui dépend de la production) ne bloque pas la tâche indéfiniment. Il est déplacé dans une nouvelle tâche en priorité `low`, liée à la tâche d'origine dans les deux sections `**Renvois**`.

## Tâches en « Done »

- Une tâche en « Done » n'est jamais rouverte (pas de retour en « In Progress » ni « To Do », pas de modification de ses critères d'acceptation ni de sa Definition of Done), même pour une suite demandée juste après sa clôture.
- Tout travail qui la concerne après coup (point défaillant, ajustement ou comportement supplémentaire demandé, nettoyage, confirmation à obtenir de l'utilisateur) fait l'objet d'une nouvelle tâche dédiée, liée à la tâche d'origine dans les deux sections `**Renvois**`.
- Seules exceptions autorisées, sans toucher à son statut, à ses critères d'acceptation ni à sa Definition of Done : modifier sa description pour compléter sa section `**Renvois**`, et compléter ou corriger son type (`--type`) et ses labels (`--add-label`/`--remove-label`).

Pour toute option non listée ici : `pnpm exec backlog task <commande> --help`.
