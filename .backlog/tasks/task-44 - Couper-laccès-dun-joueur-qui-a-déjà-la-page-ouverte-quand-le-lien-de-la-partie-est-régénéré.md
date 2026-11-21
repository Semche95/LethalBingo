---
id: TASK-44
title: >-
  Couper l'accès d'un joueur qui a déjà la page ouverte quand le lien de la
  partie est régénéré
status: To Do
assignee: []
created_date: '2026-11-21T10:24:00Z'
labels:
  - gestion-parties
dependencies: []
priority: medium
type: bug
ordinal: 48000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Régénérer le lien unique d'une partie sert à exclure quelqu'un qui ne doit plus la suivre. Aujourd'hui, un joueur qui a gardé la page ouverte n'est pas exclu : au prochain rafraîchissement de la page (réveil de la machine, reconnexion réseau), il reçoit le nouveau lien et continue de suivre la partie. Les cases cachées restent masquées pour lui : ce n'est pas une fuite de contenu, c'est la révocation qui ne fonctionne pas.

**Renvois**
- TASK-6 : Ajouter un bouton permettant de régénérer le lien unique d'une partie
- TASK-31 : Permettre l'affichage des cases visibles du bingo avant le début de la partie
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Après la régénération du lien, une page joueur ouverte avec l'ancien lien ne reçoit plus aucune mise à jour de la partie (cases, timer, titre)
- [ ] #2 Une page joueur ouverte avec l'ancien lien ne peut plus obtenir le nouveau lien
- [ ] #3 L'arbitre connecté continue de suivre la partie normalement après la régénération
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Toute nouvelle classe de `src/Service/`, `src/Controller/` ou `src/Repository/` est accompagnée de son test
- [ ] #2 Tout changement d'entité est accompagné de sa migration et des fixtures mises à jour
- [ ] #3 Les docs concernées par le changement (`docs/`, `AGENTS.md`, `.claude/`) sont mises à jour
- [ ] #4 `just test` passe
- [ ] #5 `just sf lint:container` passe
- [ ] #6 `just phpstan` passe
- [ ] #7 `just cs` passe
- [ ] #8 `just rector` passe
<!-- DOD:END -->
