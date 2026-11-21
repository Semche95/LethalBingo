---
id: TASK-36
title: Ajouter un thème clair et sombre
status: In Progress
assignee: []
created_date: 2026-10-09 21:10
updated_date: '2026-11-21T09:18:00Z'
labels:
  - ui
dependencies: []
priority: low
type: feature
ordinal: 40000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
L'interface n'a qu'un seul thème. Permettre de basculer entre un thème clair et un thème sombre en s'appuyant sur ce que Bootstrap 5.3 et Tabler fournissent déjà.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Un sélecteur permet de passer du thème clair au thème sombre
- [ ] #2 Le choix est conservé d'une visite à l'autre
- [ ] #3 Toutes les pages restent lisibles dans les deux thèmes
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
Conception (à valider avant tout code)

1. État actuel
- `<html data-bs-theme="dark">` codé en dur dans `layouts/base.html.twig` : toute l'app est en sombre, Bootstrap 5.3 et Tabler fournissent déjà les deux jeux de variables `--tblr-*` / `--bs-*` selon `data-bs-theme`.
- `base.css` a déjà une surcharge `[data-bs-theme="dark"]` pour `--bingo-border-color`, `--app-hover-bg`, `--app-header-bg` ; `toasts.css` et `tom-select.css` ont déjà leurs variantes claire et sombre.
- Page de partie (`game/game.html.twig`, `body.game-page`, joueurs et arbitre) : palette propre « Lethal Company », sombre uniquement (`game/theme.css`, 41 variables, `docs/GAME.md`), qui rebranche les `--tblr-*`. Pas de navbar.
- Pages d'erreur : étendent `base.html.twig`, sans navbar. Pages avec navbar (`main.html.twig`) : accueil, connexion, profil, espace arbitre (`referee.html.twig`).
- CSP `script-src 'self'` sans nonce (`ContentSecurityPolicyListener`) et règle « pas de JS inline dans Twig » : un script inline en tête pour éviter le flash est exclu ; les scripts Vite sont des modules différés, trop tard pour éviter le flash.

2. Couleurs codées en dur (hors définitions de variables) : environ 100 lignes repérées, dont une vingtaine de règles réellement liées au thème à faire passer par des variables :
- `pages/games-grid.css` (environ 10 : fonds et textes de cartes et badges pensés pour le sombre, `#1c2432`, `#1e3a5f`, `#8b93a1`…) : le plus gros chantier.
- `pages/dashboard.css` : badges de saison (12 couleurs pastel, lisibles sur les deux fonds, à vérifier), fonds `rgba(...)` d'alerte (3).
- `bingo/board.css` (3), `components/teams.css` (2 fonds gris/ambre), `base.css` (`#ff5400`, ombres).
- Le reste (`#fff` sur fond coloré, ombres, `rgba(var(--app-*-rgb), x)`, dégradés sur image de l'accueil) fonctionne dans les deux thèmes et reste tel quel.

3. Mécanisme
- Le thème est rendu côté serveur : `<html data-bs-theme="{{ app_theme() }}">`. Pas de flash, sans script inline, compatible CSP.
- Persistance : cookie `theme` (`light` | `dark`, 1 an, `SameSite=Lax`, `path=/`, non `HttpOnly` puisque posé par le JS). Choisi plutôt que localStorage parce que le serveur doit connaître le choix au rendu pour éviter le flash (localStorage n'est lisible qu'après chargement du JS).
- Changement : le contrôleur Stimulus pose l'attribut sur `<html>` et écrit le cookie, sans rechargement. Turbo Drive ne remplace que le `<body>`, l'attribut de `<html>` persiste ; les visites suivantes le recalculent depuis le cookie.

4. Contrats et fichiers
- `src/Twig/ThemeExtension.php` : fonction Twig `app_theme(): string` qui lit le cookie de la requête courante et ne renvoie que `light` ou `dark` (valeur inconnue ou absente : thème par défaut). Constantes `COOKIE_NAME`, `DEFAULT`. Fonction `app_theme_cookie(): string` (nom du cookie) passée au contrôleur, pour ne pas dupliquer le nom en TS.
- `assets/theme.ts` : helpers purs `isTheme(value): value is Theme`, `nextTheme(theme): Theme`, `themeCookie(name, theme): string` (chaîne `document.cookie`).
- `assets/controllers/theme_toggle_controller.ts` : values `cookieName: String` ; action `toggle()` qui lit `document.documentElement.dataset.bsTheme`, applique `nextTheme`, écrit le cookie ; met à jour l'icône et le libellé accessible (`aria-label`, `title` : « Passer au thème clair/sombre »).
- `templates/components/ThemeToggle.html.twig` (composant Twig anonyme) : bouton `nav-link btn btn-link` avec icônes `tabler:sun` / `tabler:moon`, l'une masquée selon `[data-bs-theme]` en CSS (pas de JS pour l'état initial).
- `layouts/base.html.twig` : `data-bs-theme="{% block theme %}{{ app_theme() }}{% endblock %}"`.
- `layouts/main.html.twig` : bouton dans `navbar-nav order-md-last`, avant le profil, visible connecté ou non.
- `assets/styles/components/theme-toggle.css` (affichage des icônes) importé dans `app.css`.
- CSS : nouvelles variables `--app-*` dans `base.css` (`:root` = clair, `[data-bs-theme="dark"]` = sombre) pour les couleurs de la section 2, puis remplacement dans les fichiers concernés.

