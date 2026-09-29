<script setup>
import { useBoardStore } from '@/Stores/board'
import { computed, ref, watch, watchEffect } from 'vue'
import BoardHeader from './BoardHeader.vue'
import Column from './Column.vue'
import AddColumnButton from './AddColumnButton.vue'
import CreateColumnModal from './CreateColumnModal.vue'
import { useDraggable } from 'vue-draggable-plus'

const props = defineProps({
  board: {
    type: Array,
    required: true
  },
})

const showCreateColumnModal = ref(false)

const store = useBoardStore()
const columns = ref([])
watchEffect(() => {
  // Важно: создаем новую копию массива, чтобы разорвать ссылку
  // и SortableJS мутировал локальный ref, а не массив из стора
  columns.value = [...store.sortedColumns] 
})

//------------------------
const boardRef = ref(null)
useDraggable(boardRef, columns, {
  group: 'columns',
  animation: 150,
  handle: '.column-handle', // Тянуть можно только за заголовок колонки
  // --- ДОБАВЛЕННЫЕ ОПЦИИ ---
  forceFallback: true,              // Включаем кастомный рендеринг перетаскивания
  ghostClass: 'ghost-column',       // Класс для области, куда "упадет" колонка
  chosenClass: 'chosen-column',     // Класс для колонки, которую начали тянуть
  dragClass: 'drag-column',         // Класс для самого перемещаемого элемента (cursor)
  fallbackClass: 'fallback-column',
  // onStart : (event) => {    
  // },
  onEnd: async (event) => {
    const { oldIndex, newIndex } = event
    if (oldIndex === newIndex) return

    const orderedColumnIds = columns.value.map(col => col.id)   

    try {
      const response = await store.saveColumnsOrder(orderedColumnIds)
      if (response.status === 200) {
        toast.success('Порядок успешно обновлен')
      }
    } catch (error) {
      toast.error('Что-то пошло не так.')
      columns.value = [...store.sortedColumns]
    }
    
  }
})

/**
 * Обработка окончания перемещения карточки (вызывается из дочерней колонки)
 */
const onCardDragEnd = (event, sourceColumnId) => {
  const { oldIndex, newIndex, to, from } = event
  
  // Если переместили внутри одной колонки и на то же место
  if (from === to && oldIndex === newIndex) return

  console.log('sourceColumnID ', sourceColumnId);
  const targetColumnId = event.to.dataset.columnId
  console.log('targetColumnId ', targetColumnId)
  
  // Находим карточку в обновленной структуре данных
  // Чтобы понять, в какую колонку ее бросили, ищем по DOM-структуре или сопоставляем данные
  // console.log('Карточка успешно перемещена. Актуальное состояние данных:', boardData.value)
  
  // Отправка на API: saveTaskPosition(...)
}

/**
 * Явный обработчик события update:tasks
 * Вызывается из дочернего компонента при любом изменении массива задач
 * (перетаскивание внутри колонки или перенос из одной в другую)
 */
const onUpdateCards = (columnId, newCards) => {
  // 1. Находим нужную колонку в реактивных данных
  const targetColumn = columns.value.find(col => col.id === columnId)
  
  if (targetColumn) {
    // 2. Обновляем данные в родительском состоянии
    targetColumn.cards = newCards
    
    console.log(`[update:cards] В колонке "${targetColumn.title}" теперь задач: ${newCards.length}`)
    console.log('targetColumn new cards: ', newCards)
    
    
    // 3. Здесь можно добавить логику, которая должна сработать сразу при обновлении массива.
    // Например, autosave (автосохранение) с debounce.
  }
}

//------------------------

watch(() => props.board, (newBoard) => {
  if (newBoard) {
    store.setBoardData(newBoard, newBoard.columns?.data || [])
  }
}, { immediate: true })

const openCardDetail = (card) => {
  store.selectCard(card)
}

const applyFilters = (filters) => {
  store.setFilters(filters)
}
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- Header доски -->
    <BoardHeader 
      :board="board"
      @filter-change="applyFilters"
      @add-column="showCreateColumnModal = true"
    />
    
    <!-- Холст с колонками -->
    <div 
      class="flex-1 overflow-x-auto overflow-y-hidden p-6"
    >
      <div
        class="flex h-full gap-4"
        ref="boardRef"
      >
        <Column
          v-for="column in columns"
          :key="column.id"
          :column="column"
          :cards="column.cards"
          @update:cards="(newCards) => onUpdateCards(column.id, newCards)"
          @card-click="openCardDetail"
          @card-drag-end="(event) => onCardDragEnd(event, column.id)"
        />
        
        <!-- Кнопка добавления колонки -->
        <AddColumnButton @click="showAddColumnModal" />
      </div>
    </div>

    <CreateColumnModal
      v-model="showCreateColumnModal"
      :board-id="board.id"
      @created="handleColumnCreated"
    />
  </div>
</template>

<style scoped>
/* Если стили scoped, используем :deep() */
:deep(.ghost-column) {
  background: rgba(59, 130, 246, 0.1); /* Полупрозрачный синий фон */
  border: 2px dashed #3b82f6;          /* Пунктирная граница */
  opacity: 0.7 !important;
  border-radius: 8px;
}

:deep(.chosen-column) {
  cursor: grabbing;
  opacity: 0.9;
}

/* Стиль для элемента, который висит под курсором мыши */
:deep(.drag-column),
:deep(.fallback-column) {
  background: #ffffff !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  transform: rotate(2deg); /* Легкий наклон для эффекта "взял карточку" */
  opacity: 1 !important;
  cursor: grabbing !important;
}
</style>