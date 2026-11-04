---
id: TASK-28
title: >-
  Empêcher une équipe de démarrer un round tant qu'elle n'a pas terminé le round
  précédent
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:37:00Z'
labels:
  - rounds
dependencies: []
priority: medium
type: feature
ordinal: 32000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Chaque équipe doit jouer tous les rounds dans l'ordre, mais rien n'empêche aujourd'hui de démarrer une partie d'un round alors que l'équipe n'a pas terminé le round précédent. Le démarrage doit être bloqué dans ce cas. L'avancement des autres équipes n'entre pas en compte : une équipe peut être au round 4 pendant qu'une autre est au round 1.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Une partie ne peut pas démarrer tant que son équipe n'a pas terminé sa partie du round précédent
- [ ] #2 L'avancement des autres équipes n'empêche jamais le démarrage d'une partie
- [ ] #3 L'arbitre voit pourquoi le démarrage est refusé
- [ ] #4 Le premier round peut toujours démarrer
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
