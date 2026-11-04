---
id: TASK-29
title: >-
  Interdire la modification d'un round ou d'une équipe si une partie associée
  est en cours ou terminée
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - rounds
dependencies: []
priority: medium
type: feature
ordinal: 33000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Modifier un round ou une équipe change aussi les parties qui y sont liées, y compris celles déjà jouées ou en cours, ce qui fausse leur déroulé et leur résultat. Un round ou une équipe ne doit plus être modifiable dès qu'une partie associée est en cours ou terminée. La modification reste autorisée tant que ses parties associées n'ont pas démarré.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Un round associé à une partie en cours ou terminée ne peut plus être modifié
- [ ] #2 Une équipe associée à une partie en cours ou terminée ne peut plus être modifiée
- [ ] #3 Un round ou une équipe dont les parties associées n'ont pas démarré reste modifiable
- [ ] #4 L'arbitre voit pourquoi la modification est refusée
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
