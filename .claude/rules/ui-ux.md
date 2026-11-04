---
paths:
  - "templates/**"
  - "assets/**"
---

# UI/UX : boutons et actions destructives

- Un seul bouton plein par écran (action principale) ; les actions secondaires en outline/ghost.
- Suppression irréversible : zone isolée (type « zone dangereuse ») + confirmation obligatoire (modal ou double confirmation).
- Icônes de suppression mineures : grises par défaut, rouges seulement au hover/focus.
- Suppression impossible (règle métier, dépendances…) : bouton affiché mais désactivé, avec une tooltip qui explique pourquoi (jamais masqué ni cliquable pour finir sur une erreur).
- Rouge réservé aux vrais points de non-retour, le moins possible par écran. S'appuyer sur les variants `solid` / `outline` existants.
