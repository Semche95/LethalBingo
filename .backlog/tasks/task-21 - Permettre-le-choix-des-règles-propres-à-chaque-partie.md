---
id: TASK-21
title: Permettre le choix des règles propres à chaque partie
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - regles-scoring
dependencies:
  - TASK-20
documentation:
  - .backlog/docs/doc-1 - Règles-de-scoring-à-définir.md
priority: medium
type: feature
ordinal: 25000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Toutes les parties ne se jouent pas avec les mêmes règles. Lors de la création ou de la modification d'une partie, l'arbitre doit pouvoir choisir, parmi les règles saisies dans le tableau de bord, celles qui s'appliquent à cette partie.

**Renvois**
- TASK-20 : Permettre la saisie des règles dans le tableau de bord
- TASK-22 : Ajouter un rappel des règles sur la page de la partie
- TASK-23 : Implémenter le calcul automatique des points selon les règles sélectionnées
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le formulaire d'une partie propose les règles saisies dans le tableau de bord
- [ ] #2 L'arbitre peut sélectionner plusieurs règles pour une partie
- [ ] #3 Les règles sélectionnées sont enregistrées avec la partie et retrouvées à sa modification
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
