#!/bin/bash
set -e

echo "🚀 Démarrage Auth Service..."

# Vider le cache config pour utiliser les vraies variables d'environnement
php artisan config:clear > /dev/null 2>&1 || true
php artisan cache:clear  > /dev/null 2>&1 || true

# Attendre que PostgreSQL soit prêt
echo "⏳ Attente PostgreSQL..."
until php artisan migrate --pretend > /dev/null 2>&1; do
    echo "PostgreSQL pas encore prêt, attente..."
    sleep 3
done
echo "✅ PostgreSQL prêt !"

# Migrations
echo "🗄️ Migrations..."
php artisan migrate --force

# Seeder Admin
echo "👤 Vérification admin..."
php artisan db:seed --class=AdminSeeder --force 2>/dev/null || true

echo "✅ Auth Service prêt !"

exec php artisan serve --host=0.0.0.0 --port=8000
