---
id: TASK-19
title: Afficher l'historique des actions à droite du bingo avec les temps forts
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:37:00Z'
labels:
  - historique
dependencies:
  - TASK-17
  - TASK-18
priority: medium
type: feature
ordinal: 23000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
L'arbitre ne voit pas le déroulé d'une partie : quelles cases ont été dévoilées, masquées, validées ou invalidées, et quand. Un historique affiché à droite du bingo, réservé à l'arbitre, doit lister ces actions dans l'ordre chronologique, avec les temps forts marqués pendant la partie, pour suivre la partie en direct puis la revoir une fois terminée.

**Renvois**
- TASK-17 : Permettre de marquer un temps fort pendant la partie
- TASK-18 : Conserver la date de chaque action effectuée sur une case
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 L'historique s'affiche à droite du bingo
- [ ] #2 Chaque action sur une case y figure avec sa date, la case concernée et le type d'action
- [ ] #3 Les temps forts y figurent à leur place chronologique
- [ ] #4 L'historique se met à jour en direct pendant la partie
- [ ] #5 L'historique reste consultable une fois la partie terminée
- [ ] #6 L'historique n'est visible que de l'arbitre et n'est jamais transmis aux joueurs
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Toute nouvelle classe de `src/Service/`, `src/Controller/` ou `src/Repository/` est accompagnée de son test
- [ ] #2 Tout changement d'entité est accompagné de sa migration et des fixtures mises à jour
- [ ] #3 `just test` passe
- [ ] #4 `just sf lint:container` passe
- [ ] #5 `just phpstan` passe
- [ ] #6 `just cs` passe
- [ ] #7 `just rector` passe
<!-- DOD:END -->
