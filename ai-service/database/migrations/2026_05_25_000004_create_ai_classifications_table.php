<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_classifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->json('result');
            $table->float('confidence')->nullable();
            $table->boolean('is_applied')->default(false);
            $table->timestamps();

            $table->index('provider_id');
            $table->index('is_applied');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_classifications');
    }
};
