<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('validation_status', ['pending', 'validated', 'rejected', 'suspended'])->default('pending')->after('is_active');
            $table->enum('provider_type', ['free', 'commerce'])->nullable()->after('validation_status');
            $table->text('rejection_reason')->nullable()->after('provider_type');
            $table->timestamp('validated_at')->nullable()->after('rejection_reason');
            $table->json('settings')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['validation_status', 'provider_type', 'rejection_reason', 'validated_at', 'settings']);
        });
    }
};
