#!/bin/bash
set -e

# Wait for the database to be ready if it's MySQL/MariaDB
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Waiting for MySQL database to be ready..."
    until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" --silent; do
        sleep 2
    done
    echo "Database is ready!"
fi

if [ "$1" = "apache2-foreground" ]; then
    echo "Creating storage symlink..."
    php artisan storage:link || true

    echo "Running database migrations..."
    php artisan migrate --force
fi

echo "Starting original command: $@"
exec "$@"
