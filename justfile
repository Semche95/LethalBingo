set shell := ["bash", "-eu", "-o", "pipefail", "-c"]

# ==============================
# Justfile LC Bingo (développement)
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

# Installe les dépendances npm (privilégie npm ci si un lockfile est présent)
install:
    if [ -f package-lock.json ]; then npm ci; else npm install; fi

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

# Exécute les migrations en base
migrate:
    @just sf doctrine:migrations:migrate --no-interaction

# Génère une migration à partir des entités
diff:
    @just sf doctrine:migrations:diff

# Recharge les fixtures (si le bundle fixtures est installé)
fixtures:
    @just sf doctrine:fixtures:load --no-interaction --purge-with-truncate

# Lance les conteneurs Docker puis le serveur de dev (Vite) côté frontend
dev: up
    @npm run dev

# Build des assets pour la production
build-assets:
    @npm run build

# Affiche l'aide de ce justfile
help:
    @just --list
