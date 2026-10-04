<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useForm } from '@inertiajs/vue3'

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

const emit = defineEmits(['change'])

const isOpen = ref(false)
const newLabelName = ref('')
const dropdownRef = ref(null)

const form = useForm({
  name: '',
  color: '',
  description: '',
})

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

const createNewLabel = () => {
  if (!form.name.trim()) return

  form.post(route('workspaces.labels.store', props.workspaceId), {
    preserveScroll: true,
    onSuccess: (page) => {
      // Бэкенд возвращает созданную метку
      const newLabel = page.props.createdLabel
      
      // Добавляем её в список выбранных
      emit('change', [...props.value, newLabel])
      
      // Сбрасываем форму
      form.reset()
      isOpen.value = false
      
      // Родитель должен обновить список availableLabels
      // (через Inertia это произойдет автоматически, если передать через share)
    },
  })
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false
    newLabelName.value = ''
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
        class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium"
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
    <div class="flex flex-wrap gap-1.5 justify-between">
      <div>
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

      <!-- Кнопка создания новой метки -->
      <div class="relative" ref="dropdownRef">
        <button 
          @click="isOpen = !isOpen"
          class="border-2 border-transparent hover:border-gray-300 px-2 py-2 rounded-2xl" 
          title="Добавить метку"
        >
          <PlusIcon class="w-3 h-3"/>
        </button>
        
        <div 
          v-if="isOpen"
          class="absolute right-0 top-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-3 min-w-[250px] z-10"
        >
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Название</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="Введите название метки"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-50"
              @keyup.enter="createNewLabel"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Цвет</label>
            <div class="flex items-center gap-2">
              <input
                v-model="form.color"
                type="color"
                class="w-8 h-8 rounded cursor-pointer border border-gray-300"
              />
              <input
                v-model="form.color"
                type="text"
                class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-100 font-mono"
                placeholder="#000000"
              />
            </div>
          </div>
          
          <!-- Превью -->
          <div class="flex items-center gap-2 p-2 rounded-full" :style="{ backgroundColor: form.color + '20' }">
            <span class="text-xs font-medium" :style="{ color: form.color }">
              {{ form.name || 'Пример метки' }}
            </span>
          </div>
          
          <button
            @click="createNewLabel"
            :disabled="!form.name.trim() || form.processing"
            class="w-full px-3 py-1.5 text-sm font-medium text-white bg-blue-500 rounded hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
          >
            {{ form.processing ? 'Создание...' : 'Создать метку' }}
          </button>
        </div>
      </div>
      
    </div>
  </div>
</template>