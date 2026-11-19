---
id: TASK-23
title: Implémenter le calcul automatique des points selon les règles sélectionnées
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-19T00:48:00Z'
labels:
  - regles-scoring
dependencies:
  - TASK-21
documentation:
  - .backlog/docs/doc-1 - Barème-des-règles-de-scoring.md
priority: medium
type: feature
ordinal: 27000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le score d'une partie est aujourd'hui calculé à la main. L'application doit calculer automatiquement les points d'une partie à partir des règles sélectionnées pour elle. Le barème est arrêté dans le document « Barème des règles de scoring ».

**Renvois**
- TASK-21 : Permettre le choix des règles propres à chaque partie
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le barème de chaque règle de scoring est arrêté
- [x] #2 Les points d'une partie sont calculés automatiquement à partir des règles sélectionnées pour elle
- [x] #3 Le détail des points obtenus par règle est affiché à l'arbitre
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
Barème : points = valeur × coefficient (doc-1). Valeurs des règles libres dans `GameRuleValue` (une par partie et règle). Une fois la partie terminée, un bouton Points à côté des temps forts ouvre la modale `game-points-modal` (Live Component `GamePoints`), où l'arbitre saisit les règles libres. Calcul dans `GameScoreService::compute()`, recalculé à chaque affichage. Règles d'un round triées par identifiant (`OrderBy`), comme dans l'onglet Règles. La réinitialisation et le retrait d'une règle du round suppriment les valeurs concernées. Vérifié à l'écran à 1280 et 375 px.
<!-- SECTION:NOTES:END -->
