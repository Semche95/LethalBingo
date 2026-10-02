---
id: TASK-35
title: Traduire l'application en anglais avec le français en seconde langue
status: To Do
assignee: []
created_date: '2026-10-02 23:06'
updated_date: '2026-10-02 23:06'
labels:
  - ui
  - traduction
dependencies: []
priority: low
type: feature
ordinal: 39000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
L'interface et une partie des fichiers sources (textes, commentaires) sont aujourd'hui en français. L'application doit devenir entièrement traduisible : l'anglais deviendra la langue par défaut, aussi bien pour l'interface que pour les fichiers sources, et le français restera disponible pour les utilisateurs qui le choisissent.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tous les textes de l'interface passent par le système de traduction
- [ ] #2 L'anglais est la langue par défaut de l'interface
- [ ] #3 Le français reste disponible et sélectionnable
- [ ] #4 Les textes et commentaires des fichiers sources sont en anglais
- [ ] #5 Le README ne mentionne plus une interface uniquement en français
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
