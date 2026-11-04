---
id: TASK-17
title: Permettre de marquer un temps fort pendant la partie
status: Done
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T20:59:00Z'
labels:
  - historique
dependencies: []
priority: medium
type: feature
ordinal: 21000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pendant une partie, l'arbitre n'a aucun moyen de signaler un moment marquant (action spectaculaire, retournement, fou rire) pour le retrouver plus tard. Il doit pouvoir marquer un temps fort au moment où il se produit, y compris pendant une pause, y ajouter une note facultative après coup, supprimer un temps fort marqué par erreur, et retrouver pendant la partie la liste des temps forts déjà marqués. Ces moments servent à monter la vidéo finale à la fin de la partie : ils doivent être datés à la seconde, en heure réelle et en temps écoulé depuis le lancement de la partie, sans déduire les pauses puisque l'enregistrement vidéo continue pendant les pauses. Ils sont aussi affichés dans l'historique de la partie.

Dans la barre du minuteur, après le bouton pause, deux boutons icônes séparent les deux usages : un bouton signet marque le temps fort en un clic et ouvre aussitôt la liste des temps forts, le curseur placé dans la note du nouveau temps fort pour l'écrire sans clic de plus, et un bouton liste, qui affiche le nombre de temps forts, ouvre cette même liste. Le clic en dehors qui ferme la liste agit aussi sur ce qu'il vise, pour ne pas coûter un clic de plus quand on ne veut pas de note. Dans cette liste, on ajoute ou modifie la note de chacun, enregistrée pendant la frappe comme les notes de la partie, et on peut supprimer un temps fort, après une confirmation affichée dans sa ligne, sans fenêtre modale ni bulle. Tant qu'aucun temps fort n'existe, le bouton liste est désactivé et son infobulle l'indique. Une fois la partie terminée, la liste reste consultable sans être modifiable. La réinitialisation de la partie supprime ses temps forts, comme le reste de ses actions. La fonctionnalité est réservée à l'arbitre : les joueurs ne voient que les cases du bingo, en lecture seule.

