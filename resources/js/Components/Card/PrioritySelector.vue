<script setup>
const props = defineProps({
  value: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['change'])

// Значения должны совпадать с App\Enums\Kanban\CardPriority.
const priorities = [
  { value: 'low', label: 'Низкий' },
  { value: 'medium', label: 'Средний' },
  { value: 'high', label: 'Высокий' },
  { value: 'urgent', label: 'Срочный' }
]

const priorityClasses = (priority) => {
  const isActive = props.value === priority

  const classes = {
    low: isActive
      ? 'bg-green-100 border-green-300 text-green-700'
      : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50',
    medium: isActive
      ? 'bg-blue-100 border-blue-300 text-blue-700'
      : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50',
    high: isActive
      ? 'bg-orange-100 border-orange-300 text-orange-700'
      : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50',
    urgent: isActive
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
      type="button"
      @click="selectPriority(priority.value)"
      class="flex-1 px-3 py-2 text-sm font-medium rounded-lg border transition-colors"
      :class="priorityClasses(priority.value)"
    >
      {{ priority.label }}
    </button>
  </div>
</template>
