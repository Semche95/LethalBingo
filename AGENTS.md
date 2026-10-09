# AGENTS.md : règles du projet LethalBingo

## Économie de tokens

- **Sous-agent seulement s'il économise du contexte** : exploration large (nombreux fichiers ou dossiers), sortie volumineuse dont seule la conclusion compte (logs, audit), tâches indépendantes à paralléliser. Pas pour une modification ciblée sur des fichiers connus.
- Contexte d'un sous-agent minimal : objectif, fichiers concernés, critères de fin, et seulement les règles de ce fichier utiles à la tâche. Jamais l'historique de la conversation.
- Chercher avant de lire (`grep -n`, `find`), puis lire uniquement la portion utile (`offset`/`limit`) plutôt que le fichier entier.
- Ne pas relire un fichier qu'on vient d'éditer ni re-vérifier un fait déjà établi.
- Filtrer les sorties de commandes (`tail`, `grep`, `-q`, `--plain`) : ne garder que ce qui sert à décider.
- Grouper les vérifications en une commande et n'afficher que leur fin.
- Lancer en parallèle les appels d'outils indépendants.
- `Edit` pour une modification partielle, `Write` seulement pour créer ou réécrire entièrement.
- Réponses courtes : le résultat et ce qui reste à faire, sans répéter le diff ni le contexte connu.

## Commandes

```bash
just dev / just up            # Docker + Vite / Docker seul
just fixtures                 # rechargement complet de la base de dev
just test                     # PHPUnit (Docker)
just sf lint:container
just phpstan
just cs                       # PHP-CS-Fixer dry-run
just rector                   # Rector dry-run (rector-fix pour appliquer)
```

- Les 5 vérifications (`test`, `lint:container`, `phpstan`, `cs`, `rector`) passent avant chaque commit.
- L'agent peut lancer `just up`, `just dev` et `just fixtures`. Avant `just dev`, vérifier qu'il ne tourne pas déjà : `docker compose ps` et `curl -s -o /dev/null -w "%{http_code}" http://localhost:5173/build/@vite/client` (un process `vite` ne suffit pas).
- **Jamais de build d'assets** (`pnpm run build`, `just build-assets`, `vite build`) sauf demande explicite ou vérification de build de prod : le HMR de Vite gère les changements frontend.

## PHP (8.4+)

- Syntaxe moderne : constructor promotion, readonly, enums, match, nullsafe, first-class callables, named arguments. Pas de code déprécié.
- Attributs PHP partout (routing, validation, ORM, security), jamais d'annotations.
- `new ClassName()->method()` sans parenthèses autour du `new`.
- Pas d'appel entre controllers : logique partagée dans un Service. DRY, early returns, pas de globales.
- **Repository** : DQL uniquement (pas de SQL natif), résultats bruts sans boucle PHP (`foreach`, `array_map`) ; le réarrangement se fait dans le service ou le contrôleur.
- Pas de fusion PHP de données qu'un template Twig re-parcourt : exposer les jeux de données bruts comme propriétés distinctes du composant et joindre dans le template.
- Paramètres mot de passe / secret : `#[SensitiveParameter]`.

## Frontend

- Vite, Bootstrap 5.3 + Tabler, Turbo + Stimulus, Symfony UX Live Components.
- Pas de `console.log`, pas de JS inline dans Twig, `removeEventListener` systématique avec la même référence bindée (`connect()` / `disconnect()`).

## Twig

- Pas de `style="..."` : classes Bootstrap/Tabler ou classes dédiées dans `app.css`.
- Macros partagées de `macros.html.twig` ; templates des Live Components dans `templates/components/`.
- Fonctions `form_*` plutôt que des champs écrits à la main.
- **Formulaires (Auto-Validating Form)** : `ComponentWithFormTrait` + `DefaultActionTrait` ; `instantiateForm()` crée le formulaire, `hasValidationErrors()` teste l'état, `save()` appelle `submitForm()` puis traite. Les contraintes Assert PHP sont la seule validation (pas de doublon JS). LiveProp `fieldName: 'formData'` pour l'entité, `writable: true` pour les champs modifiables. Validation `data-model="on(change)|*"` (au change, pas à la frappe).

## UI/UX : boutons et actions destructives

- Un seul bouton plein par écran (action principale) ; les actions secondaires en outline/ghost.
- Suppression irréversible : zone isolée (type « zone dangereuse ») + confirmation obligatoire (modal ou double confirmation).
- Icônes de suppression mineures : grises par défaut, rouges seulement au hover/focus.
- Rouge réservé aux vrais points de non-retour, le moins possible par écran. S'appuyer sur les variants `solid` / `outline` existants.

## Tests

- Toute nouvelle classe dans `src/Service/`, `src/Controller/` ou `src/Repository/` a son test dans le dossier `tests/` correspondant.

## Base de données

- Doctrine ORM, PostgreSQL 16, migrations dans `migrations/`.
- Après chaque changement d'Entity : `just sf doctrine:migrations:diff`, puis mettre à jour `src/DataFixtures/`.

## Structure

```
src/Controller/ (Referee/ = espace arbitre), DataFixtures/, Dto/, Entity/, Form/,
    Repository/, Service/, Twig/Components/ (Live Components), Validator/
templates/components/, layouts/ (base, main, referee), referee/, game/, security/
assets/controllers/ (component/ = UX), icons/, sounds/
```

## Git

- Tout sur `main`. Branche exceptionnelle : rebase puis `git merge --ff-only`, jamais de commit de merge ; `.backlog/` reste sur `main`.
- Message d'une seule ligne `feat(scope): …` ou `chore(scope): …`, sans corps ni trailer (`Co-Authored-By` interdit).
- Scopes : `game`, `bingo`, `referee`, `timer`, `datatable`, `template`, `env`, `config`, `migrations`.

## Backlog.md

- Toutes les notes (constats, plans, TODO, bugs, décisions) vivent dans `.backlog/`, manipulé uniquement via `pnpm exec backlog` (jamais `pnpm dlx` ni `npx`), jamais dans un Markdown libre.
- Seule source de vérité : `main` (`check_active_branches: false`). Les changements `.backlog/` sont commités avec le code de la tâche.
- Règles détaillées dans la skill `backlog-tasks` (chargée par un hook dans la session principale) : un sous-agent qui touche à Backlog.md l'invoque via `Skill` avant d'agir.

## Sécurité et erreurs

- Tokens Mercure dans `.env.local` (jamais commité), secrets Docker dans `compose.prod.yaml`.
- CSRF sur toutes les mutations (double-submit cookie côté Stimulus).
- Rôles : ADMIN > REFEREE > USER (`security.yaml`). Voir `docs/SECURITY.md`.
- Pages d'erreur dans `templates/bundles/TwigBundle/Exception/`, aucun détail d'erreur en prod.
