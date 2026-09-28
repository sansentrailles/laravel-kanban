<script setup>
import { useBoardStore } from '@/Stores/board'
import { computed, ref, watch } from 'vue'
import BoardHeader from './BoardHeader.vue'
import Column from './Column.vue'
import AddColumnButton from './AddColumnButton.vue'
import CreateColumnModal from './CreateColumnModal.vue'

const props = defineProps({
  board: {
    type: Array,
    required: true
  },
})

const showCreateColumnModal = ref(false)

const store = useBoardStore()
const columns = computed(() => store.sortedColumns)

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
      ref="boardCanvas"
    >
      <div class="flex h-full gap-4">
        <Column
          v-for="column in columns"
          :key="column.id"
          :column="column"
          :cards="column.cards"
          @card-click="openCardDetail"
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