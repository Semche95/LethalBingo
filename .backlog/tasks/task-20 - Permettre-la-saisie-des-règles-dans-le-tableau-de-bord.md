---
id: TASK-20
title: Permettre la saisie des règles dans le tableau de bord
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - regles-scoring
dependencies: []
documentation:
  - .backlog/docs/doc-1 - Règles-de-scoring-à-définir.md
priority: medium
type: feature
ordinal: 24000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les règles du jeu sont aujourd'hui écrites en dur sur la page d'accueil et ne peuvent pas être modifiées sans toucher au code. Les arbitres doivent pouvoir saisir et maintenir la liste des règles depuis leur tableau de bord, pour ensuite choisir celles qui s'appliquent à chaque partie.

**Renvois**
- TASK-21 : Permettre le choix des règles propres à chaque partie
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le tableau de bord de l'arbitre liste les règles existantes
- [ ] #2 Un arbitre peut créer une règle
- [ ] #3 Un arbitre peut modifier une règle
- [ ] #4 Un arbitre peut supprimer une règle
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
