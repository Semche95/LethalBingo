---
id: TASK-40
title: >-
  Ne pas proposer un round déjà joué par l'équipe choisie à la création d'une
  partie
status: Done
assignee: []
created_date: 2026-10-10 01:53
updated_date: '2026-11-19T22:01:00Z'
labels:
  - gestion-parties
dependencies: []
priority: medium
type: enhancement
ordinal: 44000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Dans le formulaire de création d'une partie, la liste des rounds propose aujourd'hui tous les rounds, y compris ceux que l'équipe choisie a déjà joués. Une même équipe ne doit pas pouvoir rejouer le même round : une fois l'équipe choisie, seuls les rounds pour lesquels elle n'a pas encore de partie doivent être proposés. Un round déjà joué par une équipe reste disponible pour les autres équipes.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Une fois l'équipe choisie, la liste des rounds ne propose que les rounds pour lesquels cette équipe n'a pas encore de partie
- [x] #2 Un round déjà joué par une équipe reste proposé aux autres équipes
- [x] #3 L'enregistrement d'une partie pour un couple équipe et round qui a déjà une partie est refusé avec un message d'erreur
- [x] #4 À la modification d'une partie, le round déjà associé à cette partie reste proposé
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
Contrainte de classe `UniqueTeamRound` sur `GameDto` (nouveau champ `game` = partie modifiée) : le `UniqueEntity` de `Game` n'était jamais vérifié par le formulaire, un doublon finissait en erreur 500. `TeamRepository::findPlayedRoundIdsIndexedById()` (regroupement et indexation par équipe en DQL) alimente le contrôleur Stimulus `referee--game-form`, qui grise les rounds déjà joués par l'équipe choisie et désélectionne le round choisi s'il en fait partie.
<!-- SECTION:NOTES:END -->
