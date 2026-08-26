<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refactorisation : le Booking Service ne gère plus les prestataires localement.
 * Ces données appartiennent désormais au Provider Service (port 8002).
 *
 * On supprime :
 *  - les clés étrangères de bookings vers providers et services
 *  - les tables redondantes : schedule_exceptions, schedules, services, providers, categories
 *
 * La table bookings est conservée avec provider_id et service_id comme
 * simples entiers (références externes, pas de FK locale).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Supprimer les FK sur bookings (provider_id, service_id)
        Schema::table('bookings', function (Blueprint $table) {
            // PostgreSQL nomme les FK : {table}_{column}_foreign
            if ($this->foreignExists('bookings', 'bookings_provider_id_foreign')) {
                $table->dropForeign('bookings_provider_id_foreign');
            }
            if ($this->foreignExists('bookings', 'bookings_service_id_foreign')) {
                $table->dropForeign('bookings_service_id_foreign');
            }
        });

        // 2. Supprimer les tables dans l'ordre (dépendances d'abord)
        Schema::dropIfExists('schedule_exceptions');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('services');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        // Recréation minimale pour rollback (sans données)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('duration_minutes')->default(60);
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week');
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });

        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_closed')->default(true);
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->timestamps();
        });

        // Rétablir les FK sur bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('provider_id')->references('id')->on('providers')->restrictOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
        });
    }

    /** Vérifie si une contrainte FK existe dans pg_constraint */
    private function foreignExists(string $table, string $constraintName): bool
    {
        $result = \DB::selectOne(
            "SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_type = 'FOREIGN KEY'
               AND table_name = ?
               AND constraint_name = ?",
            [$table, $constraintName]
        );
        return $result !== null;
    }
};
