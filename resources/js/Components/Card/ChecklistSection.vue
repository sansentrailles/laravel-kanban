<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Checklist from './Checklist.vue'

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

const emit = defineEmits(['change', 'to-card'])

const isOpen = ref(false)
const form = useForm({ title: '' })

const createChecklist = async () => {
  if (!form.title.trim()) return

  try {
    const response = await axios.post(route('cards.checklist.store', props.cardId), {
      title: form.title
    })

    const newChecklist = response.data.createdChecklist

    if (newChecklist) {
      emit('change', [...props.checklists, newChecklist])
      form.reset()
      isOpen.value = false
    }
  } catch (error) {
    if (error.response && error.response.status === 422) {
      form.errors = error.response.data.errors
    } else {
      console.error('Ошибка создания чеклиста:', error)
    }
  }
}

const deleteChecklist = async (id) => {
  try {
    const response = await axios.delete(route('cards.checklist.delete', id))
    const newChecklists = response.data.checklists
    if (newChecklists) {
      emit('change', newChecklists)
    }
  } catch (error) {
    console.error('Ошибка удаления чеклиста:', error)
  }  
}

// Обновляем конкретный чеклист в массиве
const updateChecklist = (updatedChecklist) => {
  const newChecklists = props.checklists.map(c => 
    c.id === updatedChecklist.id ? updatedChecklist : c
  )
  emit('change', newChecklists)
}

const handleToCard = (checklistId, itemId) => {
  emit('to-card', checklistId, itemId)
}
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-900">Чек-листы</h3>
      <div class="relative">
        <button @click="isOpen = !isOpen" class="text-sm text-blue-600 hover:text-blue-700">
          Добавить +
        </button>
        
        <template v-if="isOpen">
          <!-- Overlay для закрытия по клику вне -->
          <div class="fixed inset-0 z-10" @click="isOpen = false"></div>
          
          <div class="absolute right-0 top-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-3 min-w-[250px] z-20">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Название</label>
              <input 
                v-model="form.title" 
                type="text" 
                placeholder="Введите название чеклиста"
                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-100"
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
        </template>
      </div>
    </div>

    <div v-if="checklists.length" class="space-y-3">
      <Checklist
        v-for="checklist in checklists"
        :key="checklist.id"
        :checklist="checklist"
        @update="updateChecklist"
        @delete="deleteChecklist"
        @to-card="handleToCard"
      />
    </div>

    <div v-else class="p-3 text-sm text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200">
      Чек-листы отсутствуют
    </div>
  </div>
</template>