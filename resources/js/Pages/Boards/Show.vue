<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import BoardView from '@/Components/Board/BoardView.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

onMounted(() => {
    window.Echo.channel('test-channel')
        .listen('TestReverbEvent', (e) => {
            console.log('Получено сообщение от Reverb:', e.message);
            alert(e.message);
        });
});

onUnmounted(() => {
    window.Echo.leave('test-channel');
});

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
  workspaceLabels: {
    type: Array,
    default: () => []
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