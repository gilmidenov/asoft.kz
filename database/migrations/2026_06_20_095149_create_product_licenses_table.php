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
        Schema::create('product_licenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            // onDelete('cascade') — если товар удалён, все его лицензии тоже удаляются

            // Название варианта: "1 ПК", "5 ПК", "1 год / 1 ПК"
            $table->string('name');

            // Тип лицензии
            $table->enum('type', [
                'perpetual',    // Бессрочная
                'subscription', // Подписка
                'volume',       // Корпоративная (объёмная)
            ])->default('perpetual');

            // Количество устройств (1, 5, 10, unlimited)
            $table->string('devices')->nullable();

            // Срок действия: null = бессрочно, иначе количество месяцев
            $table->unsignedInteger('duration_months')->nullable();

            // Цена в тенге
            $table->decimal('price', 10, 2);

            // Старая цена (если есть скидка, показываем зачёркнутую)
            $table->decimal('old_price', 10, 2)->nullable();

            // Можно ли добавить в корзину или только "под запрос"
            $table->boolean('in_stock')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_licenses');
    }
};
