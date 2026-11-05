---
id: TASK-19
title: Afficher l'historique des actions à droite du bingo avec les temps forts
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-05T19:29:00Z'
labels:
  - historique
dependencies:
  - TASK-17
  - TASK-18
priority: medium
type: feature
ordinal: 23000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
L'arbitre ne voit pas le déroulé d'une partie : quelles cases ont été dévoilées, masquées, validées ou invalidées, et quand. Un historique affiché à droite du bingo, réservé à l'arbitre, doit lister ces actions dans l'ordre chronologique, avec les temps forts marqués pendant la partie, pour suivre la partie en direct puis la revoir une fois terminée.

D'après la maquette validée, l'historique est un panneau fixé dans la marge droite de la page, à la façon des commentaires d'un document : il ne prend aucune largeur à la grille du bingo. Sur un écran trop étroit pour le garder à côté de la grille, il est replié et s'ouvre à la demande depuis un bouton « Historique ».

Les éléments vont du plus ancien au plus récent, le plus récent en bas. Chaque ligne donne le temps écoulé depuis le lancement, avec l'heure en dessous, une icône dont la couleur dépend du type d'action, la case (coordonnée et intitulé) et l'action (dévoilée, masquée, validée ou invalidée). Les temps forts ressortent sur un fond jaune et affichent leur note, ou « Sans note ».

Avant le lancement, l'arbitre peut déjà dévoiler et masquer des cases. Ces actions n'ont pas de temps écoulé : seule leur heure s'affiche, en gris. Une fois la partie lancée, un séparateur « Lancement de la partie » suivi de l'heure de lancement les sépare des actions suivantes.

L'en-tête du panneau indique le nombre d'éléments et l'état de la partie : « Partie non démarrée » (point gris), « Partie en cours » (point vert animé) ou « Partie terminée » (sans point). Sans aucune action, le panneau affiche un message qui explique ce qui y apparaîtra.

**Renvois**
- TASK-17 : Permettre de marquer un temps fort pendant la partie
- TASK-18 : Conserver la date de chaque action effectuée sur une case
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 L'historique est un panneau fixé à droite de la page qui ne réduit pas la largeur de la grille du bingo
- [x] #2 Sur un écran trop étroit, le panneau est replié et s'ouvre et se ferme depuis un bouton « Historique »
- [x] #3 Chaque action sur une case y figure avec sa date, la case concernée (coordonnée et intitulé) et le type d'action (dévoilée, masquée, validée, invalidée), avec une icône de couleur propre à chaque type
- [x] #4 Les éléments vont du plus ancien au plus récent, le plus récent en bas et visible sans défiler
- [x] #5 Chaque élément postérieur au lancement affiche le temps écoulé depuis le lancement (pauses et temps passé à zéro compris) avec l'heure en dessous, et la date complète au survol
- [x] #6 Les actions faites avant le lancement n'affichent que leur heure, en gris, et un séparateur « Lancement de la partie » avec l'heure de lancement les sépare des actions suivantes
- [x] #7 Les temps forts y figurent à leur place chronologique, mis en évidence, avec leur note ou « Sans note »
- [x] #8 L'en-tête affiche le nombre d'éléments et l'état de la partie : « Partie non démarrée », « Partie en cours » ou « Partie terminée »
- [x] #9 Sans aucune action, le panneau affiche un message qui explique ce qui y apparaîtra
- [x] #10 L'historique se met à jour en direct pendant la partie
- [x] #11 L'historique reste consultable une fois la partie terminée
- [x] #12 L'historique n'est visible que de l'arbitre et n'est jamais transmis aux joueurs
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
Fait :
- Composant Live `GameHistory` (templates/components/GameHistory.html.twig), affiché dans `GameFrame` seulement pour ROLE_REFEREE ; son action par défaut exige ROLE_REFEREE, donc aucun re-rendu pour un joueur.
- Actions lues par `GameSquareActionRepository::findByGameWithSquare` (une requête, case jointe), temps forts par `game.highlights` ; fusion et tri chronologique dans le template.
- Rafraîchi en direct par Mercure sur `gameFrameRefresh`, `bingoCardRefresh` (actions sur les cases, lancement, arrêt, réinitialisation) et le nouvel événement `gameHistoryRefresh`, publié par `GameHighlightController` à l'ajout, à la note et à la suppression d'un temps fort.
- Contrôleur Stimulus `game--game-history` : tiroir sous 1500px, défilement sur l'élément le plus récent, surbrillance des éléments arrivés en direct.
- CSS de la maquette dans assets/app.css ; macro `elapsed` partagée avec la liste des temps forts.
- Les actions avant lancement (dévoiler, masquer) étaient déjà enregistrées par `GameSquareService`.
- Tests : GameSquareActionRepositoryTest, GameHistoryTest (ordre, séparateur, états, état vide, refus pour un joueur), nombre de requêtes du re-rendu ; limite de requêtes de la page arbitre passée de 8 à 9.

Écart avec la maquette : la maquette n'a pas les boutons fixes « Éditer la partie » et « Accueil » en haut à droite. Au-dessus de 1500px, le panneau les recouvrait : ils sont décalés à gauche du panneau (classe `game-top-actions-beside-history`).

Vérifié avec Playwright (yippee) à 1600px et 1280px : états vide, non démarrée, en cours et terminée, mise à jour en direct, tiroir ouvert et fermé. Page joueur sans session : aucune trace de l'historique.

Reste à vérifier par l'utilisateur : rendu et comportement validés par rapport à la maquette, et le décalage des boutons du haut au-dessus de 1500px.

Décalage des boutons du haut à gauche du panneau validé par l'utilisateur.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Panneau d'historique réservé à l'arbitre, à droite du bingo (tiroir sous 1500px) : actions sur les cases et temps forts dans l'ordre chronologique, séparateur de lancement, temps écoulé depuis le lancement, mise à jour en direct par Mercure, jamais transmis aux joueurs.
<!-- SECTION:FINAL_SUMMARY:END -->
