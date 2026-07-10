#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
        composer install --prefer-dist --no-progress --no-interaction
    fi

    if grep -q ^DATABASE_URL= .env; then
        echo "Waiting for database to be ready..."
        ATTEMPTS_LEFT_TO_REACH_DATABASE=60
        until [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ] || DATABASE_ERROR=$(php bin/console dbal:run-sql -q "SELECT 1" 2>&1); do
            if [ $? -eq 255 ]; then
                # If the Doctrine command exits with 255, an unrecoverable error occurred
                ATTEMPTS_LEFT_TO_REACH_DATABASE=0
                break
            fi
            sleep 1
            ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
            echo "Still waiting for database to be ready... Or maybe the database is not reachable. $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left."
        done

        if [ $ATTEMPTS_LEFT_TO_REACH_DATABASE -eq 0 ]; then
            echo "The database is not up or not reachable:"
            echo "$DATABASE_ERROR"
            exit 1
        else
            echo "The database is now ready and reachable"
        fi

        if [ "$APP_ENV" = 'prod' ]; then
            if [ "$(find ./migrations -iname '*.php' -print -quit)" ]; then
                php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
            fi
        elif [ "$APP_ENV" = 'dev' ]; then
            if [ ! -f /tmp/dbinit/installed ] && [ "$(find ./src/DataFixtures -iname '*.php' -print -quit)" ]; then
                php bin/console doctrine:schema:create
                php bin/console doctrine:fixtures:load -n
                touch /tmp/dbinit/installed
            fi
        fi
    fi

    if [ "$APP_ENV" = 'dev' ]; then
        php bin/console ux:icons:lock
    fi

    setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var
    setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var
fi

exec docker-php-entrypoint "$@"
