<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateColumnAction;
use App\DTO\Kanban\CreateColumnDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\StoreColumnRequest;
use App\Models\Kanban\Board;
use Illuminate\Http\RedirectResponse;

class ColumnController extends Controller
{
    public function __construct(
        private CreateColumnAction $createColumnAction
    ) {}

    public function store(StoreColumnRequest $request, Board $board): RedirectResponse
    {
        $maxOrder = $board->columns->max('order') ?? 0;

        $dto = new CreateColumnDTO(
            title: $request->validated('tilte'),
            color: $request->validated('color'),
            wipLimit: $request->validated('wip_limmit'),
            boardId: $board->id,
            order: $maxOrder + 100,
        );

        $this->createColumnAction->execute($dto);

        return redirect()->back()->with('success', 'Колонка успешно создана');
    }
}
