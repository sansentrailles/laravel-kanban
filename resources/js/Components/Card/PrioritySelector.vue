<script setup>
const props = defineProps({
  value: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['change'])

const priorities = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' }
]

const priorityClasses = (priority) => {
  const isActive = props.value === priority
  
  const classes = {
    low: isActive 
      ? 'bg-green-100 border-green-300 text-green-700' 
      : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50',
    medium: isActive 
      ? 'bg-yellow-100 border-yellow-300 text-yellow-700' 
      : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50',
    high: isActive 
      ? 'bg-red-100 border-red-300 text-red-700' 
      : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
  }
  
  return classes[priority]
}

const selectPriority = (priority) => {
  emit('change', priority)
}
</script>

<template>
  <div class="flex gap-2">
    <button
      v-for="priority in priorities"
      :key="priority.value"
      @click="selectPriority(priority.value)"
      class="flex-1 px-3 py-2 text-sm font-medium rounded-lg border transition-colors"
      :class="priorityClasses(priority.value)"
    >
      {{ priority.label }}
    </button>
  </div>
</template>