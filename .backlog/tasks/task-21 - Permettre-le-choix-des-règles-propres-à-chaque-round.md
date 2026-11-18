---
id: TASK-21
title: Permettre le choix des règles propres à chaque round
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-18T23:18:00Z'
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
Tous les rounds ne se jouent pas avec les mêmes règles. Lors de la création ou de la modification d'un round, l'arbitre doit pouvoir choisir, parmi les règles saisies dans le tableau de bord, celles qui s'appliquent à ce round.

**Renvois**
- TASK-20 : Permettre la saisie des règles dans le tableau de bord
- TASK-22 : Ajouter un rappel des règles sur la page de la partie
- TASK-23 : Implémenter le calcul automatique des points selon les règles sélectionnées
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Le formulaire d'un round propose les règles saisies dans le tableau de bord
- [x] #2 L'arbitre peut sélectionner plusieurs règles pour un round
- [x] #3 Les règles sélectionnées sont enregistrées avec le round et retrouvées à sa modification
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
1. Relation plusieurs-à-plusieurs GameRound → Rule, migration et fixtures
2. Champ de cases à cocher dans le formulaire du bingo, synchronisé par BingoService
3. Suppression d'une règle : retirée des rounds (ON DELETE CASCADE)
4. Tests, README, vérification visuelle
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Relation unidirectionnelle `GameRound::$rules` (table `game_round_rule`, `ON DELETE CASCADE` côté règle : une règle supprimée quitte les rounds, choix de l'utilisateur ; la modale de suppression le dit).
`BingoDto::$rules`, champ `rules` (EntityType multiple, cases à cocher, ordre de `findAllOrdered()`, facultatif) dans `BingoFormType`.
`BingoService::entityToFormDto()` ajoute les règles au DTO pour le seul formulaire (pas de requête de plus sur les pages de partie) ; `create`/`update` synchronisent la sélection.
Fixtures : chaque round applique les règles calculées et une règle libre de plus que le précédent.
Vérifié sur https://lethalbingo.localhost : sélection modifiée, enregistrée et retrouvée.

Règles affichées en pastilles Tabler (`form-selectgroup-pills`) avec leur coefficient ; format du coefficient partagé avec l'onglet Règles par la macro `coefficient`.
<!-- SECTION:NOTES:END -->
