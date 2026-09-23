<script setup>
import { ref, reactive, onMounted } from 'vue'

// const props = defineProps({
//   columnId: {
//     type: [Number, String],
//     required: true
//   }
// })

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
  })
  
  form.title = ''
}
</script>

<template>
  <form @submit.prevent="submit" class="p-2">
    <input 
      type="text"
      v-model="form.title"
      ref="titleInput"
      placeholder="Введите название пространства..."
      class="w-full p-2 text-sm border border-slate-700 rounded focus:outline-none bg-slate-800 focus:ring-1 focus:ring-slate-700 resize-none"
      autofocus
    >
    <div class="flex gap-2 my-4 justify-end">
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
  </form>
</template>