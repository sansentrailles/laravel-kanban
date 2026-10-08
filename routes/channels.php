<?php

use App\Models\Kanban\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('board.{workspaceId}', function (User $user, int $workspaceId) {
    // Проверяем, является ли пользователь участником этого воркспейса
    $workspace = Workspace::find($workspaceId);

    return $workspace ? $workspace->hasMember($user) : false;
});
