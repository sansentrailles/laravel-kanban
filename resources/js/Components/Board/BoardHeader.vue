<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline'

defineProps({
  board: {
    type: Object,
    required: true
  },
  view: {
    type: String,
    default: 'board'
  }
})

defineEmits(['add-column', 'filter-change'])

</script>

<template>
  <header class="bg-white border-b border-gray-200 px-6 py-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <div 
          v-if="board.color"
          class="w-6 h-6 rounded-full flex-shrink-0 shadow-sm"
          :style="{ backgroundColor: board.color }"
        />
        <h1 class="text-2xl font-bold text-gray-900">          
          {{ board.name }}
        </h1>
        <span class="text-sm text-gray-500">{{ board.description }}</span>
      </div>
      
      <div class="flex items-center gap-3">        
        <button 
          @click="$emit('add-column')"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2"
        >
          <PlusIcon class="w-4 h-4" />
          Добавить колонку
        </button>
      </div>
    </div>
  </header>
</template>