<!-- Components/Board/BoardView.vue -->
<script setup>
import { useBoardStore } from '@/Stores/board'
import { usePage } from "@inertiajs/vue3"
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import BoardHeader from './BoardHeader.vue'
import Column from './Column.vue'
import AddColumnButton from './AddColumnButton.vue'
import CreateColumnModal from './CreateColumnModal.vue'
import { useDraggable } from 'vue-draggable-plus'

const props = defineProps({
  board: { type: Object, required: true } // ИСПРАВЛЕНО: board это Object, а не Array
})

const store = useBoardStore()
const page = usePage()
const workspaceId = computed(() => page.props.currentWorkspace?.id)

// Инициализация данных доски
watch(() => props.board, (newBoard) => {
  if (newBoard) store.setBoardData(newBoard)
}, { immediate: true })

// Realtime
onMounted(() => store.connectRealtime(workspaceId.value))
onUnmounted(() => store.disconnectRealtime(workspaceId.value))

// --- Drag & Drop Колонок ---
const boardRef = ref(null)
useDraggable(boardRef, store.columns, { // Привязываем напрямую к стору!
  group: 'columns',
  animation: 150,
  handle: '.column-handle', // Тянуть можно только за заголовок колонки
  forceFallback: true,              // Включаем кастомный рендеринг перетаскивания
  ghostClass: 'ghost-column',       // Класс для области, куда "упадет" колонка
  chosenClass: 'chosen-column',     // Класс для колонки, которую начали тянуть
  dragClass: 'drag-column',         // Класс для самого перемещаемого элемента (cursor)
  fallbackClass: 'fallback-column',
  onEnd: (event) => {
    if (event.oldIndex === event.newIndex) return
    const orderedColumnIds = store.columns.map(col => col.id)
    store.saveColumnsOrder(orderedColumnIds, props.board.id)
  }
})


// --- Drag & Drop Карточек ---
const onCardDragEnd = (event) => {
  // ID перемещенной карточки (с атрибута на <Card>)
  const movedCardId = event.item?.dataset?.cardId
  if (!movedCardId) return

  // ID целевой колонки (с атрибута на контейнере списка в Column.vue)
  // event.to - это DOM-элемент, куда упала карточка
  const targetColumnId = event.to?.dataset?.columnId
  if (!targetColumnId) return

  // Если перетащили в ту же колонку на ту же позицию — ничего не делаем
  if (event.from === event.to && event.oldIndex === event.newIndex) {
    return
  }

  // Находим колонку в сторе, чтобы получить актуальный список карточек
  const targetColumn = store.columns.find(col => col.id === Number(targetColumnId))
  if (!targetColumn) {
    console.error('Целевая колонка не найдена в сторе!')
    return
  }

  // Отправляем на сервер
  store.saveCardOrder(movedCardId, targetColumnId, targetColumn.cards)
}

const openCardDetail = (card) => store.selectCard(card)
const applyFilters = (filters) => store.setFilters(filters)
const isShowCreateColumnModal = ref(false)

// --- Логика скролла (вынесена для чистоты шаблона) ---
const boardContainerRef = ref(null)

// Горизонтальный скролл колесиком мыши
const handleWheel = (e) => {
  const container = boardContainerRef.value
  if (!container) return

  // Если уже есть горизонтальная составляющая (например, пользователь использует тачпад), не вмешиваемся
  if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) return

  // Преобразуем вертикальный скролл колесика в горизонтальный
  container.scrollLeft += e.deltaY
  e.preventDefault() // Предотвращаем скролл всей страницы
}

// Drag-to-Scroll (Перетаскивание фона доски)
const isDown = ref(false)
const startX = ref(0)
const startScrollLeft = ref(0)

const handleMouseDown = (e) => {
  // Игнорируем нажатие, если оно было по колонке, карточке, кнопке или меню
  // Это гарантирует, что мы не сломаем клики по карточкам или drag-and-drop колонок
  if (e.target.closest('.column') || e.target.closest('button') || e.target.closest('[role="menu"]')) {
    return
  }
  
  isDown.value = true
  const container = boardContainerRef.value
  container.classList.add('is-dragging')
  
  const rect = container.getBoundingClientRect()
  startX.value = e.clientX - rect.left
  startScrollLeft.value = container.scrollLeft
}

const stopDragging = () => {
  isDown.value = false
  const container = boardContainerRef.value
  if (container) {
    container.classList.remove('is-dragging')
  }
}

const handleMouseMove = (e) => {
  if (!isDown.value) return
  e.preventDefault()
  const container = boardContainerRef.value
  const rect = container.getBoundingClientRect()
  const x = e.clientX - rect.left
  const walk = (x - startX.value) * 1.5 // Коэффициент скорости скролла
  container.scrollLeft = startScrollLeft.value - walk
}

// Регистрируем слушатель колеса мыши с passive: false, чтобы работал e.preventDefault()
onMounted(() => {
  const el = boardContainerRef.value
  if (el) {
    el.addEventListener('wheel', handleWheel, { passive: false })
  }
})

onUnmounted(() => {
  const el = boardContainerRef.value
  if (el) {
    el.removeEventListener('wheel', handleWheel)
  }
})

// --------------------------
</script>

<template>
  <div class="h-full flex flex-col">
    <BoardHeader 
      :board="board"
      @filter-change="applyFilters"
      @add-column="isShowCreateColumnModal = true"
    />

    <div 
      ref="boardContainerRef"
      class="flex-1 overflow-x-auto overflow-y-hidden p-6 board-scroll cursor-grab"
      @mousedown="handleMouseDown"
      @mouseleave="stopDragging"
      @mouseup="stopDragging"
      @mousemove="handleMouseMove"
    >
      <div class="flex h-full gap-4 min-w-max" ref="boardRef">
        <Column
          class="column"
          v-for="column in store.sortedColumns"
          :key="column.id"
          :column="column"
          @card-click="openCardDetail"
          @card-drag-end="onCardDragEnd"
        />
        <AddColumnButton @click="isShowCreateColumnModal = true" />
      </div>
    </div>

    <CreateColumnModal v-model="isShowCreateColumnModal" :board-id="board.id" />
  </div>
</template>

<style scoped>

/* Стилизация горизонтального скроллбара (для WebKit браузеров) */
.board-scroll::-webkit-scrollbar {
  height: 8px;
}
.board-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.board-scroll::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5); /* gray-400 с прозрачностью */
  border-radius: 4px;
}
.board-scroll::-webkit-scrollbar-thumb:hover {
  background-color: rgba(156, 163, 175, 0.8);
}

/* Базовые настройки контейнера скролла */
.board-scroll {
  overscroll-behavior-x: contain; /* Предотвращает "отскок" скролла на macOS */
}

/* Стили во время перетаскивания фона */
.board-scroll.is-dragging {
  cursor: grabbing !important;
  user-select: none; /* Запрещаем выделение текста пока тянем фон */
}

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