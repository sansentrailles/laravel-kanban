<script setup>
import { ref } from 'vue'

const props = defineProps({
  column: {
    type: Object,
    required: true
  },
  cards: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['card-click', 'card-drop', 'column-drop'])

const isDraggingOver = ref(false)
const draggedCard = ref(null)
const showAddCard = ref(false)

const handleDragStart = (card) => {
  draggedCard.value = card
}

const handleDragOver = (e) => {
  isDraggingOver.value = true
}

const handleDragLeave = () => {
  isDraggingOver.value = false
}

const handleDrop = (e) => {
  isDraggingOver.value = false
  
  if (draggedCard.value) {
    emit('card-drop', {
      cardId: draggedCard.value.id,
      columnId: props.column.id,
      position: calculatePosition(e)
    })
  }
}

const calculatePosition = (e) => {
  // Calculate drop position based on mouse coordinates
  return 0
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
  <div 
    class="w-80 flex-shrink-0 bg-gray-100 rounded-lg flex flex-col max-h-full"
    @dragover.prevent="handleDragOver"
    @dragleave="handleDragLeave"
    @drop="handleDrop"
  >
    <!-- Header колонки -->
    <div class="p-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-900">{{ column.title }}</h3>
        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
          {{ cards.length }}
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
      :current="cards.length"
      :limit="column.wip_limit"
    />
    
    <!-- Список карточек -->
    <div class="flex-1 overflow-y-auto p-2 space-y-2">
      <Card
        v-for="card in cards"
        :key="card.id"
        :card="card"
        draggable="true"
        @dragstart="handleDragStart(card)"
        @click="$emit('card-click', card)"
      />
      
      <!-- Drop placeholder -->
      <div 
        v-if="isDraggingOver"
        class="h-20 border-2 border-dashed border-blue-400 rounded-lg bg-blue-50"
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