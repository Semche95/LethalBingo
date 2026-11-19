---
id: doc-1
title: Barème des règles de scoring
type: specification
created_date: 2026-09-30 16:16
updated_date: '2026-11-19T00:31:00Z'
---
# Barème des règles de scoring

Chaque règle du round d'une partie rapporte **valeur × coefficient** points ; le score de la partie est la somme des points de ses règles. Le coefficient peut être négatif (pénalité).

## Valeur d'une règle calculée

Calculée sur les cases validées de la partie :

- **Cases validées** : nombre de cases validées.
- **Lignes de 4 cases validées** et **Lignes de 5 cases validées** : parmi les 12 lignes de la grille (5 lignes, 5 colonnes, 2 diagonales). Une ligne de 4 est faite de 4 cases contiguës validées (positions 1 à 4 ou 2 à 5). Une ligne complète compte seulement comme ligne de 5, jamais aussi comme ligne de 4.

## Valeur d'une règle libre

Saisie par l'arbitre dans la modale Points de la page de la partie, une fois la partie arrêtée ; 0 tant qu'elle n'est pas saisie. La réinitialisation de la partie efface les valeurs saisies, et retirer une règle d'un round efface ses valeurs dans les parties de ce round.

## Calcul et affichage

Les points sont recalculés à chaque affichage : un coefficient modifié s'applique aussi aux parties déjà jouées. Le détail par règle et le total ne sont montrés qu'à l'arbitre (modale Points de la page de la partie, docs/GAME.md section 4).
