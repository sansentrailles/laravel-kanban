<script setup>
import { ref, computed } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  value: {
    type: [Number, String],
    default: null
  },
  statuses: {
    type: Array,
    default: () => [
      { id: 'backlog', name: 'Backlog', color: '#6b7280' },
      { id: 'in_progress', name: 'In Progress', color: '#3b82f6' },
      { id: 'review', name: 'Review', color: '#a855f7' },
      { id: 'done', name: 'Done', color: '#10b981' }
    ]
  }
})

const emit = defineEmits(['change'])

const isOpen = ref(false)

const currentStatus = computed(() => {
  return props.statuses.find(s => s.id === props.value)
})

const selectStatus = (status) => {
  emit('change', status.id)
  isOpen.value = false
}
</script>

<template>
  <div class="relative">
    <button 
      @click="isOpen = !isOpen"
      class="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
    >
      <span class="text-sm" :style="{ color: currentStatus?.color }">
        {{ currentStatus?.name || 'Выбрать статус' }}
      </span>
      <ChevronDownIcon class="w-4 h-4 text-gray-400" />
    </button>
    
    <div 
      v-if="isOpen"
      class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 py-1"
    >
      <button
        v-for="status in statuses"
        :key="status.id"
        @click="selectStatus(status)"
        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 transition-colors flex items-center gap-2"
        :class="{ 'bg-gray-50': status.id === value }"
      >
        <span 
          class="w-2 h-2 rounded-full"
          :style="{ backgroundColor: status.color }"
        />
        <span :style="{ color: status.color }">{{ status.name }}</span>
      </button>
    </div>
  </div>
</template>