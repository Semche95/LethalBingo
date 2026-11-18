---
paths:
  - "templates/**"
  - "assets/**"
---

# UI/UX : boutons et actions destructives

- Bouton plein réservé aux actions qui doivent sauter aux yeux (action principale, action destructive) ; les actions secondaires en outline/ghost. Pas de limite stricte par écran : c'est une question de hiérarchie visuelle, pas un compte.
- Suppression irréversible : zone isolée (type « zone dangereuse ») + confirmation obligatoire (modal ou double confirmation).
- Icônes de suppression mineures : grises par défaut, rouges seulement au hover/focus.
- Suppression impossible (règle métier, dépendances…) : bouton affiché mais désactivé, avec une tooltip qui explique pourquoi (jamais masqué ni cliquable pour finir sur une erreur).
- Rouge réservé aux vrais points de non-retour, le moins possible par écran. S'appuyer sur les variants `solid` / `outline` existants.
- Couleurs : réutiliser les variables `--app-*` et `--bingo-*` de `assets/styles/base.css` (palette de la page de partie : `assets/styles/game/theme.css`) (ou celles de Tabler `--tblr-*`), jamais de couleur en dur.
