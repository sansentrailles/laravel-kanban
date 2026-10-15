<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useBoardStore } from '@/Stores/board';

const props = defineProps({
  value: {
    type: Array,
    default: () => []
  },
  availableLabels: {
    type: Array,
    default: () => []
  },
  workspaceId: {
    type: [Number, String],
    required: true
  }
})

const store = useBoardStore()

const emit = defineEmits(['change'])

const isOpen = ref(false)
const newLabelName = ref('')
const newLabelColor = ref('#f3da4a')
const isCreating = ref(false)
const errorMessage = ref('')
const dropdownRef = ref(null)

// ✅ Вычисляем список меток, которые еще НЕ выбраны у этой карточки
const unselectedLabels = computed(() => {
  return props.availableLabels.filter(label => {
    return !props.value.some(selectedLabel => selectedLabel.id === label.id)
  })
})

const addLabel = (label) => {
  if (!props.value.find(l => l.id === label.id)) {
    emit('change', [...props.value, label])
  }
}

const removeLabel = (labelId) => {
  emit('change', props.value.filter(l => l.id !== labelId))
}

const createNewLabel = async () => {
  if (!newLabelName.value.trim()) return
  
  isCreating.value = true
  errorMessage.value = ''

  try {
    const createdLabel = await store.createLabel(props.workspaceId, {
      name: newLabelName.value.trim(),
      color: newLabelColor.value,
      description: '',
    })
      
    // Сброс формы
    newLabelName.value = ''
    newLabelColor.value = '#f3da4a'
    isOpen.value = false
  } catch (error) {
    console.log(error);
    
    errorMessage.value = error.response?.data?.message || 'Не удалось создать метку'
  } finally {
    isCreating.value = false
  }
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false
    newLabelName.value = ''
    errorMessage.value = ''
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
  <div class="space-y-2">
    <!-- Current labels -->
    <div class="flex flex-wrap gap-1.5">
      <div 
        v-for="label in value"
        :key="label.id"
        class="flex items-center gap-1 px-2 py-1 mb-2 rounded-full text-xs font-medium"
        :style="{ backgroundColor: label.color + '20', color: label.color }"
      >
        {{ label.name }}
        <button 
          @click="removeLabel(label.id)"
          class="hover:opacity-70"
        >
          <XMarkIcon class="w-3 h-3" />
        </button>
      </div>
    </div>
    
    <!-- Available labels -->
    <div class="flex items-start gap-3">
      <div class="flex flex-wrap gap-1.5 flex-1">
        <button
          v-for="label in unselectedLabels"
          :key="label.id"
          @click="addLabel(label)"
          class="px-2 py-1 mr-1 rounded-full text-xs font-medium hover:opacity-80 transition-opacity"
          :style="{ backgroundColor: label.color + '20', color: label.color }"
        >
          + {{ label.name }}
        </button>
      </div>      

        <!-- Дропдаун создания -->
      <div class="relative shrink-0" ref="dropdownRef">
        <button @click="isOpen = !isOpen" class="text-sm text-blue-600 hover:text-blue-700">
          Добавить +
        </button>
        
        <div 
          v-if="isOpen"
          class="absolute right-0 top-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-3 min-w-[250px] z-10"
        >
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Название</label>
            <input
              v-model="newLabelName"
              type="text"
              placeholder="Название метки"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
              @keyup.enter="createNewLabel"
              :disabled="isCreating"
            />
            <p v-if="errorMessage" class="mt-1 text-xs text-red-600">{{ errorMessage }}</p>
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Цвет</label>
            <div class="flex items-center gap-2">
              <input
                v-model="newLabelColor"
                type="color"
                class="w-8 h-8 rounded cursor-pointer border border-gray-300 p-0"
                :disabled="isCreating"
              />
              <input
                v-model="newLabelColor"
                type="text"
                class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-100 font-mono"
                :disabled="isCreating"
              />
            </div>
          </div>
          
          <!-- Превью -->
          <div class="flex items-center gap-2 p-2 rounded-full" :style="{ backgroundColor: newLabelColor + '20' }">
            <span class="text-xs font-medium" :style="{ color: newLabelColor }">
              {{ newLabelName.trim() || 'Пример метки' }}
            </span>
          </div>
          
          <button
            @click="createNewLabel"
            :disabled="!newLabelName.trim() || isCreating"
            class="w-full px-3 py-1.5 text-sm font-medium text-white bg-blue-500 rounded hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
          >
            <span v-if="isCreating" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            {{ isCreating ? 'Создание...' : 'Создать метку' }}
          </button>
        </div>
      </div>
      
    </div>
  </div>
</template>