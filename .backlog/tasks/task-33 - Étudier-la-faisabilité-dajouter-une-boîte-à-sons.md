---
id: TASK-33
title: Étudier la faisabilité d'ajouter une boîte à sons
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:37:00Z'
labels:
  - contenu
dependencies: []
priority: medium
type: spike
ordinal: 37000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
L'application joue déjà des sons pour le timer. Une boîte à sons permettrait de déclencher d'autres sons pendant une partie pour l'animer. Avant de la développer, il faut étudier sa faisabilité : qui déclenche les sons, qui les entend, et comment les diffuser aux joueurs en direct.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Les usages attendus sont décrits (qui déclenche les sons, qui les entend)
- [ ] #2 Les solutions techniques possibles sont comparées
- [ ] #3 Une recommandation est consignée dans Backlog.md
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
