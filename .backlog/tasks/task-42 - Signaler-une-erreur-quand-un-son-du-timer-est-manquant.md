---
id: TASK-42
title: Signaler une erreur quand un son du timer est manquant
status: In Progress
assignee: []
created_date: '2026-11-05T14:49:00Z'
updated_date: '2026-11-20T22:26:00Z'
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
- [ ] #1 Un son d'alerte introuvable ou illisible déclenche une erreur visible au lieu d'être ignoré en silence
- [ ] #2 Malgré cette erreur, le décompte, la couleur des seuils et les autres sons continuent de fonctionner
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

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Comportement attendu
- Au chargement du timer (connect() du contrôleur), chaque son (start.mp3, interval.mp3, end.mp3) est téléchargé et décodé comme aujourd'hui. S'il est introuvable (réponse HTTP non OK, réseau) ou illisible (décodage refusé), une erreur visible est affichée dès le chargement de la page, pas au moment où le son devrait jouer : l'absence se voit avant le jour de la partie.
- Une erreur par fichier en échec, avec le nom du fichier.
- Le son manquant reste ignoré à la lecture (play() et schedule() le sautent déjà) : le décompte, la couleur des seuils et les autres sons continuent.

Contrats
- TimerSound (assets/controllers/game/game_timer_sound.ts) : le constructeur reçoit un second paramètre onLoadError: (url: string) => void, appelé une fois par son dont le chargement échoue. Pas d'exception propagée : load() continue de renvoyer null.
- load() : lève une erreur si response.ok est faux (aujourd'hui un 404 n'échoue qu'au décodage du corps HTML), puis le catch appelle onLoadError(url) au lieu d'ignorer en silence.
- game_timer_controller.ts, connect() : passe un callback qui appelle showToast(message, 'error') (assets/utils.ts) avec le nom du fichier, par ex. « Son introuvable ou illisible : interval.mp3. Les alertes sonores correspondantes ne seront pas jouées. »

Réutilisé
- showToast() de assets/utils.ts et le type de toast error déjà stylé (assets/styles/components/toasts.css), le conteneur .toast-container est inclus dans base.html.twig, donc présent sur la page joueur comme sur la page arbitre.
- Aucun nouveau fichier, aucun nouveau style.

Fichiers modifiés
- assets/controllers/game/game_timer_sound.ts
- assets/controllers/game/game_timer_controller.ts
- assets/tests/game_timer_sound.test.ts, assets/tests/game_timer_controller_sound.test.ts
- docs/TIMER.md

Tests (Vitest)
- game_timer_sound.test.ts : le test « keeps working silently when a sound cannot be loaded » devient : fetch rejeté → onLoadError appelé avec l'URL, play() résout sans jouer ; nouveau cas réponse 404 (ok: false) → onLoadError appelé, decodeAudioData pas appelé ; nouveau cas décodage refusé → onLoadError appelé ; un son en échec n'empêche pas de jouer ni de programmer les autres ; aucun appel quand tout se charge.
- game_timer_controller_sound.test.ts : un son en échec affiche un toast error nommant le fichier dans .toast-container, et le décompte et les autres sons continuent.
- Puis vitest, et les 5 vérifications du projet.

Docs
- docs/TIMER.md section 5 « Son manquant » : préciser comment l'erreur est signalée (toast d'erreur au chargement, un par fichier) ; section 6 : ajouter le scénario de vérification (fichier son renommé ou URL bloquée → toast, timer intact) ; section 7 récapitulatif si besoin.

Vérification visuelle
- Sur https://lethalbingo.localhost, page joueur et page arbitre, bloquer une URL de son (Playwright route) : toast d'erreur affiché, timer et autres sons intacts.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conception rédigée dans le plan, implémentation en attente de trois décisions de l'utilisateur :
1. Affichage de l'erreur : toast d'erreur existant (se ferme seul après 4 s) ou message fixe dans le panneau du timer ?
2. Qui voit l'erreur : joueurs aussi, ou seulement l'arbitre et l'administrateur ?
3. Écrire aussi l'erreur dans la console du navigateur (console.error) ?
<!-- SECTION:NOTES:END -->
