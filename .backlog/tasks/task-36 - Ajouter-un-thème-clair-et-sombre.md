---
id: TASK-36
title: Ajouter un thème clair et sombre
status: Done
assignee: []
created_date: 2026-10-09 21:10
updated_date: '2026-11-21T10:03:00Z'
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
- [x] #1 Un sélecteur permet de passer du thème clair au thème sombre
- [x] #2 Le choix est conservé d'une visite à l'autre
- [x] #3 Toutes les pages restent lisibles dans les deux thèmes
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
Conception révisée (décisions de l'utilisateur et relecture intégrées, à valider avant tout code)

1. État actuel
- `<html data-bs-theme="dark">` codé en dur dans `layouts/base.html.twig`. Bootstrap 5.3 et Tabler fournissent déjà leurs variables claires (`:root`) et sombres (`[data-bs-theme="dark"]`) : il suffit de piloter cet attribut.
- `base.css` a déjà une surcharge sombre (`--bingo-border-color`, `--app-hover-bg`, `--app-header-bg`), `toasts.css` et `tom-select.css` ont déjà leurs deux variantes.
- Page de partie (`body.game-page`) : palette sombre seule dans `game/theme.css` (41 variables qui rebranchent les `--tblr-*`), décrite dans `docs/GAME.md`.
- CSP `script-src 'self'` sans nonce et « pas de JS inline dans Twig ». Les scripts Vite sont des modules, donc différés : trop tard pour éviter le flash.

2. Mécanisme sans flash : script externe bloquant dans `<head>`
- `public/theme-init.js`, JS classique (ni module ni `defer`), chargé par `<script src="/theme-init.js">` en tête de `base.html.twig`, avant les feuilles de style. Autorisé par `'self'`, exécuté avant le premier rendu : aucun flash, même au premier chargement et en mode système.
- Repli sans JS : `data-bs-theme="dark"` reste écrit en dur sur `<html>` dans `base.html.twig` ; le script le remplace aussitôt.
- Cache : `/theme-init.js` n'a pas de hash dans son nom. Le Caddyfile lui envoie `Cache-Control: no-cache` (revalidation par ETag à chaque chargement complet, réponse 304 sans contenu), pour qu'une modification du fichier soit prise tout de suite.
- Hors Vite volontairement : Vite ne produit que des modules différés. Fichier minuscule, mis en cache par le navigateur ; une seule requête bloquante par chargement complet, aucune sur les visites Turbo (le `<head>` est conservé, l'attribut de `<html>` aussi).
- Alternatives écartées : cookie et rendu serveur (le serveur ne connaît pas la préférence du système, il faudrait quand même un script pour le mode auto, donc deux mécanismes) ; `@media (prefers-color-scheme)` en CSS (Tabler ne branche ses variables sombres que sur `[data-bs-theme="dark"]`, il faudrait recopier tout son jeu sombre) ; en-tête Client Hint `Sec-CH-Prefers-Color-Scheme` (Chromium seulement, absent de la première requête sans aller-retour supplémentaire).

3. Persistance : localStorage
- Clé `theme`, valeurs `light` ou `dark` ; absence de clé = mode système. Lu de façon synchrone par le script bloquant, donc aucun besoin de cookie (le serveur n'a pas à connaître le thème).
- Valeur invalide ou localStorage inaccessible (navigation privée stricte) : mode système, sans erreur.

4. Contrats (aucune globale)
- `public/theme-init.js` est la seule source de la logique (clé, résolution, application) :
  - au chargement : calcule la préférence (`light` | `dark` | `auto`), pose `data-bs-theme` (thème effectif) et `data-theme-preference` (préférence) sur `<html>` ;
  - écoute `app:theme` sur `document` (`CustomEvent`, `detail` = préférence) : écrit la clé (`light`/`dark`) ou la retire (`auto`), ignore une valeur invalide, réapplique les attributs ;
  - écoute `storage` sur `window` (clé `theme`) : synchronise les autres onglets ouverts ;
  - écoute `matchMedia('(prefers-color-scheme: dark)')` : en mode `auto`, réapplique le thème quand le système change.
- `assets/controllers/theme_switcher_controller.ts` : action `choose` avec paramètre `preference` (`data-theme-switcher-preference-param`), qui émet `document.dispatchEvent(new CustomEvent('app:theme', {detail: preference}))`. Il ne connaît ni la clé ni le stockage. Rien à déclarer dans `env.d.ts`.
- L'icône et la coche du choix actif suivent `data-theme-preference` en CSS : justes dès le premier rendu, sans JS.

5. Sélecteur (même composant partout)
- `templates/components/ThemeSwitcher.html.twig`, composant Twig anonyme, prop `buttonClass`. Bouton icône (`tabler:sun`, `tabler:moon` ou `tabler:device-desktop` selon la préférence, `aria-label`/`title` « Thème ») qui ouvre un dropdown Bootstrap `dropdown-menu-end` : « Clair », « Sombre », « Système », coche sur le choix actif (`aria-checked`).
- Barre de navigation (`layouts/main.html.twig`) : dans `navbar-nav order-md-last`, avant le profil, connecté ou non, `buttonClass: 'nav-link btn btn-link'`. Couvre accueil, connexion, profil et espace arbitre.
- Page de partie (`game/game.html.twig`) : dans la barre fixe en haut à droite, à côté du bouton Accueil, pour l'arbitre et les joueurs, `buttonClass: 'btn btn-icon btn-overlay'`. Réglage d'affichage local au navigateur, aucune requête serveur : compatible avec « joueurs en lecture seule ».
- Pages d'erreur : rien à ajouter, elles étendent `base.html.twig` et suivent donc le thème, sans sélecteur.
- `assets/styles/components/theme-switcher.css` (icône et coche selon `data-theme-preference`), importé dans `app.css`.

6. Couleurs de l'app (hors page de partie) : inventaire exhaustif
Nouvelles variables `--app-*` dans `base.css`, valeur claire sous `:root`, sombre sous `[data-bs-theme="dark"]`, puis remplacement dans les règles :
- `base.css:14-20` : `--app-blue`, `--app-green`, `--app-yellow` (et leurs `-rgb`, dégradés) n'ont qu'une valeur ; ajouter leur variante claire (jaune `#fcc419` illisible sur clair, prévoir `--app-yellow-text` foncé pour le texte).
- `base.css:140` : `#ff5400`.
- `bingo/board.css:34` : `#5d9010` ; `bingo/board.css:56-57` : `rgba(204, 41, 41, 0.14)` et `rgba(204, 41, 41, 0.30)` (à brancher sur `--app-danger-rgb`).
- `components/teams.css:34` : `.btn-ghost-danger` en texte `#fff` sur fond transparent, invisible en clair ; `components/teams.css:62` : `rgba(134, 142, 150, 0.15)` ; `components/teams.css:77` : `rgba(210, 153, 34, 0.15)`.
- `pages/dashboard.css:179` : `rgba(134, 142, 150, 0.2)` ; `:203` : `rgba(255, 193, 68, 0.08)` ; `:212` : `rgba(255, 145, 0, 0.08)` ; `:229-234` : `--season-border` pastel, à contrôler sur fond clair (variante plus soutenue en clair si besoin) ; `:236-241` badges de saison (fond pastel, texte foncé) lisibles dans les deux thèmes, gardés.
- `pages/games-grid.css:56-57, 79, 84, 94, 127-128, 133-134, 138-139` : fonds, textes et bordures pensés pour le sombre ; `:114` repli `#206bc4` de `--tblr-primary`, gardé.
- `pages/home.css:172` : dégradé `#475569` vers `#334155`.
- Couleurs d'équipe (`TeamColorExtension`, 10 teintes moyennes) posées en `style=` (bordure de `TeamList`, `--team-color` de `games-grid.css:70`) : contraste à contrôler sur fond clair ; jamais utilisées comme fond sous un texte `#fff`.
- Images : logo de la navbar, GIF de l'accueil et GIF des pages d'erreur à contrôler sur fond clair.
Valeurs volontairement identiques dans les deux thèmes (gardées en dur) : `#fff` sur fond coloré plein (`base.css:66`, `components/surface-cards.css:41`, `pages/dashboard.css:188, 193`, `pages/home.css:52, 95, 173`, `pages/auth.css:7`), ombres `rgba(0, 0, 0, x)` (`base.css:91, 96`, `components/surface-cards.css:20`, `pages/home.css:43, 83`), `rgba(var(--…-rgb), x)` (déjà thémables), dégradé `pages/dashboard.css:46`, badges de saison `pages/dashboard.css:236-241`, toasts (`components/toasts.css`, déjà en deux variantes).
Contrôle de fin de tâche, qui doit revenir vide une fois ces lignes exclues :
`grep -rnE '#[0-9a-fA-F]{3,8}\b|rgba?\([0-9]' assets/styles | grep -vE ':\s*--' | grep -vE 'game/palette.css|components/toasts.css'` puis retrait des lignes listées ci-dessus comme volontaires.

7. Palette claire de la page de partie
- Nouveau fichier `game/palette.css` (importé avant `game/theme.css`) : variables claires sous `.game-page`, valeurs sombres actuelles déplacées sous `[data-bs-theme="dark"] .game-page`. `game/theme.css` garde le rebranchement Tabler et les règles (il ferait plus de 300 lignes avec les deux palettes).
- Identité gardée : orange HUD `#f8400c` en couleur principale (`--app-on-primary` sombre inchangé), police 3270, coins et lueur orange, même répartition des rôles (vert validation, bleu information, jaune temps forts, cramoisi réinitialisation). Fond « papier de terminal » chaud plutôt que blanc pur.
- Valeurs claires proposées (contraste AA à contrôler visuellement) : `--app-bg #f3efe9`, `--app-surface #fbf8f4`, `--app-surface-2 #ebe5de`, `--app-surface-3 #ddd5cc`, `--app-border #d6cdc3`, `--app-text #1d1918`, `--app-text-muted #5f5550`, `--app-shade rgb(0 0 0 / 0.08)`, `--app-hover-bg rgb(0 0 0 / 0.05)`, `--app-overlay-shadow rgb(0 0 0 / 0.18)`, `--app-primary-text #c2320a`, `--app-green #3f9a22`, `--app-green-text #2a6f15`, `--app-blue-text #2a44d6`, `--app-yellow #e0a800`, `--app-danger-text #a4133c`, `--bingo-square-validated-bg #d6efcb`, grilles masquées en rayures `rgb(0 0 0 / 0.05)` sur `#e6e0d8` (validée : rayures vertes sur `#e3f2dc`), halo radial du fond à opacité réduite.
- Règles de contraste AA en clair : `--app-primary-text` et `--app-yellow-text` seulement en texte sur `--app-bg` et `--app-surface` (jamais sur `--app-surface-3`) ; `--app-green` (`#3f9a22`) jamais en texte, seulement en fond, bordure ou pastille ; boutons verts : `#fff` sur `#3f9a22` donne 3,59, insuffisant, donc texte foncé (`--app-on-primary`) ou vert plus sombre en fond, à trancher au contrôle visuel.
- Nouvelle variable `--app-yellow-text` (`#ffc21a` en sombre, `#8a6500` en clair) pour les textes jaunes (bouton `btn-outline-warning`, pause), le jaune vif étant illisible sur fond clair.

8. Tests (Vitest, aucune classe PHP ajoutée)
- `assets/tests/theme_init.test.ts` (charge `public/theme-init.js` dans jsdom, `matchMedia` et localStorage simulés) : sans clé = thème du système ; `light`/`dark` stockés ; clé stockée invalide = système ; événement `app:theme` (`light`, `dark`, `auto` qui retire la clé, valeur invalide ignorée) ; événement `storage` d'un autre onglet appliqué ; changement du système appliqué en mode auto, ignoré en mode explicite ; `setPreference('auto')` retire la clé ; localStorage qui lève une erreur = système sans plantage.
- `assets/tests/theme_switcher_controller.test.ts` : `choose` émet `app:theme` avec la bonne préférence.

9. Docs
- `docs/GAME.md` : palette en deux thèmes, `game/palette.css`, `--app-yellow-text`, sélecteur de thème sur la page.
- `.claude/rules/ui-ux.md` : toute couleur a sa valeur claire et sombre ; ligne 14, la référence à `game/theme.css` pour la palette passe à `game/palette.css`.
- `.claude/rules/frontend.md` : exception `public/theme-init.js` : fichier en JS classique (ni TypeScript ni module, hors Vite), chargé en bloquant pour éviter le flash, servi en `Cache-Control: no-cache` et ordre d'import de `game/palette.css`.

10. Vérification visuelle (https://lethalbingo.localhost), chaque page en clair, en sombre et en mode système (système clair puis sombre via l'émulation `prefers-color-scheme`)
- À chaque fois : rechargement complet sans flash, choix conservé après fermeture de l'onglet, navigation Turbo sans retour à l'ancien thème, icône et coche justes.
- Public : accueil, connexion, erreurs 403, 404, 500.
- Arbitre : profil, tableau de bord et ses onglets, grille des parties, modales (Tom Select), toasts des quatre types.
- Partie `/game/{slug}` côté arbitre (notes, historique, outils du timer, réinitialisation et sa confirmation) et côté joueur (grille masquée, cases validées, mobile) ; sélecteur dans la barre fixe sur les deux.
- Deux onglets ouverts : un changement dans l'un s'applique à l'autre.
- Sans JS : thème sombre de repli.
- Couleurs d'équipe, logo, GIF de l'accueil et des erreurs sur fond clair.
- Changement du thème du système pendant que la page est ouverte en mode système : bascule immédiate.
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Conception rédigée dans le plan, implémentation en attente de décisions de l'utilisateur :
1. Thème par défaut sans cookie : sombre comme aujourd'hui, ou préférence du système (choix Auto en plus, flash possible à la première visite hors Chromium) ?
2. La page de partie (ambiance Lethal Company) reste-t-elle toujours sombre, ou faut-il une palette claire pour game/theme.css ?
3. Si elle suit le thème, faut-il y ajouter le sélecteur (pas de barre de navigation), pour l'arbitre seul ou aussi pour les joueurs ?
4. Les pages d'erreur suivent-elles le choix (sans sélecteur) ou restent-elles sombres ?

Décisions de l'utilisateur :
- thème par défaut sans choix enregistré : préférence du système ;
- la page de partie suit aussi le thème (palette claire à créer pour game/theme.css), avec le sélecteur pour l'arbitre comme pour les joueurs ;
- les pages d'erreur suivent le thème choisi, sans sélecteur.

Implémenté selon le plan révisé (sans commit). Écarts et constats : --tblr-muted-fg de Tabler vaut --tblr-light dans les deux thèmes (texte presque blanc), remplacé par --app-text-muted (13 règles) ; .btn-ghost-danger avait un texte #fff ; .bingo .bingo-square un texte --tblr-white ; --bingo-square-blue et le hover du bouton principal de la page de partie (--app-primary-hover) ont reçu une variante claire. Grep de fin de tâche vide (seuls game/palette.css et components/toasts.css, déjà en deux variantes, sont exclus). Reste la vérification visuelle dans les deux thèmes et en mode système.

Vérifié à l'écran : accueil, connexion, tableau de bord, grille des parties, fiche bingo, page de partie arbitre et joueur (mobile 375 px), erreur 404 en clair et en sombre ; mode système (suit prefers-color-scheme et bascule à chaud), menu Clair/Sombre/Système avec coche, choix conservé après rechargement et navigation Turbo, sélecteur présent côté joueur. Vérificateur indépendant : conforme ; deux teintes du sombre corrigées pour rester identiques à avant.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Thème clair, sombre ou système (par défaut), choisi par un sélecteur dans la barre de navigation et sur la page de partie (arbitre et joueurs), mémorisé dans le navigateur et appliqué avant l'affichage par public/theme-init.js ; palette claire de la page de partie ; thème sombre inchangé.
<!-- SECTION:FINAL_SUMMARY:END -->
