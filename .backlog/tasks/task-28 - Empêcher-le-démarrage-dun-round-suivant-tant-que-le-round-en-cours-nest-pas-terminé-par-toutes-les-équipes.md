---
id: TASK-28
title: >-
  Avertir l'arbitre quand une équipe démarre un round sans avoir terminé le
  précédent
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-20T22:24:00Z'
labels:
  - rounds
dependencies: []
priority: medium
type: feature
ordinal: 32000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Chaque équipe doit jouer tous les rounds dans l'ordre, mais rien ne signale aujourd'hui qu'une partie va démarrer alors que l'équipe n'a pas terminé sa partie du round précédent. L'arbitre doit en être averti au moment du démarrage, sans être bloqué : il peut quand même démarrer la partie. L'avancement des autres équipes n'entre pas en compte : une équipe peut être au round 4 pendant qu'une autre est au round 1.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Au démarrage d'une partie dont l'équipe n'a pas terminé sa partie du round précédent, l'arbitre voit un avertissement qui explique pourquoi
- [ ] #2 Malgré l'avertissement, l'arbitre peut démarrer la partie
- [ ] #3 L'avancement des autres équipes ne déclenche jamais d'avertissement
- [ ] #4 Le premier round ne déclenche jamais d'avertissement
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
