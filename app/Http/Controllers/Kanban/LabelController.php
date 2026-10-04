<?php

namespace App\Http\Controllers\Kanban;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreLabelRequest;
use App\Models\Kanban\Workspace;
use Illuminate\Support\Facades\Gate;

class LabelController extends Controller
{
    public function store(StoreLabelRequest $request, Workspace $workspace)
    {
        Gate::authorize('update', $workspace);

        $label = $workspace->labels()->create($request->validated());

        // Возвращаем назад с созданной меткой
        return back()->with('createdLabel', [
            'id' => $label->id,
            'name' => $label->name,
            'color' => $label->color,
            'description' => $label->description,
        ]);
    }
}
