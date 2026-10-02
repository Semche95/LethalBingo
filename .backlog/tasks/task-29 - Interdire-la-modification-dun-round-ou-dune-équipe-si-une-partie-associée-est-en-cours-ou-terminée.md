---
id: TASK-29
title: >-
  Interdire la modification d'un round ou d'une équipe si une partie associée
  est en cours ou terminée
status: To Do
assignee: []
created_date: '2026-09-30 16:16'
updated_date: '2026-10-02 21:11'
labels:
  - rounds
dependencies: []
ordinal: 33000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La modification reste autorisée pour les parties suivantes.
<!-- SECTION:DESCRIPTION:END -->

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
