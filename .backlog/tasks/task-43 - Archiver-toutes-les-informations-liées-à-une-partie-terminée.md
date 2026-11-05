---
id: TASK-43
title: Archiver toutes les informations liées à une partie terminée
status: To Do
assignee: []
created_date: '2026-11-05T19:56:00Z'
labels:
  - historique
  - gestion-parties
dependencies: []
priority: medium
type: feature
ordinal: 47000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Une partie terminée reste aujourd'hui liée directement aux autres tables (équipe, round, bingo, cases, règles) : toute modification ou suppression de l'une de ces données change ou casse l'historique de la partie. À la fin d'une partie, toutes les informations qui lui sont liées doivent être archivées avec elle, et la partie terminée ne doit plus être liée directement aux autres tables.

**Renvois**
- TASK-20 : Permettre la saisie des règles dans le tableau de bord
- TASK-29 : Interdire la modification d'un round ou d'une équipe si une partie associée est en cours ou terminée
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 À la fin d'une partie, toutes les informations qui lui sont liées sont archivées avec elle
- [ ] #2 Une partie terminée n'est plus liée directement aux autres tables
- [ ] #3 Modifier ou supprimer une donnée d'origine ne change pas une partie terminée
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
