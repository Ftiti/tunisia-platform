<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('providers', function (Blueprint $table) {
            $table->enum('validation_status', ['pending', 'validated', 'rejected', 'suspended'])->default('validated')->after('is_featured');
            $table->enum('type', ['free', 'commerce'])->default('free')->after('validation_status');
            $table->enum('delivery_type', ['none', 'local', 'national'])->default('none')->after('type');
            $table->unsignedSmallInteger('delivery_radius_km')->default(0)->after('delivery_type');
            $table->json('delivery_cities')->nullable()->after('delivery_radius_km');
            $table->decimal('commission_rate', 5, 2)->default(0)->after('delivery_cities');
            $table->string('cover_image')->nullable()->after('commission_rate');
        });
    }

    public function down(): void {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['validation_status', 'type', 'delivery_type', 'delivery_radius_km', 'delivery_cities', 'commission_rate', 'cover_image']);
        });
    }
};
