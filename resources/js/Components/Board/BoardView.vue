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

// 2. ОСНОВНАЯ ЛОГИКА: Отправка на API после дропа
const onCardDragEnd = async (event) => {
  const { item, to, from, oldIndex, newIndex } = event

  // 1. ID перемещенной карточки (с атрибута на <Card>)
  const movedCardId = item?.dataset?.cardId
  if (!movedCardId) return

  // 2. ID целевой колонки (с атрибута на контейнере списка в Column.vue)
  const targetColumnId = to?.dataset?.columnId
  if (!targetColumnId) return

  // 3. Если ничего не изменилось (клик без движения или возврат на место)
  if (from === to && oldIndex === newIndex) return

  // 4. Берем АКТУАЛЬНЫЙ порядок карточек в ЦЕЛЕВОЙ колонке из реактивного состояния
  // На этот момент onUpdateCards уже обновил columns.value
  const targetColumn = columns.value.find(col => col.id === targetColumnId)
  if (!targetColumn) {
    console.error('Target column not found in state', targetColumnId)
    columns.value = [...store.sortedColumns] // фоллбэк
    return
  }

  const orderedCardIds = targetColumn.cards.map(c => c.id)

  try {
    if (from !== to) {
      // --- МЕЖКОЛОНОЧНОЕ перемещение ---
      const sourceColumnId = from.dataset.columnId
      // Пример вызова стора: передаем ID карточки, откуда, куда, и новый порядок в целевой
      // await store.moveCardBetweenColumns(movedCardId, sourceColumnId, targetColumnId, orderedCardIds)
    } else {
      // --- ПЕРЕСОРТИРОВКА ВНУТРИ ОДНОЙ КОЛОНКИ ---
      // await store.saveCardsOrder(targetColumnId, orderedCardIds)
    }
    toast.success('Порядок сохранен')
  } catch (error) {
    toast.error('Ошибка сохранения позиции')
    // Откат UI к серверному состоянию
    columns.value = [...store.sortedColumns]
  }
}

/**
 * Явный обработчик события update:tasks
 * Вызывается из дочернего компонента при любом изменении массива задач
 * (перетаскивание внутри колонки или перенос из одной в другую)
 */
const onUpdateCards = (columnId, newCards) => {
  // 1. Находим нужную колонку в реактивных данных
  const targetColumn = columns.value.find(col => col.id === columnId)
  console.log(targetColumn?.title)
  
  if (targetColumn) {
    // 2. Обновляем данные в родительском состоянии
    targetColumn.cards = newCards
    
    console.log(`[update:cards] В колонке "${targetColumn.title}" теперь задач: ${newCards.length}`)
    // console.log('targetColumn new cards: ', newCards)

    console.log('=== CARDS ===')
    newCards.forEach(card => console.log(card.title))
    
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
          :data-column-id="column.id"
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