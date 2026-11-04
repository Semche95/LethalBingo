---
id: TASK-41
title: Permettre de modifier les temps forts après la fin de la partie
status: To Do
assignee: []
created_date: '2026-11-04T16:45:00Z'
labels:
  - historique
dependencies: []
priority: low
type: feature
ordinal: 45000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Une fois la partie terminée, la liste des temps forts reste consultable mais n'est plus modifiable : l'arbitre ne peut plus compléter une note, la corriger ni supprimer un temps fort, alors que le montage de la vidéo finale se fait après la partie. Il reste à décider si cette modification après coup est utile, puis à la permettre le cas échéant.

**Renvois**
- TASK-17 : Permettre de marquer un temps fort pendant la partie
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La décision de permettre ou non la modification des temps forts après la fin de la partie est prise
- [ ] #2 Si elle est retenue, l'arbitre peut modifier la note d'un temps fort et le supprimer, avec confirmation, après la fin de la partie
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
