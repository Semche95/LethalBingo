---
id: TASK-24
title: Permettre d'ajouter ou d'enlever la durée voulue au timer
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-12T20:07:00Z'
labels:
  - timer
dependencies: []
priority: medium
type: feature
ordinal: 28000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pendant une partie, l'arbitre ne peut ajouter ou enlever du temps au timer que par pas d'une minute. Il doit pouvoir ajouter ou enlever la durée qu'il veut en une seule fois. Les boutons actuels qui règlent le timer sur des valeurs prédéfinies ne servent qu'au développement et ne s'affichent pas en production : ils ne remplacent pas cette saisie, qui doit être disponible à tout arbitre en production.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 L'arbitre peut saisir une durée et l'ajouter au timer
- [x] #2 L'arbitre peut saisir une durée et l'enlever du timer
- [x] #3 La saisie est disponible en production, pour tout arbitre
- [x] #4 Le temps restant est mis à jour en direct chez les joueurs
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

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Saisie unique « Ajuster le temps » (proposition B de .claude/maquettes/task-24-timer.html) qui remplace les boutons ±1 minute : champ min:s ou minutes seules (1:00 par défaut), parsé côté serveur dans GameTimer::parseDuration(), saisie invalide ou nulle signalée sans changer le temps. Panneau ouvert et fermé par game_timer_adjust_controller.ts (le JS des dropdowns Bootstrap n'est pas chargé). Vérifié dans le navigateur : ajout, retrait, erreur, Échap, mise à jour en direct côté joueur.
<!-- SECTION:NOTES:END -->
