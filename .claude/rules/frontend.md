---
paths:
  - "assets/**"
  - "vite.config.ts"
  - "vitest.config.ts"
  - "package.json"
---

# Frontend

- Vite, Bootstrap 5.3 + Tabler, Turbo + Stimulus, Symfony UX Live Components.
- Pas de `console.log`, pas de JS inline dans Twig, `removeEventListener` systématique avec la même référence bindée (`connect()` / `disconnect()`).
- Styles : `assets/app.css` ne contient que des `@import`, dans l'ordre de la cascade ; les règles vont dans `assets/styles/`, un fichier par sujet (`components/`, `pages/`, `bingo/`, `game/`), moins de 300 lignes chacun. `game/mobile.css` reste importé en dernier, `game/palette.css` juste avant `game/theme.css`.
- Exception : `public/theme-init.js` est un fichier en JS classique (ni TypeScript ni module, hors Vite), chargé sans `defer` dans le `<head>` pour poser le thème avant le premier rendu ; servi en `Cache-Control: no-cache` (`frankenphp/Caddyfile`). Seul à connaître la clé localStorage `theme` : le sélecteur lui envoie le choix par l'événement `app:theme`.
