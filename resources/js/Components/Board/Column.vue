<script setup>
import { ref } from 'vue'
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

// МАГИЯ ЗДЕСЬ: Привязываем Draggable напрямую к массиву карточек в Pinia!
// props.column - это реактивный объект из стора. 
// При перетаскивании vue-draggable-plus сам поменяет массив props.column.cards.
useDraggable(cardsListRef, props.column.cards, {
  group: 'cards',
  animation: 150,
  forceFallback: true,
  ghostClass: 'ghost-card',
  onStart: () => { isDragging.value = true },
  onEnd: (event) => {
    setTimeout(() => { isDragging.value = false }, 0)
    emit('card-drag-end', event) // Сообщаем родителю, что драг окончен (для API)
  }
})

const handleCardClick = (card) => {
  if (isDragging.value) return 
  emit('card-click', card)
}

const addCard = (title) => {
  // Вызываем экшен в сторе, передаем ID колонки и название
  store.createCard(props.column.id, title)
  showAddCard.value = false
}
</script>

<template>
  <div class="column w-80 flex-shrink-0 bg-gray-100 rounded-lg flex flex-col max-h-full">
    <!-- Header -->
    <div class="p-3 flex items-center justify-between column-handle">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-900">{{ column.title }} - {{ column.id }}</h3>
        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
          {{ column.cards.length }}
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
      :current="column.cards.length"
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
        v-for="card in column.cards"
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