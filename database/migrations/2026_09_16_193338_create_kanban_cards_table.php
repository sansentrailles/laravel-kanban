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
        Schema::create('kanban_cards', function (Blueprint $table) {
            $table->id();

            // Связи
            $table->foreignId('column_id')->constrained('kanban_columns')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Контент
            $table->string('title', 500);
            $table->text('description')->nullable();

            // Сортировка (Fractional Indexing)
            // decimal(20, 10) дает огромную точность для вставки между любыми числами
            $table->decimal('order', 20, 10)->default(10);

            // Метаданные
            $table->string('priority')->default('medium');
            $table->date('due_date')->nullable();
            $table->date('start_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Гибкие настройки карточки
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['column_id', 'order']);
            $table->index('due_date');
        });

        // Pivot таблица для меток (Labels)
        Schema::create('kanban_card_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('kanban_cards')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('kanban_labels')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['card_id', 'label_id']);
        });

        // Pivot таблица для исполнителей (Assignees)
        Schema::create('kanban_card_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('kanban_cards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['card_id', 'user_id']);
        });            
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_card_user');
        Schema::dropIfExists('kanban_card_label');
        Schema::dropIfExists('kanban_cards');
    }
};
