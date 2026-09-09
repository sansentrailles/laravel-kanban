<script setup>
import { computed } from 'vue'
import { XIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  value: {
    type: Array,
    default: () => []
  },
  availableLabels: {
    type: Array,
    default: () => [
      { id: 1, name: 'Bug', color: '#ef4444' },
      { id: 2, name: 'Feature', color: '#3b82f6' },
      { id: 3, name: 'Design', color: '#a855f7' },
      { id: 4, name: 'Backend', color: '#10b981' },
      { id: 5, name: 'Frontend', color: '#f59e0b' }
    ]
  }
})

const emit = defineEmits(['change'])

const addLabel = (label) => {
  if (!props.value.find(l => l.id === label.id)) {
    emit('change', [...props.value, label])
  }
}

const removeLabel = (labelId) => {
  emit('change', props.value.filter(l => l.id !== labelId))
}
</script>

<template>
  <div class="space-y-2">
    <!-- Current labels -->
    <div class="flex flex-wrap gap-1.5">
      <div 
        v-for="label in value"
        :key="label.id"
        class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
        :style="{ backgroundColor: label.color + '20', color: label.color }"
      >
        {{ label.name }}
        <button 
          @click="removeLabel(label.id)"
          class="hover:opacity-70"
        >
          <XIcon class="w-3 h-3" />
        </button>
      </div>
    </div>
    
    <!-- Available labels -->
    <div class="flex flex-wrap gap-1.5">
      <button
        v-for="label in availableLabels"
        :key="label.id"
        @click="addLabel(label)"
        class="px-2 py-1 rounded-full text-xs font-medium hover:opacity-80 transition-opacity"
        :style="{ backgroundColor: label.color + '20', color: label.color }"
      >
        + {{ label.name }}
      </button>
    </div>
  </div>
</template>