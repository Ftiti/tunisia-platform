#!/bin/bash
set -e

echo "Démarrage Provider Service..."

php artisan config:clear > /dev/null 2>&1 || true
php artisan cache:clear  > /dev/null 2>&1 || true

echo "Attente PostgreSQL..."
until php artisan migrate --pretend > /dev/null 2>&1; do
    echo "PostgreSQL pas encore prêt, attente..."
    sleep 3
done
echo "PostgreSQL prêt !"

echo "Migrations..."
php artisan migrate --force

echo "Provider Service prêt !"

exec php artisan serve --host=0.0.0.0 --port=8002
