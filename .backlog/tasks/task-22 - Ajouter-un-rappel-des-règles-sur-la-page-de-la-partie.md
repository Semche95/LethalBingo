---
id: TASK-22
title: Ajouter un rappel des règles sur la page de la partie
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - regles-scoring
dependencies:
  - TASK-21
documentation:
  - .backlog/docs/doc-1 - Règles-de-scoring-à-définir.md
priority: medium
type: feature
ordinal: 26000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les joueurs n'ont pas les règles sous les yeux pendant la partie. La page de la partie doit rappeler les règles choisies pour cette partie, afin que les joueurs puissent les consulter à tout moment sans quitter la page.

**Renvois**
- TASK-21 : Permettre le choix des règles propres à chaque partie
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La page de la partie affiche les règles sélectionnées pour cette partie
- [ ] #2 Le rappel est visible des joueurs comme de l'arbitre
- [ ] #3 Une partie sans règle sélectionnée n'affiche pas de rappel vide
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
