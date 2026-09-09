<script setup>
import { computed } from 'vue'

const props = defineProps({
  current: {
    type: Number,
    required: true
  },
  limit: {
    type: Number,
    required: true
  }
})

const percentage = computed(() => {
  return Math.min((props.current / props.limit) * 100, 100)
})

const isOverLimit = computed(() => {
  return props.current > props.limit
})

const isNearLimit = computed(() => {
  return props.current >= props.limit * 0.8 && !isOverLimit.value
})
</script>

<template>
  <div class="px-3 pb-2">
    <div class="flex items-center justify-between text-xs mb-1">
      <span class="text-gray-600">WIP Лимит</span>
      <span 
        class="font-medium"
        :class="isOverLimit ? 'text-red-600' : 'text-gray-700'"
      >
        {{ current }} / {{ limit }}
      </span>
    </div>
    <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
      <div 
        class="h-full transition-all duration-300"
        :class="isOverLimit ? 'bg-red-500' : isNearLimit ? 'bg-yellow-500' : 'bg-green-500'"
        :style="{ width: `${percentage}%` }"
      />
    </div>
  </div>
</template>