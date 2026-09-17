<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { PlusIcon, ChevronRightIcon, UsersIcon, UserIcon, ViewColumnsIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  workspace: {
    type: Object,
    required: true
  },
  boards: {
    type: Array,
    default: () => []
  }
})

const showCreateBoardModal = ref(false)

const navigateToBoard = (boardId) => {
  router.visit(`/boards/${boardId}`)
}
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl">
            {{ workspace.name.charAt(0).toUpperCase() }}
          </div>
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              {{ workspace.name }}
            </h1>
            <p v-if="workspace.description" class="text-gray-600 mt-1">
              {{ workspace.description }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-6 text-sm text-gray-500">
          <div class="flex items-center gap-2">
            <UsersIcon class="w-4 h-4" />
            <span>{{ workspace.members_count }} участников</span>
          </div>
          <div class="flex items-center gap-2">
            <UserIcon class="w-4 h-4" />
            <span>Владелец: {{ workspace.owner?.name }}</span>
          </div>
        </div>
      </div>

      <!-- Доски -->
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900">Доски</h2>
        <button
          @click="showCreateBoardModal = true"
          class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <PlusIcon class="w-5 h-5" />
          Создать доску
        </button>
      </div>

      <div v-if="boards.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="board in boards"
          :key="board.id"
          @click="navigateToBoard(board.id)"
          class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg hover:border-blue-300 transition-all cursor-pointer group"
        >
          <div class="flex items-start justify-between mb-4">
            <div
              class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl"
              :style="{ backgroundColor: board.color || '#3b82f6' }"
            >
              {{ board.icon || '📋' }}
            </div>
            <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" />
          </div>

          <h3 class="text-lg font-semibold text-gray-900 mb-2">
            {{ board.name }}
          </h3>

          <p v-if="board.description" class="text-sm text-gray-600 mb-4 line-clamp-2">
            {{ board.description }}
          </p>

          <div class="flex items-center gap-4 text-sm text-gray-500">
            <div class="flex items-center gap-1">
              <ViewColumnsIcon class="w-4 h-4" />
              <span>{{ board.columns_count || 0 }} колонок</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-16 bg-gray-50 rounded-xl">
        <ViewColumnsIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          В этом воркспейсе пока нет досок
        </h3>
        <p class="text-gray-600 mb-6">
          Создайте первую доску, чтобы начать работу
        </p>
        <button
          @click="showCreateBoardModal = true"
          class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          Создать доску
        </button>
      </div>
    </div>
  </AppLayout>
</template>