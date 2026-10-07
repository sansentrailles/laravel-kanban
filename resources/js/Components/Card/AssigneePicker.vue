<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import Avatar from '@/Components/UI/Avatar.vue'
import { UserIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  value: {
    type: Array,
    default: () => []
  },
  availableUsers: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['change'])

const searchQuery = ref('')
const isOpen = ref(false)
const dropdownRef = ref(null)
const highlightedIndex = ref(-1) // индекс выбранного элемента для навигации с клавиатуры



// ✅ Умная фильтрация: ищет по имени или email, исключает уже назначенных
const filteredUsers = computed(() => {
  const assignedIds = props.value.map(u => u.id)
  const query = searchQuery.value.toLowerCase().trim()
  
  return props.availableUsers.filter(user => {
    const isAssigned = assignedIds.includes(user.id)
    const matchesQuery = query === '' || 
                         user.name.toLowerCase().includes(query) || 
                         (user.email && user.email.toLowerCase().includes(query))
    
    return !isAssigned && matchesQuery
  })
})

watch(filteredUsers, () => {
  highlightedIndex.value = -1
})

const clearSeach = () => {
  searchQuery.value = ''
  isOpen.value = false
  highlightedIndex.value = -1
}

const handleKeydown = (e) => {
  const len = filteredUsers.value.length

  if (e.key === 'Escape') {
    e.preventDefault()
    clearSearch()
    return
  }

  if (!isOpen.value) return
console.log('key pushed')
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    console.log('down')
    // Циклическая навигация вниз
    highlightedIndex.value = highlightedIndex.value < len - 1 ? highlightedIndex.value + 1 : 0
  } 
  else if (e.key === 'ArrowUp') {
    e.preventDefault()
    console.log('up')
    // Циклическая навигация вверх
    highlightedIndex.value = highlightedIndex.value > 0 ? highlightedIndex.value - 1 : len - 1
  } 
  else if (e.key === 'Enter') {
    e.preventDefault()
    // Выбор пользователя по Enter, если индекс валиден
    if (highlightedIndex.value >= 0 && highlightedIndex.value < len) {
      addAssignee(filteredUsers.value[highlightedIndex.value])
    }
  }

  console.log(highlightedIndex.value)
}

// const filteredUsers = computed(() => {
//   const assignedIds = props.value.map(u => u.id)
//   return props.availableUsers.filter(user => 
//     !assignedIds.includes(user.id) &&
//     user.name.toLowerCase().includes(searchQuery.value.toLowerCase())
//   )
// })

const addAssignee = (user) => {
  emit('change', [...props.value, user])
  searchQuery.value = ''
  isOpen.value = false
}

const removeAssignee = (userId) => {
  emit('change', props.value.filter(u => u.id !== userId))
}

// Закрытие выпадающего списка при клике вне компонента
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    clearSeach()
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
    <!-- Current assignees -->
    <div class="flex flex-wrap gap-2">
      <div 
        v-for="assignee in value"
        :key="assignee.id"
        class="flex items-center gap-1.5 px-2 py-1 bg-gray-100 rounded-full"
      >
        <Avatar :user="assignee" size="xs" />
        <span class="text-xs text-gray-700">{{ assignee.name }}</span>
        <button 
          @click="removeAssignee(assignee.id)"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="w-3 h-3" />
        </button>
      </div>
    </div>
    
    <!-- Add assignee -->
    <div class="relative" ref="dropdownRef">
      <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
      <input
        v-model="searchQuery"
        @focus="isOpen = true"
        @keydown="handleKeydown"
        type="text"
        placeholder="Найти исполнителя..."
        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-transparent transition-all"
      />

      <button
        v-if="searchQuery"
        @click="clearSeach"
        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors"

      >
        <XMarkIcon class="w-4 h-4" />
      </button>
      
      <!-- Выпадающий список -->
      <div 
        v-if="isOpen"
        class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-gray-200 rounded-lg shadow-xl z-50 max-h-52 overflow-y-auto"
      >
        <!-- Вариант 1: Есть подходящие пользователи -->
        <template v-if="filteredUsers.length > 0">
          <button
            v-for="(user, index) in filteredUsers"
            :key="user.id"
            @click="addAssignee(user)"
            class="w-full flex items-center gap-3 px-3 py-2.5 text-sm transition-colors text-left border-b border-gray-50 last:border-0"
            :class="{ 'bg-blue-50': index === highlightedIndex }"
          >
            <Avatar :user="user" size="sm" />
            <div class="flex-1 min-w-0">
              <div class="font-medium text-gray-900 truncate">{{ user.name }}</div>
              <div class="text-xs text-gray-500 truncate">{{ user.email }}</div>
            </div>
            <!-- Подсказка появляется только при наведении или клавиатурной навигации -->
            <span 
              class="text-xs text-blue-600 font-medium opacity-0 transition-opacity"
              :class="{ 'opacity-100': index === highlightedIndex }"
            >
              Выбрать
            </span>
          </button>
        </template>

        <!-- Вариант 2: Пользователь что-то ввел, но ничего не найдено -->
        <div v-else-if="searchQuery.trim() !== ''" class="px-4 py-3 text-sm text-gray-500 text-center">
          Пользователи не найдены
        </div>

        <!-- Вариант 3: Список пуст (нет доступных пользователей в воркспейсе) -->
        <div v-else class="px-4 py-3 text-sm text-gray-500 text-center">
          Нет доступных участников
        </div>
      </div>
    </div>
  </div>
</template>
