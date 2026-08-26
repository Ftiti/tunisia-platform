<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('providers', 'images')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('images');
            });
        }
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->json('images')->nullable()->after('is_featured');
        });
    }
};
