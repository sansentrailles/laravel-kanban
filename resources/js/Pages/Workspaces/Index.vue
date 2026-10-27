<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { BriefcaseIcon, PlusIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import CreateWorkspaceModal from '@/Components/Workspace/CreateWorkspaceModal.vue'

defineProps({
  workspaces: {
    type: Array,
    default: () => []
  },
  currentWorkspace: {
    type: Object,
    default: null
  },
  boards: {
    type: Array,
    default: () => []
  },
  unreadNotifications: {
    type: Number,
    default: 0
  }
})

const showCreateWorkspaceModal = ref(false)
</script>

<template>
  <Head title="Пространства" />

  <AppLayout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
      <div class="w-16 h-16 mx-auto mb-6 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center">
        <BriefcaseIcon class="w-8 h-8 text-white" />
      </div>

      <h1 class="text-2xl font-bold text-gray-900">
        У вас пока нет пространств
      </h1>
      <p class="mt-3 text-gray-600">
        Создайте первое пространство, чтобы пригласить команду и начать работу с досками.
      </p>

      <button
        @click="showCreateWorkspaceModal = true"
        class="mt-8 inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
      >
        <PlusIcon class="w-5 h-5" />
        Создать пространство
      </button>
    </div>

    <CreateWorkspaceModal
      v-if="showCreateWorkspaceModal"
      @close="showCreateWorkspaceModal = false"
    />
  </AppLayout>
</template>
