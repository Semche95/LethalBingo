# AGENTS.md — Règles spécifiques au projet LethalBingo

## Commandes obligatoires avant chaque commit

```bash
just test                    # Tests PHPUnit dans le conteneur Docker
just sf lint:container       # Vérification du conteneur de services
```

Si la commande de lint n'existe pas, utiliser :
```bash
just sf debug:container --isValid
```

## Prérequis

- PHP 8.4+
- Node.js + npm
- Docker + Docker Compose
- just (task runner)

## Démarrage du dev

```bash
just dev        # Démarre Docker + Vite dev server
just up         # Uniquement les conteneurs Docker
```

## Conventions de code PHP

- **PHP 8.4+ obligatoire** : constructor promotion, named arguments, union/intersection types, match expressions, nullsafe operator, readonly properties, enums, first-class callable syntax
- **Pas de code déprécié** : toujours utiliser les méthodes/fonctions actuelles
- **Attributs PHP** pour tout : routing, validation, ORM, security, services (jamais les annotations)
- **Pas d'appels entre controllers** : extraire la logique partagée dans des Services
- **Factoriser** le code dupliqué agressivement (DRY)
- **Early returns** préférés aux conditions imbriquées profondément
- **Pas de variables globales** sauf nécessité absolue
- **Pas de parenthèses** autour des `new` quand on chaîne des méthodes : `$var = new ClassName()->method()`

## Conventions de code TypeScript / Frontend

- **Vite** pour le build des assets
- **Bootstrap 5.3 + Tabler** pour le styling
- **Hotwired Turbo + Stimulus** pour l'interactivité
- **Symfony UX Live Components** pour les composants serveur interactifs
- **Pas de `console.log`** dans le code produit
- **Pas de JavaScript inline** dans les templates Twig — tout dans les fichiers JS/Stimulus
- **Toujours appeler `removeEventListener`** quand on utilise `addEventListener` (même référence bindée dans `connect()` / `disconnect()`)

## Conventions Templates (Twig)

- **Pas de style inline** (`style="..."`) — utiliser les classes CSS Bootstrap/Tabler ou créer des classes dédiées dans `app.css`
- Utiliser les macros Twig partagées (`macros.html.twig`)
- Les composants Live Components ont leur template dans `templates/components/`

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
- Scopes courants : `game`, `bingo`, `referee`, `timer`, `datatable`, `template`, `env`, `config`, `migrations`

## Sécurité

- Tokens Mercure dans `.env.local` (jamais commité)
- Secrets Docker dans `compose.prod.yaml` via Docker secrets
- CSRF sur toutes les mutations (double-submit cookie côté Stimulus)
- Rôles : ADMIN > REFEREE > USER (hiérarchie dans `security.yaml`)
- Vérifier `docs/SECURITY.md` pour les bonnes pratiques

## Erreurs

- Pages d'erreur personnalisées dans `templates/bundles/TwigBundle/Exception/`
- Ne jamais exposer les détails d'erreur en production

## Règles du jeu (contexte métier)

Le bingo Lethal Company suit ces règles :
- Pas de mods, vanilla uniquement
- Pas de remake (sauf crash au démarrage)
- Pas de communication hors jeu
- Chaque jour (sauf vente), tuer l'admin avec une pelle et la laisser sur le vaisseau
- Timer par défaut : 1h, countdown avec pause possible
- Grille 5×5 avec catégories de cases
- Workflow : case visible → dévoilée → validée/invalidée
- Scoring prévu : jours max (x100), argent terminal (x1), temps restant 1min = 20 points
