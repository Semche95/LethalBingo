---
id: TASK-37
title: 'Rattacher chaque équipe, bingo et partie à un ou plusieurs arbitres'
status: To Do
assignee: []
created_date: '2026-10-09 21:10'
labels:
  - gestion-parties
  - dashboard-arbitre
dependencies: []
priority: medium
type: feature
ordinal: 41000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Aujourd'hui, tout arbitre voit et modifie toutes les équipes, tous les bingos et toutes les parties. Chaque équipe, bingo et partie doit être rattaché à un arbitre, ou à plusieurs (choix à trancher avant l'implémentation), afin que chacun travaille sur ses propres éléments. Les administrateurs gardent l'accès à tout.

Points à trancher avant l'implémentation :
- un seul arbitre ou plusieurs par élément ;
- un bingo (round) est partagé par toutes les équipes qui le jouent : s'il appartient à un arbitre, il faut décider si les arbitres des autres équipes peuvent le consulter ou le modifier ;
- une partie relie une équipe et un bingo : il faut décider si son arbitre est hérité de l'équipe, du bingo, ou choisi indépendamment, pour éviter trois rattachements contradictoires ;
- l'interdiction de démarrer le round suivant tant que toutes les équipes n'ont pas terminé le round en cours concerne des parties d'arbitres différents : il faut décider qui voit cet état et qui peut débloquer.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le choix entre un ou plusieurs arbitres par élément est tranché et documenté dans une décision Backlog.md
- [ ] #2 Chaque équipe, bingo et partie est rattaché à son ou ses arbitres
- [ ] #3 Un arbitre ne voit et ne modifie dans le tableau de bord que les éléments qui lui sont rattachés, selon les règles de partage décidées pour les bingos
- [ ] #4 Un administrateur voit et modifie tous les éléments
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
