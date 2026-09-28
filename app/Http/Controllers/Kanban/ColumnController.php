<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateColumnAction;
use App\DTO\Kanban\CreateColumnDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\ColumnOrderRequest;
use App\Http\Requests\Kanban\StoreColumnRequest;
use App\Models\Kanban\Board;
use App\Models\Kanban\Column;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ColumnController extends Controller
{
    public function __construct(
        private CreateColumnAction $createColumnAction
    ) {}

    public function store(StoreColumnRequest $request, Board $board): RedirectResponse
    {
        $maxOrder = $board->columns->max('order') ?? 0;

        $dto = new CreateColumnDTO(
            title: $request->validated('title'),
            color: $request->validated('color'),
            wipLimit: $request->validated('wip_limmit'),
            boardId: $board->id,
            order: $maxOrder + 100,
        );

        $this->createColumnAction->execute($dto);

        return redirect()->back()->with('success', 'Колонка успешно создана');
    }

    public function orders(ColumnOrderRequest $request)
    {
        $orders = $request->validated('ids');

        // Колонок не много, можно обновлять в цикле
        try {
            DB::transaction(function () use ($orders) {
                foreach ($orders as $index => $id) {
                    Column::find($id)?->update(['ord' => ($index + 1) * 100]);
                }
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Порядок обновлен'
            ]);
        } catch (\Throwable $e) {
            // Любая ошибка внутри транзакции приведет к откату
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
