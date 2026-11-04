---
id: TASK-18
title: Conserver la date de chaque action effectuée sur une case
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T15:00:00Z'
labels:
  - historique
dependencies: []
priority: medium
type: feature
ordinal: 22000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Aujourd'hui, seule la date à laquelle une case est dévoilée ou validée est conservée, et elle disparaît quand la case est masquée ou invalidée. L'application doit garder la trace datée de chaque action effectuée sur une case pendant une partie, y compris quand la même action est répétée, pour pouvoir reconstituer le déroulé complet de la partie.

**Renvois**
- TASK-19 : Afficher l'historique des actions à droite du bingo avec les temps forts
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La date de révélation est conservée
- [x] #2 La date de masquage est conservée
- [x] #3 La date de validation est conservée
- [x] #4 La date d'invalidation est conservée
- [x] #5 Une action répétée sur la même case conserve la date de chaque occurrence
- [x] #6 Deux actions simultanées sur la même partie (double clic, deux arbitres) s'appliquent l'une après l'autre et n'enregistrent jamais deux fois la même action
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Toute nouvelle classe de `src/Service/`, `src/Controller/` ou `src/Repository/` est accompagnée de son test
- [x] #2 Tout changement d'entité est accompagné de sa migration et des fixtures mises à jour
- [x] #3 `just test` passe
- [x] #4 `just sf lint:container` passe
- [x] #5 `just phpstan` passe
- [x] #6 `just cs` passe
- [x] #7 `just rector` passe
<!-- DOD:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Nouvelle entité `GameSquareAction` (partie, case, type, date) et enum `SquareActionType`.
2. Enregistrer une action à chaque dévoilement, masquage, validation et invalidation effectifs dans `BingoService`.
3. Effacer l'historique avec les marques lors de la remise à zéro d'une partie.
4. Migration, fixtures, tests.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Journal `game_square_action` à côté des marques existantes (`BingoSquareRevealed`/`BingoSquareValidated`), qui gardent l'état courant de la grille. `BingoService::mark()`/`unmark()` persistent l'action dans le même flush que la marque : un dévoilement concurrent rejeté par la contrainte d'unicité n'enregistre rien, et masquer ou invalider une case non marquée non plus.
Clés étrangères `ON DELETE CASCADE` vers la partie et la case ; `Game::getSquareActions()` trié par date puis id (les dates sont à la seconde, l'id départage). `GameService::reset()` supprime aussi l'historique.
Pas de repository dédié : aucune requête spécifique pour l'instant, à ajouter avec l'affichage de l'historique.
Fixtures : les parties commencées reçoivent des cases dévoilées et validées avec leur historique, dont des actions répétées (dévoilée, masquée, redévoilée).
Tests : `BingoServiceTest` (action enregistrée par type), `GameServiceTest` (reset), `BingoServiceSquareActionsTest` en base (action répétée, aucune action sur une case non marquée).

Concurrence : `symfony/lock` installé. Chaque action sur les cases d'une partie (vérification de l'état, modification, enregistrement) s'exécute sous un verrou par partie (`game-squares-<id>`), verrou consultatif PostgreSQL pris sur la connexion Doctrine (`DoctrineDbalPostgreSqlStore` déclaré dans `services.yaml`, `framework.lock: false` car la config du framework ne construit ce store qu'à partir d'un DSN, qui dupliquerait `DATABASE_URL`). La contrainte d'unicité reste en filet de sécurité. Blocage entre deux processus vérifié à la main sur la base de dev.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Chaque dévoilement, masquage, validation et invalidation d'une case est désormais conservé avec sa date dans un historique par partie, y compris les actions répétées ; il est effacé à la remise à zéro de la partie.
<!-- SECTION:FINAL_SUMMARY:END -->
