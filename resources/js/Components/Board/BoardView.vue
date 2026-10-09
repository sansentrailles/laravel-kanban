<script setup>
import { useBoardStore } from '@/Stores/board'
import { usePage } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref, watch, watchEffect } from 'vue'
import BoardHeader from './BoardHeader.vue'
import Column from './Column.vue'
import AddColumnButton from './AddColumnButton.vue'
import CreateColumnModal from './CreateColumnModal.vue'
import { useDraggable } from 'vue-draggable-plus'
import { useBoardRealtime } from '@/Composables/useBoardRealtime'

const props = defineProps({
  board: {
    type: Array,
    required: true
  },
})

const isShowCreateColumnModal = ref(false)

const store = useBoardStore()
const columns = ref([])
const page = usePage()
const workspaceId = computed(() => page.props.currentWorkspace?.id)
console.log('workspace id: ', workspaceId.value)

onMounted(() => {
  window.Echo.private(`board.${workspaceId.value}`)
    .listen('.card.updated', (e) => {
      console.log(e)
      console.log('🔔 Получено обновление карточки:', e.card)
      
      // Обновляем карточку в колонках доски
      store.updateCard(e.card)
      
      // Если карточка сейчас открыта в модалке у второго пользователя
      if (store.selectedCard && store.selectedCard.id === e.card.id) {
        
        // Мержим скалярные поля (название, описание и т.д.)
        Object.assign(store.selectedCard, e.card)
        
        // ВАЖНО: Массивы и объекты нужно переназначать явно, 
        // иначе Vue может не увидеть глубоких изменений
        if (e.card.checklists !== undefined) {
          store.selectedCard.checklists = e.card.checklists
        }
        if (e.card.labels !== undefined) {
          store.selectedCard.labels = e.card.labels
        }
        if (e.card.assignees !== undefined) {
          store.selectedCard.assignees = e.card.assignees
        }
      }
    })
})

// КРИТИЧЕСКИ ВАЖНО: Отписываемся при уничтожении компонента, 
// иначе будут утечки памяти и дублирование событий при переходе между страницами
onUnmounted(() => {
  window.Echo.leave(`board.${workspaceId.value}`)
})

watchEffect(() => {
  // Важно: создаем новую копию массива, чтобы разорвать ссылку
  // и SortableJS мутировал локальный ref, а не массив из стора
  columns.value = [...store.sortedColumns] 
})

defineEmits(['add-column'])

//------------------------
const boardRef = ref(null)
useDraggable(boardRef, columns, {
  group: 'columns',
  animation: 150,
  handle: '.column-handle', // Тянуть можно только за заголовок колонки
  forceFallback: true,              // Включаем кастомный рендеринг перетаскивания
  ghostClass: 'ghost-column',       // Класс для области, куда "упадет" колонка
  chosenClass: 'chosen-column',     // Класс для колонки, которую начали тянуть
  dragClass: 'drag-column',         // Класс для самого перемещаемого элемента (cursor)
  fallbackClass: 'fallback-column',
  onEnd: async (event) => {
    const { oldIndex, newIndex } = event
    if (oldIndex === newIndex) return

    const orderedColumnIds = columns.value.map(col => col.id)   

    try {
      const response = await store.saveColumnsOrder(orderedColumnIds)
    } catch (error) {
      toast.error('Что-то пошло не так.')
      columns.value = [...store.sortedColumns]
    }
  }
})

function getStrictNeighbors(array, targetId) {
    const index = array.indexOf(Number(targetId));

    if (index === -1) return { prev: null, next: null };

    const prev = index > 0 ? array[index - 1] : null;
    const next = index < array.length - 1 ? array[index + 1] : null;

    return { prev, next };
}

// ОСНОВНАЯ ЛОГИКА: Отправка на API после дропа
const onCardDragEnd = async (event) => {
  const { item, to, from, oldIndex, newIndex } = event

  // ID перемещенной карточки (с атрибута на <Card>)
  const movedCardId = item?.dataset?.cardId

  if (!movedCardId) return

  // ID целевой колонки (с атрибута на контейнере списка в Column.vue)
  const targetColumnId = to?.dataset?.columnId
  if (!targetColumnId) {
    return
  }

  // Если ничего не изменилось (клик без движения или возврат на место)
  if (from === to && oldIndex === newIndex) {
    return
  }

  // Берем АКТУАЛЬНЫЙ порядок карточек в ЦЕЛЕВОЙ колонке из реактивного состояния
  // На этот момент onUpdateCards уже обновил columns.value
  const targetColumn = columns.value.find(col => col.id === Number(targetColumnId))
  console.log(columns.value.forEach(col => console.log(col.title, col.id, typeof col.id)))
  if (!targetColumn) {
    console.error('Target column not found in state', targetColumnId)
    columns.value = [...store.sortedColumns] // фоллбэк
    return
  }

  const orderedCardIds = targetColumn.cards.map(c => c.id)
  const neighbors = getStrictNeighbors(orderedCardIds, movedCardId)

  try {
    await store.saveCardOrder(movedCardId, targetColumnId, neighbors)
    toast.success('Порядок карточек сохранен')
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
  //  Находим нужную колонку в реактивных данных
  const targetColumn = columns.value.find(col => col.id === columnId)
  
  if (targetColumn) {
    // Обновляем данные в родительском состоянии
    targetColumn.cards = newCards    
  }
}
//------------------------

watch(() => props.board, (newBoard) => {
  if (newBoard) {
    store.setBoardData(newBoard, newBoard.columns?.data || [])
  }
}, { immediate: true })

// Ссылка на контейнер скролла
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

const openCardDetail = (card) => {
  store.selectCard(card)
}

const applyFilters = (filters) => {
  store.setFilters(filters)
}

const showAddColumnModal = () => {
  isShowCreateColumnModal.value = true
}
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- Header доски -->
    <BoardHeader 
      :board="board"
      @filter-change="applyFilters"
      @add-column="showAddColumnModal"
    />
    
    <!-- Холст с колонками -->
    <div 
      ref="boardContainerRef"
      class="flex-1 overflow-x-auto overflow-y-hidden p-6 board-scroll cursor-grab"
      @mousedown="handleMouseDown"
      @mouseleave="stopDragging"
      @mouseup="stopDragging"
      @mousemove="handleMouseMove"
    >
      <div
        class="flex h-full gap-4 min-w-max"
        ref="boardRef"
      >
        <Column
          class="column"
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
        <AddColumnButton
          @click="showAddColumnModal" 
        />
      </div>
    </div>

    <CreateColumnModal
      v-model="isShowCreateColumnModal"
      :board-id="board.id"
      @created="handleColumnCreated"
    />
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