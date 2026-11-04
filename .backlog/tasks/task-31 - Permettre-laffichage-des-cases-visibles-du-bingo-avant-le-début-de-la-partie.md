---
id: TASK-31
title: Permettre l'affichage des cases visibles du bingo avant le début de la partie
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - preparation
dependencies: []
priority: medium
type: feature
ordinal: 35000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Avant le début d'une partie, les joueurs ne voient le contenu d'aucune case, même celles qui sont prévues pour être visibles dès le départ. Les cases visibles du bingo doivent être affichées aux joueurs avant le démarrage, pour qu'ils puissent préparer leur partie. Les cases cachées restent masquées jusqu'à leur révélation.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Avant le démarrage, les joueurs voient le contenu des cases visibles
- [ ] #2 Avant le démarrage, le contenu des cases cachées n'est pas transmis aux joueurs
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
