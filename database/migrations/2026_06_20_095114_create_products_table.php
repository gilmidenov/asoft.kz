<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->longText('description')->nullable();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            $table->string('version')->nullable();
            $table->string('language')->nullable();
            $table->enum('delivery_type', ['download', 'box', 'key'])->default('key');
            $table->string('main_image')->nullable();
            $table->decimal('price_from', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');

            $table->boolean('is_hit')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_sale')->default(false);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            // Счётчик просмотров
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
