---
id: TASK-30
title: Préparer un script SQL des données de la saison 1 pour la mise en ligne
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-20T22:12:00Z'
labels:
  - preparation
dependencies: []
priority: medium
type: task
ordinal: 34000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les données de la saison 1 (bingos du fichier Excel, règles, équipes, parties du round 1) doivent être en production, mais l'app est générique : elles ne passent pas par les migrations. Un script SQL non versionné, lancé une fois à la mise en ligne, les insère sans ressaisie.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un script SQL non versionné insère les cases de chaque bingo du fichier Excel
- [x] #2 Le libellé, la catégorie et la position de chaque case correspondent au fichier Excel
- [x] #3 Le script s'applique en une transaction sur une base migrée sans altérer les autres données
- [x] #4 Aucune donnée de la saison 1 n'est dans une migration ni dans l'historique git
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
Script .claude/scripts/season-1.sql (non versionné), avec en tête la commande psql pour la prod : 4 bingos de la saison 1 (libellés et positions des captures des grilles, orthographe corrigée, catégories du classeur ou proposées), règles de chaque round (2 règles libres créées : « Nombre de jours consécutifs » 100, « Argent maximum ($) » 1), équipes A à D avec leurs membres, 4 parties terminées du round 1 (cases validées, cases dévoilées pour les équipes A et C, jours et cases dévoilées en valeurs de règles). À lancer une seule fois, après les migrations, sur une base sans saison 1.

Vérifié sur une base de test neuve migrée : 25 cases par round, 3 membres et 1 capitaine par équipe, points 2500, 2200, 3900, 3700, identiques à la vidéo.

Date réelle de début inconnue : 2024-02-20 20:00 est conservée (elle ne sert qu'à l'affichage et à l'historique, pas aux points).
<!-- SECTION:NOTES:END -->
