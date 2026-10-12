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
        Schema::create('kanban_comments', function (Blueprint $table) {
            $table->id();

            // Связи
            $table->foreignId('card_id')->constrained('kanban_cards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->nullOnDelete();

            // Контент
            $table->text('content');

            // Метаданные
            $table->boolean('is_edited')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['card_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_comments');
    }
};
