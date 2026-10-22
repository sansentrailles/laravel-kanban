<script setup>
import { computed, ref, watch } from 'vue'
import DropdownMenu from '../UI/DropdownMenu.vue'
import DropdownMenuItem from '../UI/DropdownMenuItem.vue'
import WipLimitBar from '../UI/WipLimitBar.vue'
import Card from './Card.vue'
import AddCardForm from './AddCardForm.vue'
import { useDraggable } from 'vue-draggable-plus'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { useBoardStore } from '@/Stores/board'

const props = defineProps({
  column: { type: Object, required: true }
})

const store = useBoardStore()

const emit = defineEmits(['card-click', 'card-drag-end'])
const isDragging = ref(false)
const cardsListRef = ref(null)
const showAddCard = ref(false)

// vue-draggable-plus перетаскивает локальную КОПИЮ списка.
// Истиной всегда является стор: после дропа BoardView вызывает store.applyCardMove,
// стор мутируется один раз, и watcher ниже обновляет локальную копию —
// Vue полностью перестраивает DOM из данных стора (нет дублей карточек).
const localCards = ref([])

watch(
  () => [...props.column.cards],
  (cards) => { localCards.value = [...cards] },
  { immediate: true }
)

const draggable = useDraggable(cardsListRef, localCards, {
  group: 'cards',
  animation: 150,
  forceFallback: true,
  ghostClass: 'ghost-card',       // Класс для плейсхолдера (куда упадет карточка)
  chosenClass: 'chosen-card2',    // Класс для карточки, которую начали тянуть
  dragClass: 'drag-card2',        // Класс для перемещаемой карточки
  fallbackClass: 'fallback-card2',
  onStart: () => {
    // Снимаем порядок карточек ДО того, как изменим стор в applyCardMove,
    // чтобы было чем откатиться, если сервер отклонит запрос
    store.snapshotBoard()
    isDragging.value = true
  },
  onEnd: (event) => {
    setTimeout(() => { isDragging.value = false }, 0)
    emit('card-drag-end', event) // Сообщаем родителю, что драг окончен (для API)
  }
})

// SortableJS перенес DOM-узел карточки сам, Vue об этом не знает — после дропа
// делаем полную перерисовку контейнера из стор-данных (по ключу) и заново
// инициализируем Sortable на новом DOM-узле контейнера.
// ВАЖНО: источник watch — геттер. store.cardsRenderTick разворачивается Pinia
// в число, и watch по нему никогда не сработает.
const cardsListKey = computed(() => `${props.column.id}-cards-${store.cardsRenderTick}`)

watch(
  () => store.cardsRenderTick,
  () => {
    // flush: 'post' — к этому моменту контейнер уже пересобран, cardsListRef указывает
    // на новый DOM-узел: пересоздаем Sortable на нем
    draggable?.start()
  },
  { flush: 'post' }
)

const handleCardClick = (card) => {
  if (isDragging.value) return
  emit('card-click', card)
}

const addCard = (title) => {
  // Вызываем экшен в сторе, передаем ID колонки и название
  store.createCard(props.column.id, title)
  showAddCard.value = false
}

// --- Смена цвета колонки через меню ---
const colorInputRef = ref(null)

const changeColor = () => {
  // Открываем нативный диалог выбора цвета браузера
  colorInputRef.value?.click()
}

const onColorChange = (event) => {
  // Инпут типа color всегда возвращает HEX #RRGGBB — валидация на бэке совпадает
  store.updateColumnColor(props.column.id, event.target.value)
}
</script>

<template>
  <div class="column w-80 flex-shrink-0 bg-gray-100 rounded-lg flex flex-col max-h-full overflow-hidden">
    <!-- Полоска цвета колонки (если цвет установлен) -->
    <div
      v-if="column.color"
      class="h-1.5 w-full"
      :style="{ backgroundColor: column.color }"
    />

    <!-- Header -->
    <div class="p-3 flex items-center justify-between column-handle">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-900">{{ column.title }} - {{ column.id }}</h3>
        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
          {{ localCards.length }}
        </span>
      </div>
      <DropdownMenu>
        <DropdownMenuItem @click="changeColor">Сменить цвет</DropdownMenuItem>
        <DropdownMenuItem @click="editColumn">Переименовать</DropdownMenuItem>
        <DropdownMenuItem @click="setWipLimit">WIP Лимит</DropdownMenuItem>
        <DropdownMenuItem @click="archiveColumn" class="text-red-600">
          Архивировать
        </DropdownMenuItem>
      </DropdownMenu>
      <!-- Нативный color-picker: открываем программно из пункта меню,
           диалог выбора цвета не требует дополнительных компонентов -->
      <input
        ref="colorInputRef"
        type="color"
        :value="column.color || '#4b82f6'"
        class="sr-only"
        @change="onColorChange"
      >
    </div>

    <!-- WIP Limit Bar (если установлен) -->
    <WipLimitBar 
      v-if="column.wip_limit"
      :current="localCards.length"
      :limit="column.wip_limit"
      :color="column.color"
    />

    <!-- Список карточек -->
    <div
      class="flex-1 overflow-y-auto p-2 space-y-2"
      ref="cardsListRef"
      :key="cardsListKey"
      :data-column-id="column.id"
    >
      <Card
        v-for="card in localCards"
        :key="card.id"
        :card="card"
        :data-card-id="card.id"
        @click="handleCardClick(card)"
      />
    </div>
    
    <div class="p-2">
      <button 
        @click="showAddCard = !showAddCard"
        class="w-full text-left text-gray-600 hover:bg-gray-200 rounded px-2 py-1.5 text-sm flex items-center gap-2"
      >
        <PlusIcon class="w-4 h-4" />
        Добавить карточку
      </button>
      
      <AddCardForm 
        v-if="showAddCard"
        :column-id="column.id"
        @submit="addCard"
        @cancel="showAddCard = false"
      />
    </div>
  </div>
</template>

<style scoped>
/* Стили для перетаскиваемых карточек (используем :deep) */
/* Плесхолдер - место, куда встанет карточка */
:deep(.ghost-card) {
  background: rgba(168, 85, 247, 0.1); /* Полупрозрачный фиолетовый фон */
  border: 2px dashed #a855f7;          /* Пунктирная граница */
  border-radius: 8px;
  opacity: 0.2;
  /* Можно задать фиксированную высоту, чтобы плейсхолдер не "прыгал" */
  /* height: 60px; */
}

/* Элемент в момент клика до начала движения */
:deep(.chosen-card2) {
  cursor: grabbing;
  opacity: 0.5 !important;
  /* transform: rotate(1deg); */
}

/* "Призрак" карточки, который висит под курсором мыши */
:deep(.drag-card2),
:deep(.fallback-card2) 
{
  background: #ffffff !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
  transform: rotate(2deg); /* Легкий наклон, как у Trello */
  opacity: 0.85 !important;
  cursor: grabbing !important;
  border-radius: 8px;
}
</style>