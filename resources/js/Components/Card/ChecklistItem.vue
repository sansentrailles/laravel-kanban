<script setup>
import { ref } from 'vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  item: { type: Object, required: true }
})

const emit = defineEmits(['toggle', 'delete', 'to-card'])
const isMenuOpen = ref(false)

// Упрощаем эмитеры
const handleToggle = () => emit('toggle', props.item.id)
const handleDelete = () => { isMenuOpen.value = false; emit('delete', props.item.id) }
const handleToCard = () => { isMenuOpen.value = false; emit('to-card', props.item.id) }
</script>

<template>
  <div class="relative flex items-center gap-2 group">
    <input type="checkbox" :checked="props.item.completed" @change="handleToggle"
      class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-400 cursor-pointer" />
    <span :class="{ 'line-through text-gray-400': props.item.completed }" class="text-sm text-gray-700 flex-1">
      {{ props.item.content }}
    </span>

    <button 
      @click="toggleMenu" 
      class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none opacity-0 group-hover:opacity-100 transition-opacity"
    >
      <EllipsisVerticalIcon class="w-5 h-5" />
    </button>

    <template v-if="isMenuOpen">
      <!-- Прозрачный фон для перехвата кликов вне меню -->
      <div class="fixed inset-0 z-10" @click="closeMenu"></div>

      <!-- Само меню -->
      <div class="absolute right-0 top-full mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-100 z-20 py-1">
        <button @click="emit('to-card', props.item.id)"
          class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          Переделать в карточку
        </button>
        <button @click="handleDelete" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
          Удалить
        </button>
      </div>
    </template>
  </div>
</template>