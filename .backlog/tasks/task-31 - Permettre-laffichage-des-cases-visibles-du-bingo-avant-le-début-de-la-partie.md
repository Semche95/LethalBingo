---
id: TASK-31
title: Permettre l'affichage des cases visibles du bingo avant le début de la partie
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-21T10:24:00Z'
labels:
  - preparation
dependencies: []
priority: medium
type: feature
ordinal: 35000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Avant le début d'une partie, les joueurs ne voient le contenu d'aucune case, même celles qui sont prévues pour être visibles dès le départ. Les cases visibles du bingo doivent être affichées aux joueurs avant le démarrage, pour qu'ils puissent préparer leur partie. Les cases cachées restent masquées jusqu'à leur révélation.

**Renvois**
- TASK-44 : Couper l'accès d'un joueur qui a déjà la page ouverte quand le lien de la partie est régénéré
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Avant le démarrage, les joueurs voient le contenu des cases visibles
- [x] #2 Avant le démarrage, le contenu des cases cachées n'est pas transmis aux joueurs
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

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Déjà implémentée par le commit da207c9 : BingoService::gameSquares() met à null le libellé et la catégorie des cases ni visibles ni dévoilées, avant comme pendant la partie ; couvert par GameControllerTest::testPlayerReceivesOnlyVisibleAndRevealedLabelsBeforeTheStart.
Vérifié : page joueur non connectée d'une partie non démarrée (cases visibles affichées, cases cachées en ??? et absentes du HTML) ; audit security-auditor sans fuite (HTML, props et actions Live, Mercure, routes publiques, réinitialisation).
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Comportement déjà en place depuis da207c9, vérifié par test, à l'écran et par un audit de sécurité ; aucun changement de code.
<!-- SECTION:FINAL_SUMMARY:END -->
