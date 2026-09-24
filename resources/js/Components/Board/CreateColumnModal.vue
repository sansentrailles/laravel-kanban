<script setup>
import { ref, watch, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { XMarkIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  boardId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'created'])

const form = useForm({
  title: '',
  color: '',
  wip_limit: null,
})

const titleInput = ref(null)

// Автофокус на поле title при открытии
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    nextTick(() => {
      titleInput.value?.focus()
    })
  }
})

const submit = () => {
  form.post(route('boards.columns.store', props.boardId), {
    preserveScroll: true,
    onSuccess: () => {
      resetForm()
      emit('created')
      close()
    },
    onError: () => {
      // Ошибки уже будут в form.errors
    },
  })
}

const close = () => {
  resetForm()
  emit('update:modelValue', false)
}

const resetForm = () => {
  form.reset()
  form.clearErrors()
}

// Закрытие по Escape
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    const handleEscape = (e) => {
      if (e.key === 'Escape') close()
    }
    document.addEventListener('keydown', handleEscape)
    return () => document.removeEventListener('keydown', handleEscape)
  }
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.2s ease-out;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.95) translateY(10px);
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: all 0.2s ease-out;
}
</style>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div 
          class="absolute inset-0 bg-black/50 backdrop-blur-sm"
          @click="close"
        />

        <!-- Modal -->
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
              Новая колонка
            </h2>
            <button
              @click="close"
              class="text-gray-400 hover:text-gray-600 transition-colors"
              aria-label="Закрыть"
            >
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="submit" class="p-6 space-y-4">
            <!-- Название -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Название <span class="text-red-500">*</span>
              </label>
              <input
                ref="titleInput"
                v-model="form.title"
                type="text"
                placeholder="Например, To Do"
                maxlength="255"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                :class="{ 'border-red-500 ring-1 ring-red-500': form.errors.title }"
                @keydown.escape="close"
              />
              <p v-if="form.errors.title" class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                <ExclamationCircleIcon class="w-4 h-4" />
                {{ form.errors.title }}
              </p>
            </div>

            <!-- Цвет -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Цвет (опционально)
              </label>
              <div class="flex items-center gap-3">
                <input
                  v-model="form.color"
                  type="color"
                  class="h-10 w-20 rounded-lg border border-gray-300 cursor-pointer"
                />
                <input
                  v-model="form.color"
                  type="text"
                  placeholder="#3b82f6"
                  maxlength="7"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                />
              </div>
              <p v-if="form.errors.color" class="mt-1.5 text-sm text-red-600">
                {{ form.errors.color }}
              </p>
            </div>

            <!-- WIP Лимит -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                WIP Лимит <span class="text-gray-400 font-normal">(опционально)</span>
              </label>
              <input
                v-model.number="form.wip_limit"
                type="number"
                min="1"
                max="100"
                placeholder="Без ограничений"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                :class="{ 'border-red-500 ring-1 ring-red-500': form.errors.wip_limit }"
              />
              <p class="mt-1.5 text-xs text-gray-500">
                Максимальное количество карточек в колонке
              </p>
              <p v-if="form.errors.wip_limit" class="mt-1 text-sm text-red-600">
                {{ form.errors.wip_limit }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
              <button
                type="button"
                @click="close"
                class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Отмена
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
              >
                <svg v-if="form.processing" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ form.processing ? 'Создание...' : 'Создать' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>