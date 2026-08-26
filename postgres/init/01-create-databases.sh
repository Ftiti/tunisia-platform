#!/bin/bash
set -e

# Créer la base de données tunisia_providers si elle n'existe pas
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    SELECT 'CREATE DATABASE tunisia_providers'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'tunisia_providers')\gexec

    \c tunisia_providers

    -- Activer PostGIS sur la base providers
    CREATE EXTENSION IF NOT EXISTS postgis;
    CREATE EXTENSION IF NOT EXISTS postgis_topology;
EOSQL

echo "✅ Base tunisia_providers créée avec PostGIS"

# Créer la base de données tunisia_ai si elle n'existe pas
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    SELECT 'CREATE DATABASE tunisia_ai'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'tunisia_ai')\gexec
EOSQL

echo "✅ Base tunisia_ai créée"
