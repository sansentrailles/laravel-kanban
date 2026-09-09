<script setup>
import { ref } from 'vue'
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/24/outline'
import Avatar from '@/Components/UI/Avatar.vue'

defineProps({
  user: {
    type: Object,
    required: true
  }
})

const isOpen = ref(false)
</script>

<template>
  <div class="p-4 border-t border-slate-700">
    <div class="relative">
      <button 
        @click="isOpen = !isOpen"
        class="flex items-center gap-3 w-full hover:bg-slate-800 rounded-lg p-2 transition-colors"
      >
        <Avatar :user="user" size="sm" />
        <div class="flex-1 min-w-0 text-left">
          <div class="text-sm font-medium text-white truncate">{{ user.name }}</div>
          <div class="text-xs text-slate-400 truncate">{{ user.email }}</div>
        </div>
        <ChevronUpIcon v-if="isOpen" class="w-4 h-4 text-slate-400" />
        <ChevronDownIcon v-else class="w-4 h-4 text-slate-400" />
      </button>

      <div v-if="isOpen" class="absolute bottom-full left-0 right-0 mb-2 bg-slate-800 rounded-lg shadow-xl border border-slate-700 z-50">
        <div class="p-2 space-y-1">
          <a href="/profile" class="block px-3 py-2 rounded-lg hover:bg-slate-700 text-sm text-slate-300">
            Профиль
          </a>
          <a href="/settings" class="block px-3 py-2 rounded-lg hover:bg-slate-700 text-sm text-slate-300">
            Настройки
          </a>
          <a href="/billing" class="block px-3 py-2 rounded-lg hover:bg-slate-700 text-sm text-slate-300">
            Подписка
          </a>
        </div>
        <div class="border-t border-slate-700 p-2">
          <a 
            href="/logout" 
            method="post" 
            as="button"
            class="block w-full text-left px-3 py-2 rounded-lg hover:bg-slate-700 text-sm text-red-400"
          >
            Выйти
          </a>
        </div>
      </div>
    </div>
  </div>
</template>