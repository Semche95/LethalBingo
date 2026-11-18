---
id: TASK-20
title: Permettre la saisie des règles dans le tableau de bord
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-18T22:36:00Z'
labels:
  - regles-scoring
dependencies: []
documentation:
  - .backlog/docs/doc-1 - Règles-de-scoring-à-définir.md
priority: medium
type: feature
ordinal: 24000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les règles de scoring ne sont aujourd'hui recensées que dans le document « Règles de scoring à définir » : l'application ne les connaît pas. Les arbitres doivent pouvoir saisir et maintenir la liste des règles de scoring dans un onglet « Règles » de leur espace, pour ensuite choisir celles qui s'appliquent à chaque partie.

Chaque règle se compose d'une mesure et d'un coefficient, le nombre de points par unité de la mesure, négatif autorisé. Le score d'une partie vaudra la somme des valeurs des mesures multipliées par leur coefficient.

Règles retenues :
- les règles calculées par l'application (cases validées, lignes de 4 cases validées, lignes de 5 cases validées) sont prédéfinies, avec un libellé fixe : l'arbitre ne les crée ni ne les supprime, il ne modifie que leur coefficient ;
- une ligne compte une seule fois, dans la catégorie la plus haute : une ligne de 5 cases validées compte parmi les lignes de 5 cases, pas parmi celles de 4 ;
- « Cases dévoilées à la demande » (−50) est une règle libre créée d'office ;
- l'arbitre crée lui-même les règles libres : une mesure écrite dans sa langue et un coefficient ; il saisira leur valeur à l'arrêt de la partie ;
- les règles sont communes à tous les arbitres, comme les équipes, les bingos et les parties.

Les règles affichées sur la page d'accueil forment un code de conduite des joueurs, sans rapport avec le scoring : cette tâche ne les concerne pas.

**Renvois**
- TASK-21 : Permettre le choix des règles propres à chaque partie
- TASK-37 : Restreindre la visibilité des équipes, bingos et parties à des groupes d'arbitres
- TASK-43 : Archiver toutes les informations liées à une partie terminée
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un onglet « Règles » de l'espace arbitre liste les règles avec leur mesure, leur coefficient et l'origine de leur valeur (saisie par l'arbitre ou calculée par l'application)
- [x] #2 Les règles calculées par l'application (cases validées, lignes de 4 cases validées, lignes de 5 cases validées) existent d'office, avec un libellé fixe
- [x] #3 Un arbitre peut créer une règle libre en saisissant sa mesure et son coefficient, négatif autorisé
- [x] #4 Un arbitre peut modifier la mesure et le coefficient d'une règle libre, et seulement le coefficient d'une règle calculée
- [x] #5 Un arbitre peut supprimer une règle libre après confirmation ; la suppression d'une règle calculée est impossible et son bouton désactivé l'explique
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Toute nouvelle classe de `src/Service/`, `src/Controller/` ou `src/Repository/` est accompagnée de son test
- [x] #2 Tout changement d'entité est accompagné de sa migration et des fixtures mises à jour
- [x] #3 Les docs concernées par le changement (`docs/`, `AGENTS.md`, `.claude/`) sont mises à jour
- [x] #4 `just test` passe
- [x] #5 `just sf lint:container` passe
- [x] #6 `just phpstan` passe
- [x] #7 `just cs` passe
- [x] #8 `just rector` passe
<!-- DOD:END -->
