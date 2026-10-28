<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // В enum CardPriority::High значение было с опечаткой 'hight'.
        // Приводим уже сохранённые строки к исправленному 'high', иначе
        // каст priority => CardPriority::class падает при чтении карточки.
        DB::table('kanban_cards')
            ->where('priority', 'hight')
            ->update(['priority' => 'high']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('kanban_cards')
            ->where('priority', 'high')
            ->update(['priority' => 'hight']);
    }
};
