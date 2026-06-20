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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // Сохраняем ссылку на товар, но nullable — товар может быть удалён позже
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');

            // Копируем данные на момент заказа! Цены и названия могут меняться,
            // но заказ должен хранить то, что было куплено
            $table->string('product_name');            // Копия названия товара
            $table->string('license_name');            // Копия названия лицензии
            $table->decimal('price', 10, 2);           // Цена на момент покупки
            $table->unsignedInteger('quantity')->default(1);

            // Лицензионный ключ — выдаётся после оплаты
            $table->text('license_key')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
