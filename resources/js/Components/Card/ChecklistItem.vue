<script setup>
import { ref } from 'vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  item: {
    type: Object, // Исправлено: должен быть Object, а не Array
    required: true
  },
  checklistId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['delete'])

// Состояние выпадающего меню
const isMenuOpen = ref(false)

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}

const closeMenu = () => {
  isMenuOpen.value = false
}

// Действия меню (просто закрываем меню и эмитим событие наверх)
const handleEdit = () => {
  closeMenu()
  emit('edit', props.item.id)
}

const handleDelete = () => {
  closeMenu()
  emit('delete', props.item.id)
}
</script>

<template>
  <!-- Добавлен class relative для позиционирования меню -->
  <div class="relative flex items-center gap-2 group">
    <input
      type="checkbox"
      :checked="props.item.completed"
      @change="emit('toggle', props.checklistId, props.item.id)"
      class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-400 focus:ring-1"
    />
    <span :class="{ 'line-through text-gray-400': props.item.completed }" class="text-sm text-gray-700 flex-1">
      {{ props.item.content }} {{ props.item.id }}
    </span>

    <!-- Кнопка открытия меню -->
    <button 
      @click="toggleMenu" 
      class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none"
    >
      <EllipsisVerticalIcon class="w-5 h-5" />
    </button>

    <!-- Всплывающее меню -->
    <template v-if="isMenuOpen">
      <!-- Прозрачный фон для перехвата кликов вне меню (закрывает меню) -->
      <div class="fixed inset-0 z-10" @click="closeMenu"></div>

      <!-- Само меню -->
      <div class="absolute right-0 top-full mt-1 w-50 bg-white rounded-md shadow-lg border border-gray-100 z-20">
        <button 
          @click="emit('to-card', props.checklistId, props.item.id)" 
          class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
        >
          Переделать в карточку
        </button>
        <button 
          @click="handleDelete" 
          class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
        >
          Удалить
        </button>
      </div>
    </template>
  </div>
</template>