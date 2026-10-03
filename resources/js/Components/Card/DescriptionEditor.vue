<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
  value: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update'])

const isEditing = ref(false)
const content = ref('')
const descInput = ref(null)

const formattedValue = computed(() => {
  // Simple markdown-like formatting
  return props.value
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/`(.*?)`/g, '<code>$1</code>')
})

const startEditing = async () => {
  content.value = props.value
  isEditing.value = true

  await nextTick()
  descInput.value?.focus()
}

const save = () => {
  emit('update', content.value)
  isEditing.value = false
}

const cancel = () => {
  content.value = props.value
  isEditing.value = false
}

watch(() => props.value, (newValue) => {
  content.value = newValue
})
</script>

<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-900">Описание</h3>
      <button 
        v-if="!isEditing"
        @click="startEditing"
        class="text-sm text-gray-600 hover:text-gray-900"
      >
        Редактировать
      </button>
    </div>
    
    <div v-if="isEditing" class="">
      <textarea
        v-model="content"
        ref="descInput"
        @blur="save"
        @keydown.ctrl.enter="save"
        placeholder="Добавьте более подробное описание..."
        class="w-full p-3 text-sm resize-none focus:ring-0 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-100"
        rows="4"
      ></textarea>

      <div class="px-3 py-2 flex justify-end">
        <button 
          @click="cancel"
          class="px-2 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded mr-2"
        >
          Отмена
        </button>
        <button 
          @click="save"
          class="px-2 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700"
        >
          Сохранить
        </button>
      </div>
    </div>

    
    
    <div 
      v-else-if="value"
      class="p-3 text-sm text-gray-700 bg-gray-50 rounded-lg whitespace-pre-wrap"
      v-html="formattedValue"
    ></div>
    
    <div 
      v-else
      class="p-3 text-sm text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200"
    >
      Описание отсутствует
    </div>
  </div>
</template>