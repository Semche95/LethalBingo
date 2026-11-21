---
id: TASK-29
title: >-
  Interdire la modification d'un round ou d'une équipe si une partie associée
  est en cours ou terminée
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-21T09:17:00Z'
labels:
  - rounds
dependencies: []
priority: medium
type: feature
ordinal: 33000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Modifier un round ou une équipe change aussi les parties qui y sont liées, y compris celles déjà jouées ou en cours, ce qui fausse leur déroulé et leur résultat. Un round ou une équipe ne doit plus être modifiable dès qu'une partie associée est en cours ou terminée. La modification reste autorisée tant que ses parties associées n'ont pas démarré.

**Renvois**
- TASK-43 : Archiver toutes les informations liées à une partie terminée
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un round associé à une partie en cours ou terminée ne peut plus être modifié
- [x] #2 Une équipe associée à une partie en cours ou terminée ne peut plus être modifiée
- [x] #3 Un round ou une équipe dont les parties associées n'ont pas démarré reste modifiable
- [x] #4 L'arbitre voit pourquoi la modification est refusée
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
Règle
- Un round (GameRound, fiche « bingo ») ou une équipe (Team, membres compris) est verrouillé dès qu'une de ses parties a isReadOnly() (startedAt ou finishedAt renseigné), comme le formulaire de partie. Une partie réinitialisée (GameService::reset remet startedAt à null) redevient non démarrée et ne verrouille plus rien.
- Suppression hors périmètre : BingoController::delete et TeamController::delete la refusent déjà dès qu'une partie existe, même non démarrée.
- Règles de scoring (Rule) hors périmètre : leur coefficient s'applique aux parties jouées (docs/GAME.md), relève de TASK-43.

Contrat
- Trait src/Entity/HasStartedGameTrait.php, utilisé par Team et GameRound (tous deux ont $games) : hasStartedGame(): bool = $this->games->exists(fn ($k, Game $g) => $g->isReadOnly()). Pas de mapping modifié : ni migration ni fixtures.

Points d'entrée verrouillés côté serveur
- Round : BingoController::handleForm, calqué sur GameController::handleForm : $locked = $gameRound?->hasStartedGame() ?? false ; createForm(..., ['disabled' => $locked]) (Symfony ignore alors les valeurs soumises) et enregistrement seulement si !$locked. Couvre numéro, saison, règles (pills) et cases.
- Équipe : TeamForm (Live Component, endpoint public) : instantiateForm avec ['disabled' => locked] ; save, addCollectionItem et removeCollectionItem lèvent BadRequestHttpException si $this->team?->hasStartedGame() (même réponse que GameController::delete sur une partie démarrée).
- Aucun autre chemin ne modifie un round ou une équipe (createEmptyRound et fillSeasonGaps créent des rounds, GameService::update ne touche que la partie).

Affichage (AC #4), réutilisations
- Alerte info icône lock (composant Alert, comme referee/game/form.html.twig) en tête de formulaire : « Ce bingo est utilisé par une partie en cours ou terminée : il ne peut plus être modifié. » / « Cette équipe a une partie en cours ou terminée : elle ne peut plus être modifiée. »
- Champs désactivés : <fieldset disabled> autour du corps du formulaire, qui couvre aussi les champs écrits à la main (cases de BingoCard en editMode, sélecteur de catégorie, bouton capitaine, ajout/suppression de membre).
- FormFooterActions :canSave="not locked" (bouton Enregistrer retiré, comme pour une partie démarrée) ; le bouton Supprimer garde son état actuel.
- Fichiers : templates/referee/bingo/form.html.twig, templates/components/TeamForm.html.twig.

Tests PHPUnit
- tests/Entity/TeamTest.php et GameRoundTest.php : hasStartedGame faux sans partie et avec une partie non démarrée, vrai en cours et terminée.
- tests/Controller/Referee/BingoControllerTest.php : édition refusée (round inchangé, alerte affichée) avec une partie démarrée ; édition acceptée avec une partie non démarrée.
- tests/Twig/TeamFormTest.php (InteractsWithLiveComponents) : save refusé sur équipe verrouillée, équipe inchangée ; save accepté sinon.

Docs
- docs/GAME.md : section 2 (verrouillage du bingo et de l'équipe dès le démarrage, levé par la réinitialisation) et « Garanties côté serveur » (TeamForm::save et BingoController).

Vérifications : just test, lint:container, phpstan, cs, rector ; vérification visuelle sur https://lethalbingo.localhost (fiche bingo et modale équipe, verrouillées et non verrouillées).

Ajouts après relecture
- TeamForm : #[IsGranted('ROLE_REFEREE')] aussi sur addCollectionItem et removeCollectionItem ; le garde de verrouillage passe avant tout accès à formValues/getForm().
- Trait : @property Collection<int, Game> $games pour PHPStan ; hasStartedGame() n'est jamais appelé dans les listes (N+1).
- POST forgé sur un bingo verrouillé : réponse 200 sans redirection, alerte affichée, base inchangée.
- Tests en plus : TeamFormTest couvre addCollectionItem et removeCollectionItem (400 verrouillée, OK sinon) ; BingoControllerTest avec un POST forgé complet (numéro, saison, règles, case) sur round verrouillé ; déverrouillage après GameService::reset.
- docs/GAME.md : le temps à zéro (partie toujours en cours) verrouille aussi.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Les règles de scoring (coefficient modifié, suppression via RuleController) restent modifiables et changent encore les parties jouées : renvoyé à TASK-43.

Vérifié à l'écran : bingo 17 et équipe Ministère du quota verrouillés (alerte cadenas, champs désactivés, pas de bouton Enregistrer). Vérificateur indépendant : conforme, 6 vérifications OK.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Un bingo ou une équipe liés à une partie démarrée ou terminée ne sont plus modifiables, côté serveur (formulaire désactivé, actions Live refusées) et à l'écran (alerte cadenas) ; la réinitialisation lève le verrou. Les règles de scoring restent modifiables (TASK-43).
<!-- SECTION:FINAL_SUMMARY:END -->
