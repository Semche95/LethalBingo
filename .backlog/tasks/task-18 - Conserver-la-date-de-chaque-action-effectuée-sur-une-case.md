---
id: TASK-18
title: Conserver la date de chaque action effectuée sur une case
status: To Do
assignee: []
created_date: '2026-09-30 16:16'
updated_date: '2026-10-02 21:11'
labels:
  - historique
dependencies: []
ordinal: 22000
---

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La date de révélation est conservée
- [ ] #2 La date de masquage est conservée
- [ ] #3 La date de validation est conservée
- [ ] #4 La date d'invalidation est conservée
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
