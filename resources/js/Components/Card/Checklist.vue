<script setup>
import { computed, ref } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'
import ChecklistItem from './ChecklistItem.vue'
import ChecklistProgress from './ChecklistProgress.vue'

const props = defineProps({
  checklist: { type: Object, required: true }
})

const emit = defineEmits(['delete', 'add-item', 'toggle-item', 'delete-item', 'to-card'])

const newItemContent = ref('')
const isDeleteOpen = ref(false)

const sortedItems = computed(() => {
  return [...props.checklist.items].sort((a, b) => Number(a.completed) - Number(b.completed))
})

// ✅ Добавляем метод для закрытия окна подтверждения
const closeDeleteConfirm = () => {
  isDeleteOpen.value = false
}

const deleteChecklist = () => {  
  isDeleteOpen.value = false
  emit('delete', props.checklist.id)
}

const addItem = () => {
  const content = newItemContent.value.trim()
  if (!content) return
  emit('add-item', content)
  newItemContent.value = ''
}

const convertToCard = (itemId) => emit('to-card', itemId)
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
          <!-- Оверлей для закрытия при клике мимо -->
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

    <ChecklistProgress
      v-if="checklist.items.length > 0"
      :items="checklist.items"
    />

    <div class="space-y-1 mb-2">
      <ChecklistItem
        v-for="item in sortedItems"
        :item="item"
        :key="item.id"
        @toggle="emit('toggle-item', $event)"
        @delete="emit('delete-item', $event)"
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
        class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:bg-gray-300"
      >
        Добавить
      </button>
    </div>
  </div>
</template>