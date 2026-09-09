<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline'

defineProps({
  boards: {
    type: Array,
    required: true
  },
  activeBoardId: {
    type: [Number, null],
    default: null
  }
})

defineEmits(['select', 'create'])
</script>

<template>
  <div class="px-4 space-y-1">
    <button
      v-for="board in boards"
      :key="board.id"
      @click="$emit('select', board)"
      class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors text-left"
      :class="activeBoardId === board.id 
        ? 'bg-slate-800 text-white' 
        : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
    >
      <div 
        class="w-2 h-2 rounded-full flex-shrink-0"
        :style="{ backgroundColor: board.color || '#3b82f6' }"
      />
      <span class="flex-1 truncate">{{ board.name }}</span>
      <span class="text-xs text-slate-500">{{ board.cards_count || 0 }}</span>
    </button>
    
    <button 
      @click="$emit('create')"
      class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-400 hover:bg-slate-800 hover:text-white transition-colors"
    >
      <PlusIcon class="w-4 h-4" />
      <span>Новая доска</span>
    </button>
  </div>
</template>