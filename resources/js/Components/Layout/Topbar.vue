<script setup>
import { ref, reactive } from 'vue'
import { 
  MagnifyingGlassIcon, 
  UserIcon, 
  TagIcon, 
  CalendarIcon,
  BellIcon 
} from '@heroicons/vue/24/outline'
import { useDebounceFn } from '@vueuse/core'

const emit = defineEmits(['invite', 'share', 'filter-change'])

const searchQuery = ref('')
const searchResults = ref([])
const activeFilters = reactive({
  assignee: false,
  label: false,
  due_date: false,
})

const handleSearch = useDebounceFn(async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }
  
  // API call to search endpoint
  // const response = await axios.get('/api/search', { params: { q: searchQuery.value } })
  // searchResults.value = response.data
}, 300)

const toggleFilter = (filter) => {
  activeFilters[filter] = !activeFilters[filter]
  emit('filter-change', { ...activeFilters })
}
</script>

<template>
  <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
    <!-- Left: Search & Filters -->
    <div class="flex items-center gap-4 flex-1">
      <!-- Search -->
      <div class="relative w-96">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          v-model="searchQuery"
          @input="handleSearch"
          type="text"
          placeholder="Поиск карточек, досок..."
          class="w-full pl-10 pr-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
        <div v-if="searchResults.length" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
          <div v-for="result in searchResults" :key="result.id" class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
            <div class="text-sm font-medium text-gray-900">{{ result.title }}</div>
            <div class="text-xs text-gray-500">{{ result.type }} · {{ result.board_name }}</div>
          </div>
        </div>
      </div>

      <!-- Quick Filters -->
      <div class="flex items-center gap-2">
        <button 
          @click="toggleFilter('assignee')"
          class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2"
          :class="{ 'bg-blue-50 border-blue-200 text-blue-700': activeFilters.assignee }"
        >
          <UserIcon class="w-4 h-4" />
          Исполнитель
        </button>
        <button 
          @click="toggleFilter('label')"
          class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2"
          :class="{ 'bg-blue-50 border-blue-200 text-blue-700': activeFilters.label }"
        >
          <TagIcon class="w-4 h-4" />
          Метки
        </button>
        <button 
          @click="toggleFilter('due_date')"
          class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 flex items-center gap-2"
          :class="{ 'bg-blue-50 border-blue-200 text-blue-700': activeFilters.due_date }"
        >
          <CalendarIcon class="w-4 h-4" />
          Сроки
        </button>
      </div>
    </div>

    <!-- Right: Actions -->
    <div class="flex items-center gap-3">
      <!-- Notifications -->
      <button class="relative p-2 text-gray-400 hover:text-gray-600">
        <BellIcon class="w-5 h-5" />
        <span v-if="unreadNotifications > 0" class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" />
      </button>

      <!-- Invite Button -->
      <button 
        @click="$emit('invite')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50"
      >
        Пригласить
      </button>

      <!-- Share Button -->
      <button 
        @click="$emit('share')"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
      >
        Поделиться
      </button>
    </div>
  </header>
</template>