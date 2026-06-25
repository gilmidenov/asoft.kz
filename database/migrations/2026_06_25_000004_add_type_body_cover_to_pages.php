<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->enum('type', ['catalog', 'section'])->default('catalog')->after('slug');
            $table->text('body')->nullable()->after('description');
            $table->string('cover_image', 500)->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['type', 'body', 'cover_image']);
        });
    }
};
