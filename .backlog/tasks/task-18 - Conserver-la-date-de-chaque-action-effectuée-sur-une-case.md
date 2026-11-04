---
id: TASK-18
title: Conserver la date de chaque action effectuée sur une case
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - historique
dependencies: []
priority: medium
type: feature
ordinal: 22000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Aujourd'hui, seule la date à laquelle une case est dévoilée ou validée est conservée, et elle disparaît quand la case est masquée ou invalidée. L'application doit garder la trace datée de chaque action effectuée sur une case pendant une partie, y compris quand la même action est répétée, pour pouvoir reconstituer le déroulé complet de la partie.

**Renvois**
- TASK-19 : Afficher l'historique des actions à droite du bingo avec les temps forts
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La date de révélation est conservée
- [ ] #2 La date de masquage est conservée
- [ ] #3 La date de validation est conservée
- [ ] #4 La date d'invalidation est conservée
- [ ] #5 Une action répétée sur la même case conserve la date de chaque occurrence
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
