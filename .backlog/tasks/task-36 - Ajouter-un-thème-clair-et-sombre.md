---
id: TASK-36
title: Ajouter un thème clair et sombre
status: To Do
assignee: []
created_date: 2026-10-09 21:10
updated_date: '2026-11-18T22:11:00Z'
labels:
  - ui
dependencies: []
priority: low
type: feature
ordinal: 40000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
L'interface n'a qu'un seul thème. Permettre de basculer entre un thème clair et un thème sombre en s'appuyant sur ce que Bootstrap 5.3 et Tabler fournissent déjà.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Un sélecteur permet de passer du thème clair au thème sombre
- [ ] #2 Le choix est conservé d'une visite à l'autre
- [ ] #3 Toutes les pages restent lisibles dans les deux thèmes
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Toute nouvelle classe de `src/Service/`, `src/Controller/` ou `src/Repository/` est accompagnée de son test
- [ ] #2 Tout changement d'entité est accompagné de sa migration et des fixtures mises à jour
- [ ] #3 Les docs concernées par le changement (`docs/`, `AGENTS.md`, `.claude/`) sont mises à jour
- [ ] #4 `just test` passe
- [ ] #5 `just sf lint:container` passe
- [ ] #6 `just phpstan` passe
- [ ] #7 `just cs` passe
- [ ] #8 `just rector` passe
<!-- DOD:END -->
