<script setup>
import { ref } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  checklists: {
    type: Array,
    default: () => []
  }
})

const newItemContent = ref({})

const addChecklist = () => {
  // Add checklist logic
}

const addItem = (checklistId) => {
  const content = newItemContent.value[checklistId]
  if (!content?.trim()) return
  
  // Add item logic
  newItemContent.value[checklistId] = ''
}

const toggleItem = (checklistId, itemId) => {
  // Toggle item logic
}

const deleteChecklist = (checklistId) => {
  // Delete checklist logic
}
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-900">Чек-листы</h3>
      <button 
        @click="addChecklist"
        class="text-sm text-blue-600 hover:text-blue-700"
      >
        + Добавить
      </button>
    </div>
    
    <div v-if="checklists.length" class="space-y-3">
      <div 
        v-for="checklist in checklists"
        :key="checklist.id"
        class="border border-gray-200 rounded-lg p-3"
      >
        <div class="flex items-center justify-between mb-2">
          <h4 class="text-sm font-medium text-gray-900">{{ checklist.title }}</h4>
          <button 
            @click="deleteChecklist(checklist.id)"
            class="text-gray-400 hover:text-red-600"
          >
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
        
        <div class="space-y-1">
          <div 
            v-for="item in checklist.items"
            :key="item.id"
            class="flex items-center gap-2"
          >
            <input
              type="checkbox"
              :checked="item.completed"
              @change="toggleItem(checklist.id, item.id)"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <span 
              :class="{ 'line-through text-gray-400': item.completed }"
              class="text-sm text-gray-700 flex-1"
            >
              {{ item.content }}
            </span>
          </div>
        </div>
        
        <div class="mt-2 flex items-center gap-2">
          <input
            v-model="newItemContent[checklist.id]"
            @keyup.enter="addItem(checklist.id)"
            type="text"
            placeholder="Добавить пункт..."
            class="flex-1 px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <button 
            @click="addItem(checklist.id)"
            class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700"
          >
            Добавить
          </button>
        </div>
      </div>
    </div>
    
    <div v-else class="p-3 text-sm text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200">
      Чек-листы отсутствуют
    </div>
  </div>
</template>