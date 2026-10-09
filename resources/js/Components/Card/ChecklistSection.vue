<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { TrashIcon } from '@heroicons/vue/24/outline'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  checklists: {
    type: Array,
    default: () => []
  },
  cardId: {
    type: [Number, String],
    required: true
  }
})

watch(() => props.checklists, (newVal) => {
  console.log('📦 Checklists в дочернем компоненте обновлены:', newVal)
}, { immediate: true, deep: true })

const emit = defineEmits(['change'])

const newItemContent = ref({})
const isOpen = ref(false)
const newChecklistName = ref('')
const dropdownRef = ref(null)

const form = useForm({
  title: '',
})

const createChecklist = async () => {
  if (!form.title.trim()) return
  
  // Используем axios напрямую для полного контроля над JSON-ответом
  try {
    const response = await axios.post(route('cards.checklist.store', props.cardId), {
      title: form.title
    })
    
    const newChecklist = response.data.createdChecklist
    
    if (newChecklist) {
      emit('change', [...props.checklists, newChecklist])
      form.reset() // Сбрасываем нашу локальную форму
      isOpen.value = false
    }
  } catch (error) {
    // Обработка ошибок валидации (если вернется 422)
    if (error.response && error.response.status === 422) {
      form.errors = error.response.data.errors
    } else {
      console.error('Ошибка создания чеклиста:', error)
    }
  }
}

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
  console.log(checklistId)
  // Delete checklist logic
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false
    newChecklistName.value = ''
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-900">Чек-листы</h3>
      <!--  -->
      <div class="relative" ref="dropdownRef">
        <button 
          @click="isOpen = !isOpen"
          class="text-sm text-blue-600 hover:text-blue-700"
        >
          Добавить +
        </button>
        <div 
          v-if="isOpen"
          class="absolute right-0 top-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-3 min-w-[250px] z-10"
        >
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Название</label>
            <input
              v-model="form.title"
              type="text"
              placeholder="Введите название чеклиста"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-50"
              @keyup.enter="createChecklist"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
          </div>
          
          <button
            @click="createChecklist"
            :disabled="!form.title.trim() || form.processing"
            class="w-full px-3 py-1.5 text-sm font-medium text-white bg-blue-500 rounded hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
          >
            {{ form.processing ? 'Создание...' : 'Создать чеклист' }}
          </button>
        </div>
      </div>
      <!--  -->
      <!-- 
      <button 
        @click="addChecklist"
        class="text-sm text-blue-600 hover:text-blue-700"
      >
        + Добавить
      </button>
      -->      
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
            class="flex-1 px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-100"
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