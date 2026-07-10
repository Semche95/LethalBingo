# Executables (local)
dockercomp := "docker compose"

# Docker containers
phpcont := dockercomp + " exec php"

# Executables
php      := phpcont + " php"
composer := "composer"
symfony  := php + " bin/console"

default:
	@just --list

# npm
install:
	@npm install

assets:
	@npm run build

dev: install up
	@npm run dev

# Docker
build: ## Builds the Docker images
	@COMPOSE_FILE=compose.yaml:compose.prod.yaml {{dockercomp}} build --pull --no-cache

up *cmd='': ## Start the docker hub in detached mode (no logs)
	@{{dockercomp}} up --build --detach {{cmd}}

down *cmd='': ## Stop the docker hub
	@{{dockercomp}} down --remove-orphans {{cmd}}

logs: ## Show live logs
	@{{dockercomp}} logs --tail=0 --follow

sh: ## Connect to the FrankenPHP container
	@{{phpcont}} sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	@{{phpcont}} bash

test *cmd='': ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
	@{{dockercomp}} exec -e APP_ENV=test php bin/phpunit {{cmd}}

prod: install vendor assets && build
	#!/usr/bin/env bash
	set -euo pipefail
	CADDY_MERCURE_JWT_SECRET=$(openssl rand -hex 32)
	echo 'APP_ENV=prod' > .env.prod.local
	echo 'APP_DEBUG=0' >> .env.prod.local
	echo 'APP_SECRET=$(openssl rand -hex 32)' >> .env.prod.local
	echo 'CADDY_MERCURE_JWT_SECRET=$(openssl rand -hex 32)' >> .env.prod.local
	echo 'POSTGRES_PASSWORD=$(openssl rand -hex 16)' >> .env.prod.local
	echo 'SERVER_NAME=bingo.semche.ovh' >> .env.prod.local
	echo 'DATABASE_URL="postgresql://app:%env(POSTGRES_PASSWORD)%@database:5432/app?serverVersion=16&charset=utf8"' >> .env.prod.local
	echo 'MERCURE_PUBLISHER_JWT_KEY=$(CADDY_MERCURE_JWT_SECRET)' >> .env.prod.local
	echo 'MERCURE_SUBSCRIBER_JWT_KEY=$(CADDY_MERCURE_JWT_SECRET)' >> .env.prod.local

## Composer
composer *cmd='': ## Run composer
	@{{composer}} {{cmd}}

vendor: && (composer 'install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction') ## Install vendors according to the current composer.lock file

# Symfony
sf *cmd='': ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@{{symfony}} {{cmd}}

cc: && (sf 'c:c') ## Clear the cache

migrate: && (sf '--no-interaction d:m:m')
