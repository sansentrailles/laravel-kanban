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
        Schema::create('kanban_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('kanban_cards')->cascadeOnDelete();
            $table->string('title', 255);
            $table->unsignedInteger('order')->default(0);

            $table->timestamps();

            $table->index(['card_id', 'order']);
        });

        Schema::create('kanban_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('kanban_checklists')->cascadeOnDelete();
            $table->string('content', 500); // Текст задачи
            $table->boolean('is_completed')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['checklist_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_checklist_items');
        Schema::dropIfExists('kanban_checklists');
    }
};
