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
        Schema::create('kanban_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('kanban_workspaces')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 7);
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            // Уникальность имени метки в рамках воркспейса
            $table->unique(['workspace_id', 'name']);
            $table->index('workspace_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_labels');
    }
};
