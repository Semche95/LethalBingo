set shell := ["bash", "-eu", "-o", "pipefail", "-c"]

# ==============================
# Justfile LethalBingo (développement)
# ------------------------------
# Raccourcis pour le workflow local décrit dans les guidelines.
# Astuce: `just` sans argument liste toutes les commandes.
# ==============================

# Cible par défaut: afficher la liste des commandes
# Permet à `just` (sans argument) de lister toutes les recettes disponibles
default: help

# Démarre les conteneurs Docker en arrière-plan
up *args:
    @docker compose up -d {{args}}

# Arrête et supprime les conteneurs, réseaux…
down *args:
    @docker compose down {{args}}

# Affiche les logs des services (ajoutez -f pour suivre en continu)
logs *args:
    @docker compose logs {{args}}

# Construit / reconstruit les images Docker
build *args:
    @docker compose build {{args}}

# Liste l'état des services
ps *args:
    @docker compose ps {{args}}

# Redémarre un ou plusieurs services
restart *args:
    @docker compose restart {{args}}

# Installe les dépendances pnpm
install:
    @pnpm install --frozen-lockfile

# Proxy pour exécuter des commandes Composer, ex: `just composer install`
composer +args:
    @composer {{args}}

# Ouvre un shell dans le conteneur PHP (busybox/ash ou sh)
sh:
    @docker compose exec php sh

# Ouvre un shell bash dans le conteneur PHP (si disponible)
bash:
    @docker compose exec php bash

# Proxy pour exécuter des commandes Symfony, ex: `just sf cache:clear`
sf *args:
    @docker compose exec php bin/console {{args}}

# Vide le cache Symfony
cc:
    @just sf cache:clear --no-warmup

# Exécute la suite de tests PHPUnit dans le conteneur PHP
test *args:
    @docker compose exec php ./vendor/bin/phpunit {{args}}

# Analyse statique du code avec PHPStan dans le conteneur PHP
phpstan *args:
    @docker compose exec php ./vendor/bin/phpstan analyse {{args}}

# Vérifie le style du code avec PHP-CS-Fixer (sans modifier, affiche le diff)
cs *args:
    @docker compose exec php ./vendor/bin/php-cs-fixer fix --dry-run --diff {{args}}

# Corrige le style du code avec PHP-CS-Fixer
cs-fix *args:
    @docker compose exec php ./vendor/bin/php-cs-fixer fix {{args}}

# Vérifie le code avec Rector (sans modifier, affiche le diff)
rector *args:
    @docker compose exec php ./vendor/bin/rector process --dry-run {{args}}

# Applique les corrections Rector (syntaxe moderne, dépréciations)
rector-fix *args:
    @docker compose exec php ./vendor/bin/rector process {{args}}

# Exécute les migrations en base
migrate:
    @just sf doctrine:migrations:migrate --no-interaction

# Génère une migration à partir des entités
diff:
    @just sf doctrine:migrations:diff

# Recharge les fixtures (si le bundle fixtures est installé)
fixtures:
    @just sf doctrine:fixtures:load --no-interaction --purge-with-truncate

# Exécute une requête SQL brute, ex: `just sql "SELECT * FROM game"`
sql sql:
    @just sf dbal:run-sql -- "{{quote(sql)}}"

# Lance les conteneurs Docker puis le serveur de dev (Vite) côté frontend
dev: up
    @pnpm run dev

# Build des assets pour la production
build-assets:
    @pnpm run build

# Affiche l'aide de ce justfile
help:
    @just --list
