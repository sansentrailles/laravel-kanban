<script setup>
import { computed, ref } from 'vue'
import BoardView from '@/Components/Board/BoardView.vue'
import AppLayout from '@/Layouts/AppLayout.vue'

// 1. Получаем данные от Laravel через Inertia
const props = defineProps({
  board: {
    type: Array,
    required: true
  },
  workspaces: {
    type: Array,
    required: true
  },
  currentWorkspace: {
    type: Object,
    required: true
  },
  boards: {
    type: Object,
    required: true
  },
  unreadNotifications: {
    type: Number,
    default: 0
  }
})

// 2. Извлекаем колонки из объекта доски (так как Resource вкладывает их внутрь)
// const columns = computed(() => props.columns?.data || props.columns || [])
const columns = computed(() => props.board?.columns?.data || [])

const showAddColumnModal = ref(false)

const openCardDetail = (card) => {
  console.log('Open card:', card)
  // Здесь будет логика открытия CardDetailDrawer (например, через Pinia store)
}

const handleCardDrop = async (data) => {
  console.log('Card drop:', data)
  // Здесь будет вызов axios.patch для перемещения карточки (Optimistic UI)
}

const handleColumnDrop = async (data) => {
  console.log('Column drop:', data)
  // Здесь будет вызов axios.patch для перемещения колонки
}
</script>

<template>
  <AppLayout>
    <BoardView 
      :board="board"
      :columns="columns"
      @card-click="openCardDetail"
      @card-drop="handleCardDrop"
      @column-drop="handleColumnDrop"
      @add-column="showAddColumnModal = true"
    />
  </AppLayout>
</template>