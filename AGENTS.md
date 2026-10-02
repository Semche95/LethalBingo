# AGENTS.md — Règles spécifiques au projet LethalBingo

## Délégation aux sous-agents

- **Chaque tâche d'implémentation ou d'exploration** doit être confiée à un sous-agent dédié, lancé avec un **contexte minimal** :
  - la description précise de la tâche (objectif, fichiers ou zones concernés, critères de fin) ;
  - le **contenu complet de ce fichier `AGENTS.md`**, pour que le sous-agent applique les mêmes règles.
- Ne pas transmettre l'historique de la conversation ni d'informations sans rapport avec la tâche.
- L'agent principal coordonne, vérifie le résultat rendu par le sous-agent et en rend compte à l'utilisateur.

## Commandes obligatoires avant chaque commit

```bash
just test                    # Tests PHPUnit dans le conteneur Docker
just sf lint:container       # Vérification du conteneur de services
just phpstan                 # Analyse statique PHPStan
just cs                      # Vérification du style PHP-CS-Fixer (dry-run)
just rector                  # Vérification Rector (dry-run)
```

## Prérequis

- PHP 8.4+
- Node.js + pnpm
- Docker + Docker Compose
- just (task runner)

## Démarrage du dev

```bash
just dev        # Démarre Docker + Vite dev server
just up         # Uniquement les conteneurs Docker
```

- L'agent peut lancer `just up` et `just dev` si l'environnement n'est pas déjà démarré (vérifier d'abord avec `docker compose ps` et que Vite répond réellement sur le port 5173, ex. `curl -s -o /dev/null -w "%{http_code}" http://localhost:5173/build/@vite/client` — la simple présence d'un process `vite` ne suffit pas).
- L'agent peut recharger la base de dev avec `just fixtures` si un test (manuel ou navigateur) nécessite un état propre.

## Vite et gestion des assets

- **Ne jamais lancer automatiquement de build** des assets après une modification de fichiers frontend (TypeScript, JavaScript, CSS, images, etc.).
- **Serveur de dev Vite** : `just dev` (ou `pnpm run dev`) peut être lancé uniquement s'il ne tourne pas déjà (ex. pour tester dans un navigateur) — vérifier avant de le démarrer pour ne pas créer de doublon.
- Après modification de fichiers frontend, **laisser le HMR de Vite gérer la mise à jour** en temps réel.
- Les commandes de build (`pnpm run build`, `just build-assets`, `vite build` ou toute commande équivalente) ne doivent être lancées que sur **demande explicite de l'utilisateur** ou dans le cadre d'une vérification de build de production.

## Conventions de code PHP

