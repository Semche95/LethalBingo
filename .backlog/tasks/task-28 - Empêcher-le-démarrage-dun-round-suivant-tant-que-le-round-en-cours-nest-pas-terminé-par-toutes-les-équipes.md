---
id: TASK-28
title: >-
  Empêcher le démarrage d'un round suivant tant que le round en cours n'est pas
  terminé par toutes les équipes
status: To Do
assignee: []
created_date: '2026-09-30 16:16'
updated_date: '2026-10-09 21:51'
labels:
  - rounds
dependencies: []
ordinal: 32000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
**Renvois**
- TASK-37 : Restreindre la visibilité des équipes, bingos et parties à des groupes d'arbitres (le blocage ne comptera que les équipes du groupe)
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
