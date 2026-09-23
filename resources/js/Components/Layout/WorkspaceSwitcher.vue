<script setup>
import { ref } from 'vue'
import { ChevronDownIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import AddWorkspaceForm from '../Board/AddWorkspaceForm.vue'

const props = defineProps({
  workspaces: {
    type: Array,
    required: true
  },
  currentWorkspace: {
    type: Array,
    default: null
  }
})

const showAddWorkspace = ref(false)

const emit = defineEmits(['select', 'create-workspace-click'])

const isOpen = ref(false)

const selectWorkspace = (workspace) => {
  emit('select', workspace)
  isOpen.value = false
}

const addWorkspace = () => {
  showAddWorkspace.value = false
}
</script>

<template>
  <div class="p-4 border-b border-slate-700 relative">
    <button 
      @click="isOpen = !isOpen"
      class="flex items-center gap-3 w-full text-left hover:bg-slate-800 rounded-lg p-2 transition-colors"
    >
      <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
        {{ currentWorkspace?.name?.charAt(0) || 'W' }}
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-sm font-medium text-white truncate">
          {{ currentWorkspace?.name || 'Выбрать пространство' }}
        </div>
      </div>
      <ChevronDownIcon class="w-4 h-4 text-slate-400" />
    </button>

    <!-- Dropdown -->
    <div v-if="isOpen" class="absolute left-4 mt-2 bg-slate-800 rounded-lg shadow-xl border border-slate-700 z-50 w-max min-w-[12rem]">
      <div class="p-2 space-y-1">
        <button
          v-for="workspace in workspaces"
          :key="workspace.id"
          @click="selectWorkspace(workspace)"
          class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-700 transition-colors text-left"
          :class="{ 'bg-slate-700': workspace.id === currentWorkspace?.id }"
        >
          <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
            {{ workspace.name.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-white truncate">{{ workspace.name }}</div>
            <div class="text-xs text-slate-400">{{ workspace.members_count }} участников</div>
          </div>
        </button>
      </div>

      <AddWorkspaceForm
        v-if="showAddWorkspace"
        @submit="addWorkspace"
        @cancel="showAddWorkspace = false"        
      />
    
      <div class="border-t border-slate-700 p-2">
        <button class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-700 text-slate-300 text-sm"
          @click="$emit('create-workspace-click', card)"
        >
          <div class="flex items-center">
            <PlusIcon  class="w-4 h-4 mr-1" />
            Добавить пространство
          </div>
        </button>
      </div>

      <div class="border-t border-slate-700 p-2">
        <button class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-700 text-slate-300 text-sm"
          @click="showAddWorkspace = !showAddWorkspace"
        >
          <div v-if="!showAddWorkspace" class="flex items-center">
            <PlusIcon  class="w-4 h-4 mr-1" />
            Создать пространство
          </div>
          
          <div v-else class="flex items-center justify-center">
            <XMarkIcon  class="w-4 h-4 mr-1" />
            Отменить
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
