<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('platform_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_category_id')->nullable()->constrained('platform_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 3);
            $table->decimal('price_promo', 10, 3)->nullable();
            $table->integer('stock')->default(-1);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_promo')->default(false);
            $table->boolean('is_vedette')->default(false);
            $table->enum('delivery_type', ['national', 'local'])->default('national');
            $table->json('variants')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('platform_products');
    }
};