**Renvois**
- TASK-19 : Afficher l'historique des actions à droite du bingo avec les temps forts
- TASK-41 : Permettre de modifier les temps forts après la fin de la partie
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 L'arbitre peut marquer un temps fort pendant une partie en cours, y compris pendant une pause
- [x] #2 Chaque temps fort conserve, à la seconde, l'heure réelle et le temps écoulé depuis le lancement de la partie, sans déduire les pauses, pris au clic sur le bouton signet
- [x] #3 Les temps forts sont réservés à l'arbitre : les joueurs ne peuvent ni en marquer ni les voir
- [x] #4 Un bouton icône signet, placé après le bouton pause dans la barre du minuteur, marque le temps fort en un clic et ouvre aussitôt le menu des temps forts, le curseur placé dans le champ de note du temps fort qui vient d'être marqué
- [x] #5 Un bouton icône liste, placé après le bouton signet, affiche une pastille avec le nombre de temps forts de la partie
- [x] #6 Les boutons signet et liste affichent immédiatement, sans délai, leur infobulle : « Marquer un temps fort » et « Temps forts »
- [x] #7 Après un marquage, la pastille est mise à jour et le nouveau temps fort apparaît en tête de la liste ouverte, avec son temps écoulé, sans toast
- [x] #8 Le bouton liste ouvre un menu, fermé par un clic en dehors ou par Échap, qui liste les temps forts de la partie du plus récent au plus ancien, avec leur temps écoulé
- [x] #9 Le clic en dehors qui ferme le menu des temps forts agit aussi sur l'élément visé (par exemple une case du bingo ou le bouton pause), sans clic supplémentaire
- [x] #10 Dans cette liste, chaque temps fort a un champ de note facultative, que l'arbitre peut remplir ou modifier à tout moment pendant la partie, enregistré pendant la frappe comme les notes de la partie
- [x] #11 La liste des temps forts défile quand elle dépasse la hauteur du menu
- [x] #12 Dans cette liste, chaque temps fort a une corbeille qui remplace sa ligne, sans fenêtre modale ni bulle et sans changer la hauteur de la ligne, par « Supprimer 00:12:20 ? » (son temps écoulé) avec « Annuler » et « Supprimer ». « Supprimer » retire le temps fort et met à jour la pastille ; « Annuler » ou Échap rétablissent la ligne
- [x] #13 Tant qu'aucun temps fort n'existe, le bouton liste est désactivé, sans pastille, et son infobulle immédiate indique « Aucun temps fort »
- [x] #14 Une fois la partie terminée, le bouton signet disparaît et l'arbitre peut encore consulter la liste des temps forts, sans modifier les notes ni supprimer de temps fort
- [x] #15 La réinitialisation de la partie supprime tous ses temps forts
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
1. Entité `GameHighlight` (partie, heure réelle, temps écoulé, note), migration, fixtures.
2. Contrôleur arbitre : marquer, enregistrer la note, supprimer, refusés hors partie en cours.
3. Composant Twig `GameHighlights` dans la barre du minuteur (partie en cours) et le pied de la grille (partie terminée, lecture seule).
4. Contrôleur Stimulus : menu, notes enregistrées à la frappe, confirmation de suppression dans la ligne.
5. Remise à zéro de la partie, tests.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Entité `GameHighlight` : `markedAt` (heure réelle, à la seconde) et `elapsedSeconds` calculé à la création depuis `startedAt`, sans déduire les pauses ; `Game::getHighlights()` trié du plus récent au plus ancien. Clé étrangère `ON DELETE CASCADE` ; `GameService::reset()` supprime aussi les temps forts.
`Referee\GameHighlightController` : `POST /referee/game/{id}/highlight` (renvoie la ligne `components/GameHighlights/_row.html.twig`, insérée en tête de liste), `/referee/game/highlight/{id}/note` (`GameHighlightNoteDto` via `MapRequestPayload`, note vide = null) et `/delete`. Jeton CSRF `game-highlights-<id>` en en-tête `X-CSRF-Token`, 400 si la partie n'est pas en cours.
Composant anonyme `GameHighlights` (`data-live-ignore`, pour qu'un re-rendu du minuteur ne remette pas l'état serveur par-dessus la liste). Menu ouvert par `game_highlights_controller.ts` plutôt que par le dropdown Bootstrap : l'infobulle du bouton désactivé est portée par un `span` englobant, et le clic en dehors ferme le menu sans être arrêté, donc atteint sa cible. Infobulle « Aucun temps fort » / « Temps forts » mise à jour avec `Tooltip.setContent`. `initTooltips` passe à `getOrCreateInstance` pour ne pas créer deux infobulles sur le même élément.
Limite : un temps fort marqué par un autre arbitre n'apparaît qu'au rechargement de la page (pas de diffusion Mercure).
Vérifié sur https://lethalbingo.localhost : état vide, marquage (pastille, focus dans la note), note enregistrée, confirmation de suppression à hauteur constante, Échap, clic en dehors qui agit sur sa cible, défilement, marquage pendant une pause, partie terminée en lecture seule.

Infobulles et re-rendu des Live Components : un Live Component garde les attributs modifiés en JS et les remet sur l'élément de son rendu suivant, donc ceux que Bootstrap pose (`title` déplacé dans `data-bs-original-title`, `aria-describedby`). Le bouton pause, une fois devenu « Reprendre la partie », gardait la bulle « Mettre en pause », restée affichée. Correctif : `IconButton` passe son texte en `data-bs-title` (lu par Bootstrap sans modifier d'attribut) et en `aria-label` ; `bootstrap.ts` écoute `live:connect` avant le démarrage de Stimulus (`app_controller` est chargé en différé et raterait les composants) et, pour chaque composant, détruit ses infobulles à `render:started` et les recrée à `render:finished` ; `disposeTooltips` retire aussi `aria-describedby`.
<!-- SECTION:NOTES:END -->
