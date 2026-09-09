<script setup>
import { ref } from 'vue'
import { PlusIcon, MagnifyingGlassIcon, UserIcon, TagIcon, CalendarIcon } from '@heroicons/vue/24/outline'
import { useDebounceFn } from '@vueuse/core'

defineProps({
  board: {
    type: Object,
    required: true
  },
  view: {
    type: String,
    default: 'board'
  }
})

defineEmits(['view-change', 'add-column', 'filter-change'])

const searchQuery = ref('')
const showMemberFilter = ref(false)
const showLabelFilter = ref(false)
const showDateFilter = ref(false)

const handleSearch = useDebounceFn(() => {
  // Emit search event
}, 300)
</script>

<template>
  <header class="bg-white border-b border-gray-200 px-6 py-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ board.name }}</h1>
        <span class="text-sm text-gray-500">{{ board.description }}</span>
      </div>
      
      <div class="flex items-center gap-3">
        <!-- View toggle -->
        <div class="flex bg-gray-100 rounded-lg p-1">
          <button 
            @click="$emit('view-change', 'board')"
            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
            :class="view === 'board' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          >
            Доска
          </button>
          <button 
            @click="$emit('view-change', 'list')"
            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
            :class="view === 'list' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          >
            Список
          </button>
        </div>
        
        <button 
          @click="$emit('add-column')"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2"
        >
          <PlusIcon class="w-4 h-4" />
          Добавить колонку
        </button>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="mt-4 flex items-center gap-3">
      <div class="relative">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          v-model="searchQuery"
          @input="handleSearch"
          type="text"
          placeholder="Поиск..."
          class="pl-10 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      
      <button 
        @click="showMemberFilter = !showMemberFilter"
        class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2"
      >
        <UserIcon class="w-4 h-4" />
        Исполнители
      </button>
      
      <button 
        @click="showLabelFilter = !showLabelFilter"
        class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2"
      >
        <TagIcon class="w-4 h-4" />
        Метки
      </button>
      
      <button 
        @click="showDateFilter = !showDateFilter"
        class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2"
      >
        <CalendarIcon class="w-4 h-4" />
        Сроки
      </button>
    </div>
  </header>
</template>