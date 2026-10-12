<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import Avatar from '@/Components/UI/Avatar.vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  comment: { type: Object, required: true }
})

const emit = defineEmits(['edit', 'delete'])

const page = usePage()
const user = computed(() => page.props.auth.user)

// Состояние выпадающего меню
const isMenuOpen = ref(false)
// Состояние окна подтверждения удаления
const isDeleteOpen = ref(false)
const isEditing = ref(false)
const content  = ref('')
const commentInput = ref(null)

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Управление меню
const toggleMenu = () => { isMenuOpen.value = !isMenuOpen.value }
const closeMenu = () => { isMenuOpen.value = false }

// Управление удалением
const showDeleteConfirm = () => {
  closeMenu()
  isDeleteOpen.value = true
}

const closeDeleteConfirm = () => {
  isDeleteOpen.value = false
}

const confirmDelete = () => {
  isDeleteOpen.value = false
  emit('delete', props.comment.id)
}

const handleEdit = async () => {
  closeMenu()
  content.value = props.comment.content // Сбрасываем текст на случай, если редактировали до этого
  isEditing.value = true
  await nextTick()
  commentInput.value?.focus() // Ставим курсор в textarea
}

const save = () => {
  if (!content.value.trim()) return // Не сохраняем пустоту
  
  // Отправляем id и новый текст
  emit('edit', { 
    id: props.comment.id, 
    content: content.value 
  })
  isEditing.value = false
}

const cancel = () => {
  content.value = props.comment.content // Возвращаем оригинальный текст
  isEditing.value = false
}

watch(() => props.comment.content, (newValue) => {
  if (!isEditing.value) {
    content.value = newValue
  }
})
</script>

<template>
  <!-- Добавлен класс group, чтобы показывать меню при наведении -->
  <div class="flex gap-3 group">
    <Avatar :user="props.comment.user" size="sm" />
    <div class="flex-1">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium text-gray-900">{{ props.comment.user.name }}</span>
          <span class="text-xs text-gray-500">{{ formatDate(props.comment.created_at) }}</span>
        </div>

        <!-- Кнопка открытия меню -->
        <div class="relative">
          <button
            v-if="user.id === comment.user.id" 
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
                @click="handleEdit" 
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
              >
                Редактировать
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

      <div v-if="!isEditing" class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">
        {{ props.comment.content }}
      </div>

      <div v-if="isEditing" class="">
        <textarea
          v-model="content"
          ref="commentInput"
          @blur="save"
          @keydown.ctrl.enter="save"
          placeholder="Комментарий..."
          class="w-full p-3 text-sm resize-none focus:ring-0 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-100"
          rows="4"
        ></textarea>

        <div class="px-3 py-2 flex justify-end">
          <button 
            @click="cancel"
            class="px-2 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded mr-2"
          >
            Отмена
          </button>
          <button 
            @click="save"
            class="px-2 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700"
          >
            Сохранить
          </button>
        </div>
      </div>

      <!-- Окно подтверждения удаления -->
      <template v-if="isDeleteOpen">
        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
          <p class="text-sm text-gray-700 mb-3">Вы действительно хотите удалить этот комментарий?</p>
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
      </template>
    </div>
  </div>
</template>