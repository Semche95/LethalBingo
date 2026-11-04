---
id: TASK-26
title: >-
  Permettre la configuration de la durée du timer avant le démarrage de la
  partie
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - timer
dependencies: []
priority: medium
type: feature
ordinal: 30000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La durée du timer est aujourd'hui fixée à une heure pour toutes les parties. L'arbitre doit pouvoir choisir la durée du timer de chaque partie avant son démarrage, ou s'en passer complètement pour une partie sans limite de temps.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La durée par défaut est de 1h30
- [ ] #2 Le timer peut être complètement désactivé
- [ ] #3 La durée du timer se règle à la création ou à la modification d'une partie, tant qu'elle n'a pas démarré
- [ ] #4 Le timer de la partie démarre avec la durée choisie
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
