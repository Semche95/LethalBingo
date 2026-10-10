---
id: TASK-40
title: >-
  Ne pas proposer un round déjà joué par l'équipe choisie à la création d'une
  partie
status: To Do
assignee: []
created_date: '2026-10-10 01:53'
updated_date: '2026-10-10 02:02'
labels:
  - gestion-parties
dependencies: []
priority: medium
type: enhancement
ordinal: 44000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Dans le formulaire de création d'une partie, la liste des rounds propose aujourd'hui tous les rounds, y compris ceux que l'équipe choisie a déjà joués. Une même équipe ne doit pas pouvoir rejouer le même round : une fois l'équipe choisie, seuls les rounds pour lesquels elle n'a pas encore de partie doivent être proposés. Un round déjà joué par une équipe reste disponible pour les autres équipes.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Une fois l'équipe choisie, la liste des rounds ne propose que les rounds pour lesquels cette équipe n'a pas encore de partie
- [ ] #2 Un round déjà joué par une équipe reste proposé aux autres équipes
- [ ] #3 L'enregistrement d'une partie pour un couple équipe et round qui a déjà une partie est refusé avec un message d'erreur
- [ ] #4 À la modification d'une partie, le round déjà associé à cette partie reste proposé
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
