<script setup>
import { ref, computed, watch } from 'vue'
import { ExclamationCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  start: {
    type: String,
    default: null
  },
  end: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['change'])

const startDate = ref(props.start)
const endDate = ref(props.end)

watch(() => props.start, (val) => { startDate.value = val })
watch(() => props.end, (val) => { endDate.value = val })

const isOverdue = computed(() => {
  if (!endDate.value) return false
  return new Date(endDate.value) < new Date()
})

const emitChange = () => {
  emit('change', {
    start_date: startDate.value,
    due_date: endDate.value
  })
}
</script>

<template>
  <div class="space-y-2">
    <div class="grid grid-cols-2 gap-2">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Начало</label>
        <input
          v-model="startDate"
          @change="emitChange"
          type="date"
          class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Конец</label>
        <input
          v-model="endDate"
          @change="emitChange"
          type="date"
          class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
    </div>
    
    <div v-if="isOverdue" class="text-xs text-red-600 flex items-center gap-1">
      <ExclamationCircleIcon class="w-3 h-3" />
      Срок истек
    </div>
  </div>
</template>