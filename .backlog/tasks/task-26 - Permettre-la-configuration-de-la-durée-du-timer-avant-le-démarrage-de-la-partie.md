---
id: TASK-26
title: >-
  Permettre la configuration de la durée du timer avant le démarrage de la
  partie
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-12T21:06:00Z'
labels:
  - timer
dependencies: []
priority: medium
type: feature
ordinal: 30000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
La durée du timer est aujourd'hui fixée à une heure pour toutes les parties. L'arbitre doit pouvoir choisir la durée du timer de chaque partie avant son démarrage, ou s'en passer complètement pour une partie sans limite de temps.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La durée par défaut est de 1h30
- [x] #2 Le timer peut être complètement désactivé
- [x] #3 La durée du timer se règle à la création ou à la modification d'une partie, tant qu'elle n'a pas démarré
- [x] #4 Le timer de la partie démarre avec la durée choisie
- [x] #5 La durée se saisit dans deux champs séparés, heures et minutes (de 0 à 59)
- [x] #6 La durée maximale (99 h 59 min) est indiquée dans le formulaire
- [x] #7 Les durées habituelles (0h30, 1h, 1h30, 2h, 2h30, 3h) se sélectionnent en un clic
- [x] #8 Une partie terminée sans limite de temps affiche sa durée sur la page de la partie
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

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. Enum TimerMode (Countdown, Stopwatch) et champs de Game : timerMode, countdownDurationSeconds (5400), pausedAt, pausedDurationSeconds ; migration (parties démarrées 3600 s, autres 5400 s).
2. GameDto et GameFormType : case « Limiter le temps » et durée h:min (1:30 par défaut), conversion dans GameService.
3. GameTimerService : une branche par mode (lancement, pause, reprise, arrêt, isTimeUp, ajustement refusé en compteur).
4. GameTimer : libellé « Temps écoulé », pas d'ajustement ni d'alerte en compteur, pause et temps forts conservés ; durée de la partie pour les alertes du décompte.
5. Fixtures, docs/TIMER.md, tests PHPUnit et Vitest.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Décisions : sans limite de temps, le bloc affiche le temps écoulé depuis le lancement, pauses déduites, avec pause et temps forts conservés. Le compteur a ses propres colonnes (pausedAt, pausedDurationSeconds), jamais celles du décompte. Saisie de la durée au format h:min.

Fait : enum TimerMode et colonnes dédiées au compteur (migration Version20261112203921), case « Limiter le temps » et durée h:min dans le formulaire, branches par mode dans GameTimerService et game_timer_controller, section 8 de docs/TIMER.md. Vérifié dans le navigateur : erreur de format, passage en compteur, lancement, pause et reprise (pauses déduites), décompte de 1:30 avant lancement.

Heures seules (2 ou 2:) complétées par :00 à la sortie du champ (completeDurationInput dans assets/utils.ts, partagé avec l'ajustement du temps) et acceptées par GameDto ; vérifié dans le navigateur.

Saisie remplacée par deux champs entiers, heures (0 à 99) et minutes (0 à 59), sans conversion des minutes en heures ; maximum 99 h 59 min indiqué sous les champs ; boutons des durées habituelles (GameDto::COUNTDOWN_PRESETS) qui remplissent les champs et cochent la case. Le complément h:min est retiré. Vérifié dans le navigateur (préréglage 3h enregistré, durée nulle refusée).

Partie terminée sans limite de temps : « Durée de la partie » à la place du bloc du timer (GameTimerService::elapsedSeconds, GameFrame::getStopwatchDurationSeconds), visible par l'arbitre et les joueurs ; vérifié dans le navigateur (01:46:50 pour 2026-10-20 02:03:12 → 03:50:02).
<!-- SECTION:NOTES:END -->
