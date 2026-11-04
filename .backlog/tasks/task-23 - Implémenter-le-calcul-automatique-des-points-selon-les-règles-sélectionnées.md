---
id: TASK-23
title: Implémenter le calcul automatique des points selon les règles sélectionnées
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - regles-scoring
dependencies:
  - TASK-21
documentation:
  - .backlog/docs/doc-1 - Règles-de-scoring-à-définir.md
priority: medium
type: feature
ordinal: 27000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le score d'une partie est aujourd'hui calculé à la main. L'application doit calculer automatiquement les points d'une partie à partir des règles sélectionnées pour elle. Le barème n'est pas encore arrêté : les pistes envisagées (nombre de jours maximum, argent maximum sur le terminal, temps restant) sont recensées dans le document « Règles de scoring à définir ».

**Renvois**
- TASK-21 : Permettre le choix des règles propres à chaque partie
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le barème de chaque règle de scoring est arrêté
- [ ] #2 Les points d'une partie sont calculés automatiquement à partir des règles sélectionnées pour elle
- [ ] #3 Le détail des points obtenus par règle est affiché à l'arbitre
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
