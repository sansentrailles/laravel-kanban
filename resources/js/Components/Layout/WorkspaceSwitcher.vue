<script setup>
import { ref } from 'vue'
import { ChevronDownIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  workspaces: {
    type: Array,
    required: true
  },
  currentWorkspace: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['select'])

const isOpen = ref(false)

const selectWorkspace = (workspace) => {
  emit('select', workspace)
  isOpen.value = false
}
</script>

<template>
  <div class="p-4 border-b border-slate-700">
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
        <div class="text-xs text-slate-400 truncate">
          {{ currentWorkspace?.plan || 'Free Plan' }}
        </div>
      </div>
      <ChevronDownIcon class="w-4 h-4 text-slate-400" />
    </button>

    <!-- Dropdown -->
    <div v-if="isOpen" class="absolute left-4 right-4 mt-2 bg-slate-800 rounded-lg shadow-xl border border-slate-700 z-50">
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
      <div class="border-t border-slate-700 p-2">
        <button class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-700 text-slate-300 text-sm">
          <PlusIcon class="w-4 h-4" />
          Создать пространство
        </button>
      </div>
    </div>
  </div>
</template>
