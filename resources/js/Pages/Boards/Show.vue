<script setup>
import { computed, ref } from 'vue'
import BoardView from '@/Components/Board/BoardView.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

// Получаем данные от Laravel через Inertia
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

// Извлекаем колонки из объекта доски (так как Resource вкладывает их внутрь)
const columns = computed(() => props.board?.columns?.data || [])

</script>

<template>
  <Head :title="`Доска: ${board.name}`"/>

  <AppLayout>
    <BoardView 
      :board="board"
      :columns="columns"
    />
  </AppLayout>
</template>