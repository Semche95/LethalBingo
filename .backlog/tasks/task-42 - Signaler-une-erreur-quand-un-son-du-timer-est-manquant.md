---
id: TASK-42
title: Signaler une erreur quand un son du timer est manquant
status: Done
assignee: []
created_date: '2026-11-05T14:49:00Z'
updated_date: '2026-11-21T09:52:00Z'
labels:
  - timer
dependencies: []
priority: low
type: bug
ordinal: 46000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quand un son d'alerte du timer (bip des seuils ou son de fin) est introuvable ou illisible, le timer l'ignore aujourd'hui sans rien signaler : l'absence de son passe inaperçue jusqu'au jour de la partie. La spécification du timer exige qu'un son manquant déclenche une erreur, sans pour autant interrompre le timer ni la partie, le son n'étant pas critique.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Un son d'alerte introuvable ou illisible déclenche une erreur dans la console du navigateur au lieu d'être ignoré en silence, sans aucun message affiché à l'arbitre ni aux joueurs
- [x] #2 Malgré cette erreur, le décompte, la couleur des seuils et les autres sons continuent de fonctionner
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
Comportement attendu
- Au chargement du timer (connect() du contrôleur), chaque son (start.mp3, interval.mp3, end.mp3) est téléchargé et décodé comme aujourd'hui. S'il est introuvable (réponse HTTP non OK, réseau) ou illisible (décodage refusé), une erreur est écrite dans la console du navigateur dès le chargement de la page, une par fichier, avec son URL et la cause.
- Erreur purement technique : aucun toast ni message affiché à l'arbitre ou aux joueurs.
- Le son manquant reste sauté à la lecture (play() et schedule() le sautent déjà) : le décompte, la couleur des seuils et les autres sons continuent.

Contrats
- TimerSound.load() (assets/controllers/game/game_timer_sound.ts) : lève une erreur si response.ok est faux (avant, un 404 n'échouait qu'au décodage du corps HTML) ; le catch appelle console.error(`Timer sound missing or unreadable: <url>`, cause) puis renvoie null comme avant. Aucune exception propagée.
- console.error directement dans TimerSound : c'est lui qui charge les sons, pas de nouveau paramètre ni de callback ; testable par un spy sur console.error.
- game_timer_controller.ts inchangé.

Fichiers modifiés
- assets/controllers/game/game_timer_sound.ts
- assets/tests/game_timer_sound.test.ts, assets/tests/game_timer_controller_sound.test.ts (stubs de fetch avec ok: true)
- docs/TIMER.md

Tests (Vitest)
- game_timer_sound.test.ts : son injoignable ou 404 → console.error appelé une fois avec l'URL ; 404 → pas de décodage ; décodage refusé → console.error une fois ; un son en échec n'empêche ni de jouer ni de programmer les autres ; aucun appel quand tout se charge.
- game_timer_controller_sound.test.ts : end.mp3 en 404 → une seule erreur console nommant le fichier, aucun toast, le décompte passe à 00:04:59 avec sa couleur et le bip interval.mp3 est joué.
- Puis la skill lethalbingo-checks.

Docs
- docs/TIMER.md section 5 « Son manquant » (erreur en console au chargement, rien à l'écran), section 6 (scénario de vérification : URL de son bloquée), section 7 (récapitulatif).

Vérification visuelle
- Sur https://lethalbingo.localhost, page joueur et page arbitre, bloquer une URL de son (Playwright route, en 404) : une erreur console, aucun message à l'écran, timer et autres sons intacts.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conception rédigée dans le plan, implémentation en attente de trois décisions de l'utilisateur :
1. Affichage de l'erreur : toast d'erreur existant (se ferme seul après 4 s) ou message fixe dans le panneau du timer ?
2. Qui voit l'erreur : joueurs aussi, ou seulement l'arbitre et l'administrateur ?
3. Écrire aussi l'erreur dans la console du navigateur (console.error) ?

Décisions de l'utilisateur : l'erreur est purement technique, écrite uniquement dans la console du navigateur (console.error) ; aucun message affiché à l'arbitre ni aux joueurs, qui ne pourraient rien y faire.

Vérifié dans le navigateur : interval.mp3 en 404 sur la page joueur donne une seule erreur console nommant le fichier, aucun toast, timer affiché. Vérificateur indépendant : conforme, 6 vérifications OK.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Un son du timer introuvable ou illisible écrit une erreur dans la console du navigateur au chargement (une par fichier), sans rien afficher ; le son est sauté et le timer continue.
<!-- SECTION:FINAL_SUMMARY:END -->
