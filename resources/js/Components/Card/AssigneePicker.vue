<script setup>
import { ref, computed } from 'vue'
import { XIcon } from '@heroicons/vue/24/outline'
import Avatar from '@/Components/UI/Avatar.vue'

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

const filteredUsers = computed(() => {
  const assignedIds = props.value.map(u => u.id)
  return props.availableUsers.filter(user => 
    !assignedIds.includes(user.id) &&
    user.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const addAssignee = (user) => {
  emit('change', [...props.value, user])
  searchQuery.value = ''
  isOpen.value = false
}

const removeAssignee = (userId) => {
  emit('change', props.value.filter(u => u.id !== userId))
}
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
          <XIcon class="w-3 h-3" />
        </button>
      </div>
    </div>
    
    <!-- Add assignee -->
    <div class="relative">
      <input
        v-model="searchQuery"
        @focus="isOpen = true"
        type="text"
        placeholder="Добавить исполнителя..."
        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      
      <div 
        v-if="isOpen && filteredUsers.length"
        class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-h-48 overflow-y-auto"
      >
        <button
          v-for="user in filteredUsers"
          :key="user.id"
          @click="addAssignee(user)"
          class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 transition-colors text-left"
        >
          <Avatar :user="user" size="xs" />
          <div class="flex-1 min-w-0">
            <div class="font-medium text-gray-900 truncate">{{ user.name }}</div>
            <div class="text-xs text-gray-500 truncate">{{ user.email }}</div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
