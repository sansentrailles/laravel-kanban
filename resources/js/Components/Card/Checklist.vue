<script setup>
import { TrashIcon } from '@heroicons/vue/24/outline'
import { onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
  checklist: {
    type: Array,
    required: true
  },
})

const newItemContent = ref({})
const isOpen = ref(false)
const itemConfirmDeleteRef = ref(null)

const emit = defineEmits(['delete'])

const deleteChecklist = (id) => {  
  // ваша логика удаления
  isOpen.value = false
  emit('delete', id)
  console.log('id: ', id)
}

const handleClickOutside = (event) => {
  if (itemConfirmDeleteRef.value && !itemConfirmDeleteRef.value.contains(event.target)) {
    isOpen.value = false
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
  <div class="border border-gray-200 rounded-lg p-3">
    <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-medium text-gray-900">{{ props.checklist.title }}</h4>

        <div class="relative" ref="itemConfirmDeleteRef">
          <button @click="isOpen = !isOpen" class="text-gray-400 hover:text-red-600">
            <TrashIcon class="w-4 h-4" />
          </button>
          <div v-if="isOpen"
            class="absolute right-0 top-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-3 space-y-3 min-w-[250px] z-10">
            <div>
              <p>Вы действительно хотите удалить чеклист?</p>
            </div>

            <button @click="deleteChecklist(props.checklist.id)"
              class="w-full px-3 py-1.5 text-sm font-medium text-white bg-red-500 rounded hover:bg-red-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
              Удалить
            </button>
          </div>
        </div>
      </div>

      <div class="space-y-1">
        <div v-for="item in props.checklist.items" :key="item.id" class="flex items-center gap-2">
          <input type="checkbox" :checked="item.completed" @change="toggleItem(props.checklist.id, item.id)"
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
          <span :class="{ 'line-through text-gray-400': item.completed }" class="text-sm text-gray-700 flex-1">
            {{ item.content }}
          </span>
        </div>
      </div>

      <div class="mt-2 flex items-center gap-2">
        <input v-model="newItemContent[props.checklist.id]" @keyup.enter="addItem(props.checklist.id)" type="text"
          placeholder="Добавить пункт..."
          class="flex-1 px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-100" />
        <button @click="addItem(props.checklist.id)"
          class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
          Добавить
        </button>
      </div>
    </div>
</template>
