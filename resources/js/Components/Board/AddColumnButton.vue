<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline'

defineEmits(['click'])
</script>

<template>
  <div class="w-80 flex-shrink-0">
    <button 
      @click="$emit('click')"
      class="w-full p-3 text-left text-gray-600 hover:bg-gray-200 rounded-lg flex items-center gap-2 transition-colors"
    >
      <PlusIcon class="w-5 h-5" />
      <span class="font-medium">Добавить колонку</span>
    </button>
  </div>
</template>