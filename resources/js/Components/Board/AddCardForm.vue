<script setup>
import { ref, reactive, onMounted } from 'vue'
import { DocumentTextIcon, CheckSquareIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  columnId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  title: ''
})

const titleInput = ref(null)

onMounted(() => {
  titleInput.value?.focus()
})

const submit = () => {
  if (!form.title.trim()) return
  
  emit('submit', {
    title: form.title,
    column_id: props.columnId
  })
  
  form.title = ''
}
</script>

<template>
  <form @submit.prevent="submit" class="bg-white p-3 rounded-lg shadow-sm border border-gray-200">
    <textarea
      v-model="form.title"
      ref="titleInput"
      placeholder="Введите название карточки..."
      class="w-full p-2 text-sm border border-gray-200 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
      rows="3"
      autofocus
    ></textarea>
    
    <div class="mt-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <button 
          type="button"
          class="p-1.5 text-gray-500 hover:bg-gray-100 rounded"
          title="Добавить описание"
        >
          <DocumentTextIcon class="w-4 h-4" />
        </button>
        <button 
          type="button"
          class="p-1.5 text-gray-500 hover:bg-gray-100 rounded"
          title="Добавить чек-лист"
        >
          <CheckSquareIcon class="w-4 h-4" />
        </button>
      </div>
      
      <div class="flex items-center gap-2">
        <button 
          type="button"
          @click="$emit('cancel')"
          class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded"
        >
          Отмена
        </button>
        <button 
          type="submit"
          :disabled="!form.title.trim()"
          class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Добавить
        </button>
      </div>
    </div>
  </form>
</template>