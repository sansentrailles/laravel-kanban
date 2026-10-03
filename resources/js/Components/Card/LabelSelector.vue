<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  value: {
    type: Array,
    default: () => []
  },
  availableLabels: {
    type: Array,
    default: () => [
      { id: 1, name: 'Bug', color: '#ef4444' },
      { id: 2, name: 'Feature', color: '#3b82f6' },
      { id: 3, name: 'Design', color: '#a855f7' },
      { id: 4, name: 'Backend', color: '#10b981' },
      { id: 5, name: 'Frontend', color: '#f59e0b' }
    ]
  }
})

const emit = defineEmits(['change'])

const isOpen = ref(false)
const newLabelName = ref('')
const newLabelColor = ref('#6b7280')
const dropdownRef = ref(null)

const addLabel = (label) => {
  console.log(label)
  if (!props.value.find(l => l.id === label.id)) {
    emit('change', [...props.value, label])
  }
}

const removeLabel = (labelId) => {
  console.log(labelId)
  emit('change', props.value.filter(l => l.id !== labelId))
}

const createNewLabel = () => {
  if (!newLabelName.value.trim()) return
  
  const newLabel = {
    id: Date.now(),
    name: newLabelName.value.trim(),
    color: newLabelColor.value
  }
  
  emit('change', [...props.value, newLabel])
  
  newLabelName.value = ''
  newLabelColor.value = '#6b7280'
  isOpen.value = false
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
          v-for="label in availableLabels"
          :key="label.id"
          @click="addLabel(label)"
          class="px-2 py-1 mr-1 rounded-full text-xs font-medium hover:opacity-80 transition-opacity"
          :style="{ backgroundColor: label.color + '20', color: label.color }"
        >
          + {{ label.name }}
        </button>
      </div>      

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
              v-model="newLabelName"
              type="text"
              placeholder="Введите название метки"
              class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-50"
              @keyup.enter="createNewLabel"
            />
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Цвет</label>
            <div class="flex items-center gap-2">
              <input
                v-model="newLabelColor"
                type="color"
                class="w-8 h-8 rounded cursor-pointer border border-gray-300"
              />
              <input
                v-model="newLabelColor"
                type="text"
                class="flex-1 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-100 font-mono"
                placeholder="#000000"
              />
            </div>
          </div>
          
          <div class="flex items-center gap-2 p-2 rounded-full" :style="{ backgroundColor: newLabelColor + '20' }">
            <span class="text-xs font-medium" :style="{ color: newLabelColor }">
              {{ newLabelName || 'Пример метки' }}
            </span>
          </div>
          
          <button
            @click="createNewLabel"
            :disabled="!newLabelName.trim()"
            class="w-full px-3 py-1.5 text-sm font-medium text-white bg-blue-500 rounded hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
          >
            Добавить метку
          </button>
        </div>
      </div>
      
    </div>
  </div>
</template>