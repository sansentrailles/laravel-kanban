<script setup>
import { ref, watch } from 'vue'
import DropdownMenu from '../UI/DropdownMenu.vue'
import DropdownMenuItem from '../UI/DropdownMenuItem.vue'
import WipLimitBar from '../UI/WipLimitBar.vue'
import Card from './Card.vue'
import AddCardForm from './AddCardForm.vue'
import { useDraggable } from 'vue-draggable-plus'
import { PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  column: {
    type: Object,
    required: true
  },
  // cards: {
  //   type: Array,
  //   required: true
  // }
})

const isDragging = ref(false)  // флаг перетаскивания
const cardsListRef = ref(null)
const localCards = ref([...props.column.cards])

const emit = defineEmits([
  'card-click',
  'card-drag-end',
  // 'update:cards'
])

const showAddCard = ref(false)

// Следим за внешними изменениями (например, если карточка пришла из другой колонки)
watch(() => props.column.cards, (newCards) => {
  localCards.value = [...newCards]
}, { deep: true })

// Инициализируем drag-and-drop для КАРТОЧЕК внутри этой колонки
useDraggable(cardsListRef, localCards, {
  group: 'cards',
  animation: 150,
  // --- ДОБАВЛЕННЫЕ ОПЦИИ ДЛЯ КАРТОЧЕК ---
  forceFallback: true,             
  ghostClass: 'ghost-card',         // Класс для плейсхолдера (куда упадет карточка)
  chosenClass: 'chosen-card2',       // Класс для карточки, которую начали тянуть
  dragClass: 'drag-card2',           // Класс для перемещаемой карточки
  fallbackClass: 'fallback-card2',   // Класс для fallback-элемента
  // --------------------------------------
  onStart: () => {
    isDragging.value = true
  },
  onEnd: (event) => {
    // ВАЖНО: Сбрасываем флаг с микро-задержкой (setTimeout 0).
    // Это гарантирует, что событие @click (которое сработает сразу после mouseup)
    // увидит isDragging = true и заблокируется.
    setTimeout(() => {
      isDragging.value = false
    }, 0)

    // Оповещаем родителя, что массив внутри колонки изменился
    emit('update:cards', localCards.value)
    
    // Передаем событие окончания перетаскивания для отправки на API
    emit('card-drag-end', event)
  }
})

const handleCardClick = (card) => {
  // Если только что было перетаскивание - отменяем открытие модалки
  if (isDragging.value) return 
  
  // Если перетаскивания не было, триггерим обычный клик
  emit('card-click', card)
}

const editColumn = () => {
  // Edit column logic
}

const setWipLimit = () => {
  // Set WIP limit logic
}

const archiveColumn = () => {
  // Archive column logic
}

const addCard = (data) => {
  // Add card logic
  showAddCard.value = false
}
</script>

<template>
  <div class="w-80 flex-shrink-0 bg-gray-100 rounded-lg flex flex-col max-h-full">
    <!-- Цветная полоска сверху (если цвет задан) -->
    <div 
      v-if="column.color"
      class="h-1.5 w-full rounded-t-lg"
      :style="{ backgroundColor: column.color }"
    />

    <!-- Header колонки -->
    <div class="p-3 flex items-center justify-between column-handle">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-900">{{ column.title }}</h3>
        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
          {{ localCards.length }}
        </span>
      </div>
      
      <DropdownMenu>
        <DropdownMenuItem @click="editColumn">Переименовать</DropdownMenuItem>
        <DropdownMenuItem @click="setWipLimit">WIP Лимит</DropdownMenuItem>
        <DropdownMenuItem @click="archiveColumn" class="text-red-600">
          Архивировать
        </DropdownMenuItem>
      </DropdownMenu>
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
      :data-column-id="column.id"
    >
      <Card
        v-for="card in localCards"
        :key="card.id"
        :card="card"
        @click="handleCardClick"
      />
      
    </div>
    
    <!-- Кнопка добавления карточки -->
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