5. Tests
- PHPUnit `tests/Twig/ThemeExtensionTest.php` : cookie `light`, `dark`, absent, valeur invalide (retombe sur le défaut).
- Vitest `assets/tests/theme.test.ts` (helpers) et `assets/tests/theme_toggle_controller.test.ts` (attribut posé, cookie écrit, libellé mis à jour).

6. Docs
- `docs/GAME.md` (section palette) selon la décision sur la page de partie ; `.claude/rules/ui-ux.md` : toute nouvelle couleur a sa valeur claire et sombre.

7. Vérification visuelle (https://lethalbingo.localhost, chaque page en clair puis en sombre, rechargement pour vérifier l'absence de flash et la persistance)
- Public : accueil, connexion, pages d'erreur 403, 404, 500.
- Arbitre : profil, tableau de bord (onglets parties, bingos, équipes, saisons), grille des parties, formulaires en modale (Tom Select), toasts des quatre types.
- Partie : `/game/{slug}` côté arbitre et côté joueur, selon la décision ci-dessous.

8. Questions ouvertes
- Thème par défaut sans cookie : sombre (comportement actuel) ou préférence du système ? Suivre le système impose un troisième choix « Auto » et ne peut pas être rendu côté serveur sans flash au premier chargement (seul l'en-tête Client Hint `Sec-CH-Prefers-Color-Scheme` le permet, Chromium uniquement).
- Page de partie (ambiance Lethal Company) : reste-t-elle toujours sombre (bloc `theme` forcé à `dark`), ou faut-il concevoir une palette claire de `game/theme.css` ?
- Sélecteur pour les joueurs : la page de partie n'a pas de navbar ; si elle suit le thème, faut-il y ajouter le sélecteur (et pour l'arbitre seul ou aussi les joueurs) ?
- Pages d'erreur : suivre le cookie sans sélecteur (proposé), ou rester sombres ?
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conception rédigée dans le plan, implémentation en attente de décisions de l'utilisateur :
1. Thème par défaut sans cookie : sombre comme aujourd'hui, ou préférence du système (choix Auto en plus, flash possible à la première visite hors Chromium) ?
2. La page de partie (ambiance Lethal Company) reste-t-elle toujours sombre, ou faut-il une palette claire pour game/theme.css ?
3. Si elle suit le thème, faut-il y ajouter le sélecteur (pas de barre de navigation), pour l'arbitre seul ou aussi pour les joueurs ?
4. Les pages d'erreur suivent-elles le choix (sans sélecteur) ou restent-elles sombres ?
<!-- SECTION:NOTES:END -->
