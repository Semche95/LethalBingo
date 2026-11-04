---
id: TASK-24
title: Permettre d'ajouter ou d'enlever la durée voulue au timer
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:39:00Z'
labels:
  - timer
dependencies: []
priority: medium
type: feature
ordinal: 28000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pendant une partie, l'arbitre ne peut ajouter ou enlever du temps au timer que par pas d'une minute. Il doit pouvoir ajouter ou enlever la durée qu'il veut en une seule fois. Les boutons actuels qui règlent le timer sur des valeurs prédéfinies ne servent qu'au développement et ne s'affichent pas en production : ils ne remplacent pas cette saisie, qui doit être disponible à tout arbitre en production.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 L'arbitre peut saisir une durée et l'ajouter au timer
- [ ] #2 L'arbitre peut saisir une durée et l'enlever du timer
- [ ] #3 La saisie est disponible en production, pour tout arbitre
- [ ] #4 Le temps restant est mis à jour en direct chez les joueurs
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
