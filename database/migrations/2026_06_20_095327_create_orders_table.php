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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Уникальный номер заказа: "ORD-2024-000001"
            $table->string('order_number')->unique();

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Статус заказа
            $table->enum('status', [
                'pending',    // Ожидает оплаты
                'paid',       // Оплачен
                'processing', // В обработке
                'completed',  // Выполнен (ключ выдан)
                'cancelled',  // Отменён
                'refunded',   // Возврат
            ])->default('pending');

            // Контактные данные (копируем на момент заказа, т.к. пользователь может изменить их)
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();

            // Финансы
            $table->decimal('subtotal', 10, 2);  // Сумма без скидок
            $table->decimal('discount', 10, 2)->default(0);  // Сумма скидки
            $table->decimal('total', 10, 2);     // Итоговая сумма

            // Комментарий покупателя к заказу
            $table->text('comment')->nullable();

            // Комментарий менеджера (внутренний)
            $table->text('admin_comment')->nullable();

            // Когда заказ был оплачен
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
