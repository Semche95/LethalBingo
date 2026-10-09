---
id: TASK-37
title: >-
  Restreindre la visibilité des équipes, bingos et parties à des groupes
  d'arbitres
status: To Do
assignee: []
created_date: '2026-10-09 21:10'
updated_date: '2026-10-09 21:51'
labels:
  - gestion-parties
  - dashboard-arbitre
  - comptes
dependencies: []
priority: medium
type: feature
ordinal: 41000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Aujourd'hui, tout arbitre voit et modifie toutes les équipes, tous les bingos et toutes les parties. Les arbitres doivent être répartis en groupes d'organisateurs : les arbitres d'un groupe préparent et arbitrent ensemble leurs propres bingos, et ne connaissent ni le contenu ni les arbitres des autres groupes. Un groupe peut ne compter qu'un seul arbitre.

Règles retenues :
- un arbitre appartient à un seul groupe ;
- une partie, son équipe et son bingo appartiennent toujours au même groupe ;
- le blocage du round suivant ne compte que les équipes du groupe ;
- seul l'administrateur, qui gère techniquement l'application, crée les groupes et y affecte les arbitres ;
- l'administrateur voit le contenu de tous les groupes.

**Renvois**
- TASK-28 : Empêcher le démarrage d'un round suivant tant que le round en cours n'est pas terminé par toutes les équipes
- TASK-38 : Ajouter une page d'administration des comptes
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Chaque arbitre appartient à un seul groupe
- [ ] #2 Chaque équipe, bingo et partie appartient à un groupe, et une partie ne peut relier qu'une équipe et un bingo de son groupe
- [ ] #3 Un arbitre ne voit et ne modifie que les équipes, bingos et parties de son groupe, et ne voit pas les arbitres des autres groupes
- [ ] #4 Le blocage du round suivant ne compte que les équipes du groupe
- [ ] #5 Seul l'administrateur crée les groupes et y affecte les arbitres
- [ ] #6 L'administrateur voit et modifie le contenu de tous les groupes
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
