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
        Schema::create('kanban_columns', function (Blueprint $table) {
            $table->id();

            // Связи
            $table->foreignId('board_id')->constrained('kanban_boards')->cascadeOnDelete();

            // Контент и визуал
            $table->string('title', 255);
            $table->string('color', 7)->nullable(); // HEX цвет

            // Сортировка и состояние
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_hidden')->default(false);

            // Канбан-ограничения
            $table->unsignedInteger('wip_limit')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['board_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_columns');
    }
};
