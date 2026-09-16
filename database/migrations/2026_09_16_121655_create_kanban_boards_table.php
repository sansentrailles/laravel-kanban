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
        Schema::create('kanban_boards', function (Blueprint $table) {
            $table->id();

            // Связи
            $table->foreignId('workspace_id')->constrained('kanban_workspaces')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Идентификаторы
            $table->uuid('uuid')->unique()->nullable(); // для публичных ссылок

            // Контент
            $table->string('name', 255);
            $table->text('description')->nullable();

            // Визуал
            $table->string('color', 7)->nullable(); // HEX цвет
            $table->string('icon', 50)->nullable();

            // Сортировака и доступ
            $table->unsignedInteger('order')->default(0);
            $table->string('visibility')->default('workspace');

            // Настройки
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'order']);
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_boards');
    }
};
