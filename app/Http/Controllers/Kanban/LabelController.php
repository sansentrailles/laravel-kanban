<?php

namespace App\Http\Controllers\Kanban;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreLabelRequest;
use App\Http\Resources\Kanban\LabelResource;
use App\Models\Kanban\Workspace;
use Illuminate\Support\Facades\Gate;

class LabelController extends Controller
{
    public function store(StoreLabelRequest $request, Workspace $workspace)
    {
        Gate::authorize('update', $workspace);

        $label = $workspace->labels()->create($request->validated());

        // Возвращаем назад с созданной меткой
        return response()->json([
            'createdLabel' => new LabelResource($label),
        ]);
    }
}
