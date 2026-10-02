---
id: TASK-34
title: Ajouter des captures d'écran dans le README pour mieux présenter l'application
status: To Do
assignee: []
created_date: '2026-10-02 23:06'
labels:
  - contenu
dependencies: []
priority: low
type: docs
ordinal: 38000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Le README ne montre aujourd'hui que la page d'accueil et le tableau de bord de l'arbitre, avec des données de démonstration en faux texte latin. Les écrans qui montrent l'intérêt du projet n'y apparaissent pas : la grille de bingo côté joueur et le tableau de bord de l'arbitre pendant une partie. Les visiteurs du dépôt ne peuvent pas lancer l'application facilement, les captures sont donc leur principal aperçu.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le README montre la grille de bingo côté joueur pendant une partie
- [ ] #2 Le README montre le tableau de bord de l'arbitre pendant une partie en cours
- [ ] #3 Les captures affichent des données crédibles, sans faux texte latin
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