- **PHP 8.4+ obligatoire** : constructor promotion, named arguments, union/intersection types, match expressions, nullsafe operator, readonly properties, enums, first-class callable syntax
- **Pas de code déprécié** : toujours utiliser les méthodes/fonctions actuelles (appliqué automatiquement par Rector : `just rector-fix`)
- **Attributs PHP** pour tout : routing, validation, ORM, security, services (jamais les annotations)
- **Pas d'appels entre controllers** : extraire la logique partagée dans des Services
- **Factoriser** le code dupliqué agressivement (DRY)
- **Early returns** préférés aux conditions imbriquées profondément
- **Pas de variables globales** sauf nécessité absolue
- **Pas de parenthèses** autour des `new` quand on chaîne des méthodes : `$var = new ClassName()->method()`
  *(syntaxe valide depuis PHP 8.4 — vérifier que PHPStan/PHP-CS-Fixer sont configurés pour l'accepter et ne pas la signaler comme erreur)*
- **Repository — pas de boucles PHP, DQL uniquement** : les méthodes de repository doivent retourner les résultats bruts de la requête DQL sans transformation PHP par boucle (`foreach`, `array_map`, etc.). Si un réarrangement des données est nécessaire, il doit être effectué par le service ou le contrôleur appelant, pas dans le repository. Les requêtes SQL natives (`NativeQuery`, `SQL`) sont interdites — seul le DQL est autorisé.
- **Pas de fusion PHP quand Twig re-parcourt** : éviter d'empiler des boucles de fusion en PHP (ex. injecter des données de complétude dans un tableau JSON déjà agrégé via `json_decode` / `json_encode`) quand la donnée sera de toute façon re-parcourue en Twig pour l'affichage. Exposer plutôt les deux jeux de données bruts comme propriétés distinctes du composant et faire la jointure/logique dans le template.

## Conventions de code TypeScript / Frontend

- **Vite** pour le build des assets
- **Bootstrap 5.3 + Tabler** pour le styling
- **Hotwired Turbo + Stimulus** pour l'interactivité
- **Symfony UX Live Components** pour les composants serveur interactifs
- **Auto-Validating Form** : pour les formulaires, utiliser le pattern Live Component avec `ComponentWithFormTrait` + `DefaultActionTrait` — la validation côté PHP (constraints Assert) est la seule source de vérité, pas de validation JS dupliquée
- **Pas de `console.log`** dans le code produit
- **Pas de JavaScript inline** dans les templates Twig — tout dans les fichiers JS/Stimulus
- **Toujours appeler `removeEventListener`** quand on utilise `addEventListener` (même référence bindée dans `connect()` / `disconnect()`)

## Conventions Templates (Twig)

- **Pas de style inline** (`style="..."`) — utiliser les classes CSS Bootstrap/Tabler ou créer des classes dédiées dans `app.css`
- Utiliser les macros Twig partagées (`macros.html.twig`)
- Les composants Live Components ont leur template dans `templates/components/`
- **Pattern Auto-Validating Form** : `ComponentWithFormTrait` + `DefaultActionTrait` — `instantiateForm()` crée le formulaire, `hasValidationErrors()` vérifie l'état, `save()` appelle `submitForm()` puis traite les données
- **Nom des LiveProp** : `fieldName: 'formData'` pour l'entité liée au formulaire, `writable: true` pour les champs modifiables par le client
- **Validation temps réel** : `data-model="on(change)|*"` (défaut du trait) — validation au blur/change, pas à chaque frappe
- Privilégier les fonctions form_* (form_start, form_row, form_widget, form_label, form_errors, etc.) plutôt que de créer les champs de formulaire à la main

## UI/UX – Conventions boutons et actions destructives

Ces règles s'appliquent à tous les formulaires et écrans de gestion (équipes, membres, parties, bingos, etc.).

### Hiérarchie des boutons
- Un seul bouton **plein/solid** par écran : réservé à l'action principale (ex. `Enregistrer`).
- Les actions secondaires (`Retour`, `Nouveau membre`, etc.) utilisent un style **outline/ghost**.
- Ne jamais avoir deux boutons pleins de même poids visuel sur un même écran.

### Actions destructives
- Toute action de suppression irréversible (ex. suppression d'une équipe) doit :
  - être visuellement isolée des autres actions (espacement, zone dédiée type "zone dangereuse") pour éviter les clics accidentels ;
  - déclencher une confirmation obligatoire (modal ou double confirmation) avant exécution.
- Les icônes de suppression mineures (ex. suppression d'un membre dans une liste) :
  - sont **grises/neutres** par défaut ;
  - passent en rouge uniquement au **survol (hover)** ou au **focus**.

### Usage de la couleur rouge
- Réserver le rouge aux vrais points de non-retour.
- Éviter de multiplier les éléments rouges sur un même écran : plus il y en a, moins le signal d'alerte est efficace.

### Cohérence
- Respecter ces conventions sur l'ensemble de l'application, en s'appuyant sur les composants du design system existant (variants `solid` / `outline` des boutons, etc.).

## Tests

- **Test obligatoire** : toute nouvelle classe ajoutée dans `src/Service/`, `src/Controller/` ou `src/Repository/` doit être accompagnée d'un test dans le dossier correspondant (`tests/Service/`, `tests/Controller/`, `tests/Repository/`) avant d'être considérée terminée
- `just test` doit passer sans erreur avant tout commit

## Base de données

- **Doctrine ORM** avec PostgreSQL 16
- Migrations dans `migrations/`
- Après chaque changement d'Entity :
  1. Générer la migration : `just sf doctrine:migrations:diff`
  2. Mettre à jour les fixtures dans `src/DataFixtures/` en conséquence
- Fixtures : `just fixtures` (truncate + rechargement complet)

## Structure du projet

```
src/
  Controller/          # Controllers HTTP (Routing attributs)
  Controller/Referee/  # Controllers de l'espace arbitre
  DataFixtures/        # Fixtures de test
  Dto/                 # Data Transfer Objects
  Entity/              # Entités Doctrine
  Form/                # Types de formulaires Symfony
  Repository/          # Repositories Doctrine
  Service/             # Logique métier (BingoService, GameService, etc.)
  Twig/Components/     # Symfony UX Live Components (PHP)
  Validator/           # Contraintes de validation custom
templates/
  components/          # Templates des Live Components
  layouts/             # Layouts (base, main, referee)
  referee/             # Vues de l'espace arbitre
  game/                # Vues de la partie (joueur)
  security/            # Login
assets/
  controllers/         # Stimulus controllers
  controllers/component/ # Stimulus controllers pour composants UX
  icons/               # Icones SVG (Tabler, MDI, Symfony)
  sounds/              # Fichiers audio (timer)
```

## Conventions Git

- Messages de commit au format : `feat(scope): description` ou `chore(scope): description`
- **Une seule ligne** par message de commit : pas de corps ni de liste à puces
- **Jamais de trailer `Co-Authored-By`** (ni aucune autre ligne d'attribution) dans les commits
- Scopes courants : `game`, `bingo`, `referee`, `timer`, `datatable`, `template`, `env`, `config`, `migrations`

## Workflow Git

- **Tout se fait directement sur `main`** : pas de branche de travail par défaut
- Si une branche est exceptionnellement nécessaire : jamais de commit de merge (rebase sur `main` puis `git merge --ff-only`), et les fichiers de tâches `.backlog/` restent sur `main`

## Gestion des tâches (Backlog.md)

- Les tâches sont dans `.backlog/`, manipulées uniquement via `pnpm exec backlog` (jamais `pnpm dlx` ni `npx`)
- **Seule source de vérité : la branche `main`**. L'état des tâches ne se lit et ne se modifie que sur `main`, jamais sur une autre branche (`check_active_branches: false` dans `.backlog/config.yml`)
- **Au démarrage d'une tâche** : la passer en cours automatiquement, sans attendre de demande
  `pnpm exec backlog task edit <id> -s "In Progress"`
- **À la fin d'une tâche** (tests et lint passés) : cocher ses critères d'acceptation et la passer à terminée automatiquement
  `pnpm exec backlog task edit <id> --check-ac <n> -s "Done"`
- Les changements de fichiers `.backlog/` sont commités avec le code de la tâche

## Sécurité

- Tokens Mercure dans `.env.local` (jamais commité)
- Secrets Docker dans `compose.prod.yaml` via Docker secrets
- CSRF sur toutes les mutations (double-submit cookie côté Stimulus)
- Rôles : ADMIN > REFEREE > USER (hiérarchie dans `security.yaml`)
- Vérifier `docs/SECURITY.md` pour les bonnes pratiques

## Erreurs

- Pages d'erreur personnalisées dans `templates/bundles/TwigBundle/Exception/`
- Ne jamais exposer les détails d'erreur en production
