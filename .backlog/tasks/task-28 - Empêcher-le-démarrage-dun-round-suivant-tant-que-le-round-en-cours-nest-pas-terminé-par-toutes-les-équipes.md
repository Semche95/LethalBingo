---
id: TASK-28
title: >-
  Avertir l'arbitre quand une équipe démarre un round sans avoir terminé le
  précédent
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-20T22:39:00Z'
labels:
  - rounds
dependencies: []
priority: medium
type: feature
ordinal: 32000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Chaque équipe doit jouer tous les rounds dans l'ordre, mais rien ne signale aujourd'hui qu'une partie va démarrer alors que l'équipe n'a pas terminé sa partie du round précédent. L'arbitre doit en être averti au moment du démarrage, sans être bloqué : il peut quand même démarrer la partie. L'avancement des autres équipes n'entre pas en compte : une équipe peut être au round 4 pendant qu'une autre est au round 1.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Au démarrage d'une partie dont l'équipe n'a pas terminé sa partie du round précédent, l'arbitre voit un avertissement qui explique pourquoi
- [x] #2 Malgré l'avertissement, l'arbitre peut démarrer la partie
- [x] #3 L'avancement des autres équipes ne déclenche jamais d'avertissement
- [x] #4 Le premier round ne déclenche jamais d'avertissement
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

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Conception (à valider avant tout code)

Règle
- Round précédent : le round de la même saison dont le numéro vaut celui du round de la partie moins 1. Les numéros d'une saison sont continus (BingoService::fillSeasonGaps comble les trous), il existe donc toujours pour un numéro > 1.
- Le round 1 d'une saison ne déclenche jamais d'avertissement, y compris en saison 2 (le dernier round de la saison précédente n'est pas regardé).
- Avertissement si l'équipe de la partie n'a pas de partie terminée (finishedAt renseigné) pour le round précédent : aucune partie créée, partie non démarrée ou partie en cours. Seule l'équipe de la partie compte, jamais les autres équipes.
- L'avertissement ne bloque jamais : GameFrame::startGame et GameTimerService::start ne changent pas.

Affichage (arbitre seulement, partie non démarrée)
- Sans avertissement : le bouton « Démarrer la partie » démarre directement, comme aujourd'hui.
- Avec avertissement : le même bouton ouvre une modale ConfirmModal start-game-modal (status primary, confirmLabel « Démarrer la partie », confirmIcon player-play, liveAction startGame), dont le texte explique la raison : « L'équipe X n'a pas terminé sa partie du round N : aucune partie créée / partie non démarrée / partie en cours. Démarrer quand même ? ».
- Côté joueur : rien ne change, rien n'est calculé ni envoyé.

Contrats
- Enum App\Entity\PreviousRoundStatus (string, comme TimerMode) : NoGame, NotStarted, InProgress.
- GameRepository::findPreviousRoundGame(Game $game): ?Game : partie de la même équipe pour le round (saison du round, numéro - 1), null si aucune. DQL, une requête, jointure sur g.round.
- GameService::previousRoundStatus(Game $game): ?PreviousRoundStatus : null si le round est le 1 (sans requête) ou si la partie précédente est terminée ; sinon NoGame, NotStarted (startedAt nul) ou InProgress. GameRepository injecté dans GameService.
- GameFrame::getPreviousRoundStatus(): ?PreviousRoundStatus, lu en computed dans le template : null sans requête si la partie est démarrée ou si l'utilisateur n'a pas ROLE_REFEREE.

Fichiers
- src/Entity/PreviousRoundStatus.php (nouveau, pas de changement de schéma, donc ni migration ni fixtures)
- src/Repository/GameRepository.php, src/Service/GameService.php, src/Twig/Components/GameFrame.php
- templates/components/GameFrame.html.twig : bouton Démarrer conditionnel + modale

Réutilisations
- ConfirmModal (modale, bouton d'action Live), modèle de stop-game-modal ; jointure team/round déjà utilisée dans GameRepository ; helpers createRound, createGame, createTeam de ControllerTestCase.

Tests (PHPUnit)
- tests/Repository/GameRepositoryTest.php (nouveau) : trouve la partie de la même équipe au round N-1 de la même saison ; ignore celle d'une autre équipe, d'une autre saison, d'un autre round.
- tests/Service/GameServiceTest.php : round 1 -> null ; précédente terminée -> null ; aucune partie -> NoGame ; non démarrée -> NotStarted ; en cours (y compris en pause ou à zéro) -> InProgress ; une autre équipe en retard ou en avance ne change rien.
- tests/Twig/GameFrameTest.php : arbitre avec avertissement -> bouton ciblant #start-game-modal et texte de la raison ; sans avertissement -> bouton live startGame direct, pas de modale ; joueur -> ni modale ni texte ; startGame démarre la partie même avec avertissement.
- tests/Twig/GameComponentsQueryCountTest.php : vérifier que les plafonds tiennent (une requête de plus seulement pour l'arbitre avant le démarrage).

Docs
- docs/GAME.md section 2 (Démarrer : confirmation seulement en cas d'avertissement, règle du round précédent), section 1 (garanties : pas de blocage), section 8 (tests, GameRepositoryTest).

Corrections après relecture
- Unicité (saison, numéro) non garantie par le schéma : findPreviousRoundGame devient findPreviousRoundGames(Game): list<Game> (toutes les parties de l'équipe au numéro N-1 de la saison) ; avertir seulement si aucune n'est terminée (statut tiré de la plus avancée : en cours, sinon non démarrée, sinon aucune partie).
- Test de nombre de requêtes du rendu GameFrame (arbitre, partie non démarrée), plafond incluant la requête ajoutée ; côté joueur, aucune requête ajoutée.
- Template : computed.previousRoundStatus ; numéro du round précédent (round.number - 1) dans le texte de la modale.
- Tests en plus : partie réinitialisée (startedAt nul) -> NotStarted ; saison 2 round 1 -> null.
- docs/GAME.md : corriger « sans confirmation » (section 2), plus sections 1 et 8.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Vérifié à l'écran (partie Service des pertes acceptables, round 2, round 1 non démarré) : la modale nomme l'équipe, le round précédent et la raison, et permet de démarrer quand même. Vérificateur indépendant : conforme, 6 vérifications OK.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Avertissement non bloquant au démarrage, dans une ConfirmModal affichée seulement si l'équipe n'a terminé aucune partie du round précédent de la saison ; démarrage direct sinon.
<!-- SECTION:FINAL_SUMMARY:END -->
