<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Checklist from './Checklist.vue'

const props = defineProps({
  checklists: { type: Array, default: () => [] },
  cardId: { type: [Number, String], required: true }
})

const emit = defineEmits(['create-checklist', 'delete-checklist', 'add-item', 'toggle-item', 'delete-item', 'to-card'])

const isOpen = ref(false)
const form = useForm({ title: '' })

const createChecklist = () => {
  if (!form.title.trim()) return
  emit('create-checklist', form.title)
  form.reset()
  isOpen.value = false
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
        @delete="(id) => emit('delete-checklist', id)"
        @add-item="(content) => emit('add-item', checklist.id, content)"
        @toggle-item="(itemId) => emit('toggle-item', checklist.id, itemId)"
        @delete-item="(itemId) => emit('delete-item', checklist.id, itemId)"
        @to-card="(itemId) => emit('to-card', checklist.id, itemId)"
      />
    </div>
    <div v-else class="p-3 text-sm text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200">
      Чек-листы отсутствуют
    </div>
  </div>
</template>