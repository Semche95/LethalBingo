---
id: TASK-22
title: Ajouter un rappel des règles sur la page de la partie
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-18T23:36:00Z'
labels:
  - regles-scoring
dependencies:
  - TASK-21
documentation:
  - .backlog/docs/doc-1 - Règles-de-scoring-à-définir.md
priority: medium
type: feature
ordinal: 26000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les joueurs n'ont pas les règles sous les yeux pendant la partie. La page de la partie doit rappeler les règles choisies pour cette partie, afin que les joueurs puissent les consulter à tout moment sans quitter la page.

**Renvois**
- TASK-21 : Permettre le choix des règles propres à chaque partie
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La page de la partie affiche les règles sélectionnées pour cette partie
- [x] #2 Le rappel est visible des joueurs comme de l'arbitre
- [x] #3 Une partie sans règle sélectionnée n'affiche pas de rappel vide
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Toute nouvelle classe de `src/Service/`, `src/Controller/` ou `src/Repository/` est accompagnée de son test
- [x] #2 Tout changement d'entité est accompagné de sa migration et des fixtures mises à jour
- [x] #3 Les docs concernées par le changement (`docs/`, `AGENTS.md`, `.claude/`) sont mises à jour
- [x] #4 `just test` passe
- [x] #5 `just sf lint:container` passe
- [x] #6 `just phpstan` passe
- [x] #7 `just cs` passe
- [x] #8 `just rector` passe
<!-- DOD:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Carte Règles dans GameFrame sous la grille, visible de tous, absente si le round n'a aucune règle.
2. Règles chargées par findOneForPlay() avec la partie.
3. Tests, doc GAME.md, vérification visuelle.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Carte « Règles » ajoutée à GameFrame après la grille (hors de la hauteur Full HD imposée côté joueurs), libellé et coefficient (macro coefficient), styles dans components/rules.css en réutilisant rules-coefficient-cell. GameRepository::findOneForPlay() joint les règles du round pour garder le nombre de requêtes de la page. Ordre d'affichage : celui de la collection du round (pas de tri imposé). Tests dans GameControllerTest, doc dans docs/GAME.md (profils, mise en page). Vérifié à l'écran côté joueur et arbitre, y compris à 375 px.
<!-- SECTION:NOTES:END -->
