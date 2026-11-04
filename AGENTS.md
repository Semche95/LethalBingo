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

## Règles par sujet

Avant de modifier un fichier, lire la ou les règles de `.claude/rules/` qui couvrent son chemin :

| Règle | Chemins |
|---|---|
| `.claude/rules/php.md` | `src/`, `tests/`, `config/`, `migrations/` |
| `.claude/rules/symfony.md` | `src/`, `tests/`, `config/`, `composer.json` |
| `.claude/rules/database.md` | `src/Entity/`, `src/Repository/`, `src/DataFixtures/`, `migrations/` |
| `.claude/rules/tests.md` | `tests/`, `src/Service/`, `src/Controller/`, `src/Repository/` |
| `.claude/rules/twig.md` | `templates/`, `src/Twig/`, `src/Form/` |
| `.claude/rules/ui-ux.md` | `templates/`, `assets/` |
| `.claude/rules/frontend.md` | `assets/`, `vite.config.ts`, `vitest.config.ts`, `package.json` |

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
- Le temps qui arrive à zéro ne termine jamais la partie : l'arrêt dévoile toutes les cases aux joueurs, seul l'arbitre le décide. À zéro, la partie reste en cours et l'arbitre peut encore remettre du temps.
- À zéro, le décompte s'arrête : le temps restant n'est jamais négatif, et le temps passé à zéro n'est ni du temps de jeu ni une pause. Les temps forts sont datés depuis le lancement, pauses et temps passé à zéro compris. Détail dans `docs/TIMER.md`, section 3.

## Sécurité et erreurs

- Tokens Mercure dans `.env.local` (jamais commité), secrets Docker dans `compose.prod.yaml`.
- CSRF sur toutes les mutations (double-submit cookie côté Stimulus).
- Rôles : ADMIN > REFEREE > USER (`security.yaml`). Voir `docs/SECURITY.md`.
- Pages d'erreur dans `templates/bundles/TwigBundle/Exception/`, aucun détail d'erreur en prod.
