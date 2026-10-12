<script setup>
import { ref } from 'vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  item: { type: Object, required: true }
})

const emit = defineEmits(['toggle', 'delete', 'to-card'])
const isMenuOpen = ref(false)
const isDeleteOpen = ref(false)

const toggleMenu = () => { isMenuOpen.value = !isMenuOpen.value }
const closeMenu = () => { isMenuOpen.value = false }

const handleToggle = () => emit('toggle', props.item.id)

const handleToCard = () => { 
  isMenuOpen.value = false
  emit('to-card', props.item.id) 
}

const showDeleteConfirm = () => {
  closeMenu()
  isDeleteOpen.value = true
}

const closeDeleteConfirm = () => {
  isDeleteOpen.value = false
}

// ✅ Добавлена недостающая функция
const confirmDelete = () => {
  isDeleteOpen.value = false
  emit('delete', props.item.id)
}
</script>

<template>
  <!-- Корневой контейнер делаем колонкой -->
  <div class="relative flex flex-col group">
    
    <!-- Строка с пунктом чеклиста -->
    <div class="flex items-center gap-2">
      <input 
        type="checkbox" 
        :checked="props.item.completed" 
        @change="handleToggle"
        class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-400 cursor-pointer" 
      />
      <span 
        :class="{ 'line-through text-gray-400': props.item.completed }" 
        class="text-sm text-gray-700 flex-1"
      >
        {{ props.item.content }}
      </span>

      <!-- Кнопка открытия меню -->
      <div class="relative">
        <button
          @click="toggleMenu" 
          class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <EllipsisVerticalIcon class="w-5 h-5" />
        </button>

        <!-- Выпадающее меню -->
        <template v-if="isMenuOpen">
          <div class="fixed inset-0 z-10" @click="closeMenu"></div>
          <div class="absolute right-0 top-full mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-100 z-20 py-1">
            <button 
              @click="handleToCard" 
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
            >
              Переделать в карточку
            </button>
            <button 
              @click="showDeleteConfirm" 
              class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
            >
              Удалить
            </button>
          </div>
        </template>
      </div>
    </div>

    <!-- Окно подтверждения удаления (теперь оно под пунктом и растягивается на всю ширину) -->
    <div v-if="isDeleteOpen" class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md w-full">
      <p class="text-sm text-gray-700 mb-3">Вы действительно хотите удалить этот пункт?</p>
      <div class="flex justify-end gap-2">
        <button 
          @click="closeDeleteConfirm"
          class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-200 rounded transition-colors"
        >
          Отмена
        </button>
        <button 
          @click="confirmDelete"
          class="px-3 py-1.5 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded transition-colors"
        >
          Удалить
        </button>
      </div>
    </div>
    
  </div>
</template>