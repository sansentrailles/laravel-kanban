<script setup>
import { useBoardStore } from '@/Stores/board'
import { computed } from 'vue'
// import { useBoardStore } from '@/stores/board'

const store = useBoardStore()
const board = computed(() => store.currentBoard)
const columns = computed(() => store.sortedColumns)

const openCardDetail = (card) => {
  store.selectCard(card)
}

const handleCardDrop = async (data) => {
  // Optimistic UI update
  store.moveCardOptimistic(data)
  
  try {
    await axios.patch(`/api/cards/${data.cardId}/move`, data)
  } catch (error) {
    store.rollbackCardMove(data)
    toast.error('Не удалось переместить карточку')
  }
}

const handleColumnDrop = async (data) => {
  // Handle column reordering
  await axios.patch(`/api/columns/${data.columnId}/move`, data)
}

const showAddColumnModal = () => {
  // Show modal to add new column
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
      @add-column="showAddColumnModal"
    />
    
    <!-- Холст с колонками -->
    <div 
      class="flex-1 overflow-x-auto overflow-y-hidden p-6"
      ref="boardCanvas"
    >
      <div class="flex h-full gap-4">
        <Column
          v-for="column in columns"
          :key="column.id"
          :column="column"
          :cards="column.cards"
          @card-click="openCardDetail"
          @card-drop="handleCardDrop"
          @column-drop="handleColumnDrop"
        />
        
        <!-- Кнопка добавления колонки -->
        <AddColumnButton @click="showAddColumnModal" />
      </div>
    </div>
  </div>
</template>