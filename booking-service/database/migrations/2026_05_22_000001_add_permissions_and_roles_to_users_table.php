<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ajoute la colonne permissions + met à jour l'enum role pour inclure super_admin, manager, moderator
return new class extends Migration
{
    public function up(): void
    {
        // Mettre à jour la contrainte CHECK sur le rôle (PostgreSQL uniquement)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("
                ALTER TABLE users ADD CONSTRAINT users_role_check
                CHECK (role IN (
                    'super_admin', 'admin', 'manager', 'moderator',
                    'provider', 'client', 'delivery'
                ))
            ");
        }

        // Ajouter la colonne permissions seulement si elle n'existe pas
        if (! Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('permissions')->nullable()->after('role');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });

        DB::statement("
            ALTER TABLE users
            DROP CONSTRAINT IF EXISTS users_role_check
        ");

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_role_check
            CHECK (role IN ('admin', 'provider', 'client', 'delivery'))
        ");
    }
};
