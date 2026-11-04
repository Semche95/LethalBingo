---
id: TASK-27
title: Ajouter un son déclenché au début de la partie
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:37:00Z'
labels:
  - timer
dependencies: []
priority: medium
type: feature
ordinal: 31000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Des sons signalent déjà les paliers du timer et la fin de la partie, mais rien ne marque son début. Un son doit être joué au démarrage de la partie pour prévenir les joueurs que le temps commence à s'écouler.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Un son est joué sur la page des joueurs au démarrage de la partie
- [ ] #2 Le son n'est pas rejoué au rechargement de la page
- [ ] #3 Le son suit le même réglage d'activation que les autres alertes du timer
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
