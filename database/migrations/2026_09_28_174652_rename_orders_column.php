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
        Schema::table('kanban_boards', function (Blueprint $table) {
            $table->renameColumn('order', 'ord');
        });

        Schema::table('kanban_columns', function (Blueprint $table) {
            $table->renameColumn('order', 'ord');
        });

        Schema::table('kanban_labels', function (Blueprint $table) {
            $table->renameColumn('order', 'ord');
        });

        Schema::table('kanban_cards', function (Blueprint $table) {
            $table->renameColumn('order', 'ord');
        });

        Schema::table('kanban_checklists', function (Blueprint $table) {
            $table->renameColumn('order', 'ord');
        });

        Schema::table('kanban_checklist_items', function (Blueprint $table) {
            $table->renameColumn('order', 'ord');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kanban_boards', function (Blueprint $table) {
            $table->renameColumn('ord', 'order');
        });

        Schema::table('kanban_columns', function (Blueprint $table) {
            $table->renameColumn('ord', 'order');
        });

        Schema::table('kanban_labels', function (Blueprint $table) {
            $table->renameColumn('ord', 'order');
        });

        Schema::table('kanban_cards', function (Blueprint $table) {
            $table->renameColumn('ord', 'order');
        });

        Schema::table('kanban_checklists', function (Blueprint $table) {
            $table->renameColumn('ord', 'order');
        });

        Schema::table('kanban_checklist_items', function (Blueprint $table) {
            $table->renameColumn('ord', 'order');
        });
    }
};
