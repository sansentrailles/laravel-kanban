<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { PlusIcon, ChevronRightIcon, UsersIcon, BriefcaseIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import CreateWorkspaceModal from '@/Components/Workspace/CreateWorkspaceModal.vue'

defineProps({
  workspaces: {
    type: Array,
    default: () => []
  }
})

const showCreateModal = ref(false)

const navigateToWorkspace = (slug) => {
  router.visit(`/workspaces/${slug}`)
}

const handleWorkspaceCreated = (workspace) => {
  showCreateModal.value = false
  // Переходим к созданному воркспейсу
  router.visit(`/workspaces/${workspace.slug}`)
}
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Мои воркспейсы</h1>
          <p class="mt-2 text-sm text-gray-600">
            Выберите воркспейс для работы или создайте новый
          </p>
        </div>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <PlusIcon class="w-5 h-5" />
          Создать воркспейс
        </button>
      </div>

      <!-- Список воркспейсов -->
      <div v-if="workspaces.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="workspace in workspaces"
          :key="workspace.id"
          @click="navigateToWorkspace(workspace.slug)"
          class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg hover:border-blue-300 transition-all cursor-pointer group"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">
              {{ workspace.name.charAt(0).toUpperCase() }}
            </div>
            <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" />
          </div>

          <h3 class="text-lg font-semibold text-gray-900 mb-2">
            {{ workspace.name }}
          </h3>

          <p v-if="workspace.description" class="text-sm text-gray-600 mb-4 line-clamp-2">
            {{ workspace.description }}
          </p>

          <div class="flex items-center gap-4 text-sm text-gray-500">
            <div class="flex items-center gap-1">
              <UsersIcon class="w-4 h-4" />
              <span>{{ workspace.members_count || 1 }} участников</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Пустое состояние -->
      <div v-else class="text-center py-16">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <BriefcaseIcon class="w-12 h-12 text-gray-400" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          У вас пока нет воркспейсов
        </h3>
        <p class="text-gray-600 mb-6">
          Создайте первый воркспейс, чтобы начать работу
        </p>
        <button
          @click="showCreateModal = true"
          class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          Создать воркспейс
        </button>
      </div>
    </div>

    <!-- Модальное окно создания -->
    <CreateWorkspaceModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleWorkspaceCreated"
    />
  </AppLayout>
</template>