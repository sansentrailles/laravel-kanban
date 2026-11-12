<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Подтвердите действие',
  },
  message: {
    type: String,
    default: '',
  },
  confirmLabel: {
    type: String,
    default: 'Удалить',
  },
  cancelLabel: {
    type: String,
    default: 'Отмена',
  },
})

const emit = defineEmits(['confirm', 'cancel'])

const cancelButton = ref(null)

// При открытии фокус на «Отмена»: Enter не должен случайно подтвердить удаление.
watch(
  () => props.show,
  (show) => {
    if (show) nextTick(() => cancelButton.value?.focus())
  },
  { immediate: true }
)

const handleKeydown = (event) => {
  if (props.show && event.key === 'Escape') {
    emit('cancel')
  }
}

onMounted(() => document.addEventListener('keydown', handleKeydown))
onUnmounted(() => document.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <Teleport to="body">
    <Transition name="confirm-fade">
      <div
        v-if="show"
        class="fixed inset-0 z-[70] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
      >
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="emit('cancel')" />

        <div class="relative w-full max-w-sm bg-white rounded-lg shadow-2xl p-5 space-y-4">
          <div class="space-y-1.5">
            <h3 class="text-base font-semibold text-gray-900">{{ title }}</h3>
            <p v-if="message" class="text-sm text-gray-600">{{ message }}</p>
          </div>

          <div class="flex justify-end gap-2">
            <button
              ref="cancelButton"
              type="button"
              class="px-3 py-1.5 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              @click="emit('cancel')"
            >
              {{ cancelLabel }}
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 transition-colors"
              @click="emit('confirm')"
            >
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity 0.15s ease;
}

.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}
</style>