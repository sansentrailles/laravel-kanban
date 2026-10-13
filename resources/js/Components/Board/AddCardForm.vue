<!-- Components/Board/AddCardForm.vue -->
<script setup>
import { ref, onMounted, nextTick } from 'vue'

const props = defineProps({
  columnId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['submit', 'cancel'])

const title = ref('')
const titleInput = ref(null)

onMounted(async () => {
  await nextTick()
  titleInput.value?.focus()
})

const submit = () => {
  if (!title.value.trim()) return
  
  // Отправляем только название
  emit('submit', title.value)
  title.value = ''
}
</script>

<template>
  <form @submit.prevent="submit" class="bg-white p-2 rounded-lg shadow-sm border border-gray-200">
    <input 
      type="text" 
      v-model="title"
      ref="titleInput"
      placeholder="Введите название карточки..."
      class="w-full p-2 text-sm border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-100 resize-none mb-2"
      @keydown.esc="$emit('cancel')"
    />
    
    <div class="flex items-center justify-end gap-2">
      <button 
        type="button"
        @click="$emit('cancel')"
        class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded"
      >
        Отмена
      </button>
      <button 
        type="submit"
        :disabled="!title.trim()"
        class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Добавить
      </button>
    </div>
  </form>
</template>