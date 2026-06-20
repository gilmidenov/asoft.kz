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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            // Чья корзина. nullable() — гость без авторизации тоже может иметь корзину
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Идентификатор сессии для гостей (без авторизации)
            $table->string('session_id')->nullable();

            // Какой товар
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Какая именно лицензия (тип/вариант товара)
            $table->foreignId('product_license_id')->constrained()->onDelete('cascade');

            // Количество (для ПО обычно 1, но для корпоративных заказов может быть больше)
            $table->unsignedInteger('quantity')->default(1);

            $table->timestamps();

            // Уникальная пара: один пользователь не может добавить одну и ту же лицензию дважды
            // unique(['user_id', 'product_license_id']) — составной уникальный индекс
            $table->unique(['user_id', 'product_license_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
