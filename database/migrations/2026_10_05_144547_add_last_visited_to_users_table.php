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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('last_visited_workspace_id')
                ->nullable()
                ->constrained('kanban_workspaces')
                ->nullOnDelete();

            $table->foreignId('last_visited_board_id')
                ->nullable()
                ->constrained('kanban_boards')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['last_visited_workspace_id']);
            $table->dropForeign(['last_visited_board_id']);
            $table->dropColumn(['last_visited_workspace_id', 'last_visited_board_id']);
        });
    }
};
