<?php

namespace App\Http\Controllers\Kanban;

use App\Actions\Kanban\CreateColumnAction;
use App\Actions\Kanban\UpdateColumnAction;
use App\DTO\Kanban\CreateColumnDTO;
use App\DTO\Kanban\UpdateColumnDTO;
use App\Events\Kanban\ColumnReordered;
use App\Events\Kanban\ColumnUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kanban\ColumnOrderRequest;
use App\Http\Requests\Kanban\StoreColumnRequest;
use App\Http\Requests\Kanban\UpdateColumnRequest;
use App\Http\Resources\Kanban\ColumnResource;
use App\Models\Kanban\Board;
use App\Models\Kanban\Column;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ColumnController extends Controller
{
    public function __construct(
        private CreateColumnAction $createColumnAction,
        private UpdateColumnAction $updateColumnAction,
    ) {}

    public function store(StoreColumnRequest $request, Board $board): RedirectResponse
    {
        $maxOrder = $board->columns->max('ord') ?? 0;

        $dto = new CreateColumnDTO(
            title: $request->validated('title'),
            color: $request->validated('color'),
            wipLimit: $request->validated('wip_limit'),
            boardId: $board->id,
            order: $maxOrder + 100,
        );

        $this->createColumnAction->execute($dto);

        return redirect()->back()->with('success', 'Колонка успешно создана');
    }

    /**
     * Частичное обновление колонки (сейчас — смена цвета).
     * Оповещаем других клиентов по realtime, чтобы полоска цвета
     * у них перекрасилась без перезагрузки доски.
     */
    public function update(UpdateColumnRequest $request, Column $column)
    {
        $updatedColumn = $this->updateColumnAction->execute(
            $column,
            UpdateColumnDTO::fromRequest($request)
        );

        event(new ColumnUpdated($updatedColumn));

        return response()->json([
            'success' => true,
            'message' => 'Колонка обновлена',
            'column' => ColumnResource::make($updatedColumn)->resolve(),
        ]);
    }

    public function orders(ColumnOrderRequest $request, Board $board)
    {
        $orders = $request->validated('ids');

        // Колонок не много, можно обновлять в цикле
        try {
            DB::transaction(function () use ($orders) {
                foreach ($orders as $index => $id) {
                    Column::find($id)?->update(['ord' => ($index + 1) * 100]);
                }
            });

            $board->load(['columns']);
            broadcast(new ColumnReordered($board))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Порядок обновлен',
            ]);
        } catch (\Throwable $e) {
            // Любая ошибка внутри транзакции приведет к откату
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
