---
id: TASK-30
title: >-
  Pré-remplir les cases des bingos depuis un fichier Excel avec des migrations
  Symfony
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - preparation
dependencies: []
priority: medium
type: task
ordinal: 34000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les cases des bingos sont aujourd'hui saisies une à une dans l'éditeur. Leur contenu est déjà préparé dans un fichier Excel : des migrations Symfony doivent insérer ces cases, pour que chaque environnement, production comprise, dispose des bingos sans ressaisie.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Une migration insère les cases de chaque bingo du fichier Excel
- [ ] #2 Le libellé, la catégorie et la position de chaque case correspondent au fichier Excel
- [ ] #3 La migration s'applique sur une base existante sans altérer les autres données
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
