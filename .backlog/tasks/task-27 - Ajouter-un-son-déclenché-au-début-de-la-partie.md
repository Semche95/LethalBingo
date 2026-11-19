---
id: TASK-27
title: Ajouter un son déclenché au début de la partie
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-19T18:26:00Z'
labels:
  - timer
dependencies: []
priority: medium
type: feature
ordinal: 31000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Des sons signalent déjà les paliers du timer et la fin de la partie, mais rien ne marque son début. Un son doit être joué au démarrage de la partie pour prévenir les joueurs que le temps commence à s'écouler.

- Le son de départ est joué en mode compte à rebours comme en mode chronomètre.
- Le chronomètre ne reçoit que le son de départ : ni son de palier, ni son de fin.
- Le son de départ est un nouveau fichier, créé dans l'esprit des deux sons existants.
- Le son de départ n'est jamais joué en retard : une page qui découvre le lancement après coup (sortie de veille) reste silencieuse.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un son est joué sur la page des joueurs au démarrage de la partie
- [x] #2 Le son n'est pas rejoué au rechargement de la page
- [x] #3 Le son suit le même réglage d'activation que les autres alertes du timer
- [x] #4 Le son de départ est joué aussi en mode chronomètre, qui affiche donc lui aussi le bouton d'activation du son
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
Son de départ : assets/sounds/start.mp3 (1 s, deux bips graves à 787 Hz puis un aigu à 1575 Hz, timbre de interval.mp3), généré par synthèse. game_timer_controller.ts joue 'start' dans gameStartedAtValueChanged() quand startedAt passe de vide à une date, si le lancement date de moins de 2 s (max du serverTime du rendu et de l'heure serveur estimée). Les valeurs initiales sont lues avant que connect() crée le son : un chargement de page reste silencieux. Bouton d'activation du son affiché aussi en mode chronomètre (GameTimer.html.twig). Vérifié sur https://lethalbingo.localhost : son joué une fois au lancement en compte à rebours et en chronomètre, rien au rechargement.
<!-- SECTION:NOTES:END -->
