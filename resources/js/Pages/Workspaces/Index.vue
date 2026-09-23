<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { PlusIcon, ChevronRightIcon, UsersIcon, BriefcaseIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import CreateWorkspaceModal from '@/Components/Workspace/CreateWorkspaceModal.vue'
import BoardView from '@/Components/Board/BoardView.vue'

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

</script>

<template>
  <AppLayout>
    <Head :title="`Пространство: ${currentWorkspace?.name}`"/>
  </AppLayout>
</template>