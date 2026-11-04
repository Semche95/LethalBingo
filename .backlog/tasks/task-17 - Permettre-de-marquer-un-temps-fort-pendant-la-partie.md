---
id: TASK-17
title: Permettre de marquer un temps fort pendant la partie
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - historique
dependencies: []
priority: medium
type: feature
ordinal: 21000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pendant une partie, l'arbitre n'a aucun moyen de signaler un moment marquant (action spectaculaire, retournement, fou rire) pour le retrouver plus tard. Il doit pouvoir marquer un temps fort d'un simple clic au moment où il se produit, afin que ces moments soient datés et puissent être affichés dans l'historique de la partie.

**Renvois**
- TASK-19 : Afficher l'historique des actions à droite du bingo avec les temps forts
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 L'arbitre peut marquer un temps fort pendant une partie en cours
- [ ] #2 Chaque temps fort conserve l'heure à laquelle il a été marqué
- [ ] #3 Les joueurs ne peuvent pas marquer de temps fort
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
