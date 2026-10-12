<script setup>
import { computed, ref } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'
import ChecklistItem from './ChecklistItem.vue'

const props = defineProps({
  checklist: {
    type: Object, // Исправлено: это объект, а не массив
    required: true
  },
})

const emit = defineEmits(['delete', 'update', 'to-card'])

const newItemContent = ref('')
const isDeleteOpen = ref(false)

const sortedItems = computed(() => {
  return [...props.checklist.items].sort(
    (a, b) => Number(a.completed) - Number(b.completed)
  )
})

const closeDeleteConfirm = () => {
  isDeleteOpen.value = false
}

const deleteChecklist = () => {  
  isDeleteOpen.value = false
  emit('delete', props.checklist.id)
}

const addItem = async () => {
  const content = newItemContent.value.trim()
  if (!content) return

  try {
    const response = await axios.post(route('cards.checklist.items.store', props.checklist.id), {
      content: content
    })

    const newChecklistItem = response.data.createdChecklistItem

    if (newChecklistItem) {
      // Отдаем наверх новый обновленный объект чеклиста
      emit('update', {
        ...props.checklist,
        items: [...props.checklist.items, newChecklistItem]
      })
      newItemContent.value = ''
    }
  } catch (error) {
    console.error('Ошибка создания пункта чеклиста:', error)
  }
}

const toggleItem = async (itemId) => {
  try {
    const response = await axios.patch(route('cards.checklist.item.toggle', { 
      checklist: props.checklist.id, 
      item: itemId 
    }))

    const updatedChecklistItems = response.data.updatedChecklistItems
    if (updatedChecklistItems) {
      emit('update', {
        ...props.checklist,
        items: updatedChecklistItems
      })
    }
  } catch (error) {
    console.error('Ошибка переключения пункта чеклиста:', error)
  }
}

const deleteItem = async (itemId) => {
  try {
    // Предполагается, что такой роут есть на бэкенде
    await axios.delete(route('cards.checklist.item.delete', { 
      checklist: props.checklist.id, 
      item: itemId 
    }))
    
    emit('update', {
      ...props.checklist,
      items: props.checklist.items.filter(i => i.id !== itemId)
    })
  } catch (error) {
    console.error('Ошибка удаления пункта чеклиста:', error)
  }
}

const convertToCard = (itemId) => {
  emit('to-card', props.checklist.id, itemId)
}
</script>

<template>
  <div class="border border-gray-200 rounded-lg p-3">
    <div class="flex items-center justify-between mb-2">
      <h4 class="text-sm font-medium text-gray-900">{{ props.checklist.title }}</h4>

      <div class="relative">
        <button @click="isDeleteOpen = !isDeleteOpen" class="text-gray-400 hover:text-red-600">
          <TrashIcon class="w-4 h-4" />
        </button>
        
        <template v-if="isDeleteOpen">
          <div class="fixed inset-0 z-10" @click="closeDeleteConfirm"></div>
          <div class="absolute right-0 top-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-3 min-w-[250px] z-20">
            <p class="text-sm text-gray-600">Вы действительно хотите удалить чеклист?</p>
            <button @click="deleteChecklist"
              class="w-full px-3 py-1.5 text-sm font-medium text-white bg-red-500 rounded hover:bg-red-600 transition-colors">
              Удалить
            </button>
          </div>
        </template>
      </div>
    </div>

    <div class="space-y-1 mb-2">
      <ChecklistItem
        v-for="item in sortedItems"
        :item="item"
        :key="item.id"
        :checklist-id="props.checklist.id"
        @toggle="toggleItem"
        @delete="deleteItem"
        @to-card="convertToCard"
      />
    </div>

    <div class="mt-2 flex items-center gap-2">
      <input 
        v-model="newItemContent"
        @keyup.enter="addItem" 
        type="text"
        placeholder="Добавить пункт..."
        class="flex-1 px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-100" 
      />
      <button 
        @click="addItem"
        :disabled="!newItemContent.trim()"
        class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:bg-gray-300 transition-colors"
      >
        Добавить
      </button>
    </div>
  </div>
</template>