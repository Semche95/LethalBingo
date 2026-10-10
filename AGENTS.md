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
- Échec de la CI : avant de le déclarer corrigé, vider le cache de test (`docker compose exec -T php rm -rf var/cache/test`) puis relancer `just test`, comme sur GitHub. Les tests n'appellent aucun service externe.
- L'agent peut lancer `just up`, `just dev` et `just fixtures`. Avant `just dev`, vérifier qu'il ne tourne pas déjà : `docker compose ps` et `curl -s -o /dev/null -w "%{http_code}" http://localhost:5173/build/@vite/client` (un process `vite` ne suffit pas).
- **Jamais de build d'assets** (`pnpm run build`, `just build-assets`, `vite build`) sauf demande explicite ou vérification de build de prod : le HMR de Vite gère les changements frontend.

## Principes généraux

- **DRY dans toute l'app** (PHP, Twig, TS, CSS) : avant d'écrire un bloc qui existe déjà ailleurs, le factoriser (Service, composant Twig, macro, helper TS, classe CSS) et réutiliser l'existant. Ex. : modales de confirmation via le composant `ConfirmModal`.

## PHP (8.5+)

- Syntaxe moderne : constructor promotion, readonly, enums, match, nullsafe, first-class callables, named arguments. Pas de code déprécié.
- Attributs PHP partout (routing, validation, ORM, security), jamais d'annotations.
- `new ClassName()->method()` sans parenthèses autour du `new`.
- Pas d'appel entre controllers : logique partagée dans un Service. Early returns, pas de globales.
- **Repository** : DQL uniquement (pas de SQL natif), résultats bruts sans boucle PHP (`foreach`, `array_map`) ; le réarrangement se fait dans le service ou le contrôleur.
- Pas de fusion PHP de données qu'un template Twig re-parcourt : exposer les jeux de données bruts comme propriétés distinctes du composant et joindre dans le template.
- Paramètres mot de passe / secret : `#[SensitiveParameter]`.

## Symfony

- Nouvelle fonctionnalité via `composer require` (la recipe Flex enregistre le bundle et sa config) : jamais de `bundles.php` ni de config de bundle écrits à la main.
- Autowiring et autoconfiguration ; cas particuliers dans la classe (`#[Autowire]`, `#[Target]`), `services.yaml` en dernier recours.
- Données de requête via `#[MapRequestPayload]` / `#[MapQueryString]`, pas de `json_decode()` manuel.
- Pas de service `readonly` susceptible de devenir `lazy: true` (un proxy lazy ne peut pas étendre une classe `readonly`).
- Exclusion mutuelle (actions concurrentes sur la même donnée) via `symfony/lock` (`LockFactory`), jamais de drapeau ou fichier verrou maison.
- En cas d'erreur, lire `var/log/dev.log` et le profiler (`/_profiler`) avant de modifier le code.
- `make:*` avec tous les arguments et `--no-interaction` (sinon la commande s'interrompt).
- Vérifier plutôt que supposer : `debug:router`, `debug:container`, `debug:autowiring`, `debug:config`, `config:dump-reference`, la source dans `vendor/`, et la doc de la version installée.

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
- Suppression impossible (règle métier, dépendances…) : bouton affiché mais désactivé, avec une tooltip qui explique pourquoi (jamais masqué ni cliquable pour finir sur une erreur).
- Rouge réservé aux vrais points de non-retour, le moins possible par écran. S'appuyer sur les variants `solid` / `outline` existants.

## Tests

- Toute nouvelle classe dans `src/Service/`, `src/Controller/` ou `src/Repository/` a son test dans le dossier `tests/` correspondant.

## Base de données

- Doctrine ORM, PostgreSQL 18, migrations dans `migrations/`.
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

- Règles générales dans la skill globale `backlog-tasks` ; ci-dessous, uniquement les particularités du projet.
- CLI : `pnpm exec backlog …` (version épinglée dans `package.json`), jamais le `backlog` global, `pnpm dlx` ni `npx`.
- `.backlog/` est versionné, sur `main` uniquement (`check_active_branches: false`) : ses changements sont commités avec le code de la tâche.
- Statuts : à faire = `To Do`, en cours = `In Progress`, terminé = `Done`. Pas de statut « à vérifier » : une tâche qui attend une vérification manuelle reste `In Progress`, avec ce qu'il reste à vérifier.
- Definition of Done (`config.yml`) : test de toute nouvelle classe `src/Service/`, `src/Controller/` ou `src/Repository/`, migration et fixtures pour tout changement d'entité, puis `just test`, `just sf lint:container`, `just phpstan`, `just cs`, `just rector`.
- Vérification visuelle sur https://lethalbingo.localhost obligatoire avant de terminer une tâche dont le changement est visible dans l'interface.
- Langue des tâches : français.
- Labels courants : `timer`, `gestion-parties`, `dashboard-arbitre`, `ui`, `regles-scoring`, `historique`, `contenu`, `rounds`, `preparation`, `comptes`, `traduction`.
- Document transverse existant : règles de scoring à définir (doc-1).

## Domaine

- Seuls les arbitres et les administrateurs ont un compte (`User`). Les joueurs n'en ont pas : ce sont des `TeamMember` qui suivent leur partie via son lien unique.
- Aucun pseudo de compte n'est jamais affiché aux joueurs : rien de ce qui est visible côté joueur ne dépend du `username`.

## Sécurité et erreurs

- Tokens Mercure dans `.env.local` (jamais commité), secrets Docker dans `compose.prod.yaml`.
- CSRF sur toutes les mutations (double-submit cookie côté Stimulus).
- Rôles : ADMIN > REFEREE > USER (`security.yaml`). Voir `docs/SECURITY.md`.
- Pages d'erreur dans `templates/bundles/TwigBundle/Exception/`, aucun détail d'erreur en prod.
