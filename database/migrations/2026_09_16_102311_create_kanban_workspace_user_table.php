<?php

use App\Enums\Kanban\WorkspaceRole;
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
        Schema::create('kanban_workspace_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('kanban_workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default(WorkspaceRole::Member->value);
            $table->string('invite_token', 64)->nullable()->unique();
            $table->timestamp('invaited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
            $table->index(['invite_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_workspace_user');
    }
};
