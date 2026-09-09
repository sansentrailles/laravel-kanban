<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { 
  CheckCircleIcon, 
  ExclamationCircleIcon, 
  InformationCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

const toasts = ref([])
let toastId = 0

const toastClasses = (type) => {
  const classes = {
    success: 'bg-green-50 border border-green-200',
    error: 'bg-red-50 border border-red-200',
    info: 'bg-blue-50 border border-blue-200',
    warning: 'bg-yellow-50 border border-yellow-200'
  }
  return classes[type] || classes.info
}

const toastTextClasses = (type) => {
  const classes = {
    success: 'text-green-800',
    error: 'text-red-800',
    info: 'text-blue-800',
    warning: 'text-yellow-800'
  }
  return classes[type] || classes.info
}

const toastIcon = (type) => {
  const icons = {
    success: CheckCircleIcon,
    error: ExclamationCircleIcon,
    info: InformationCircleIcon,
    warning: ExclamationCircleIcon
  }
  return icons[type] || InformationCircleIcon
}

const addToast = (message, type = 'info', duration = 5000) => {
  const id = ++toastId
  toasts.value.push({ id, message, type })
  
  if (duration > 0) {
    setTimeout(() => {
      removeToast(id)
    }, duration)
  }
  
  return id
}

const removeToast = (id) => {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

// Глобальный доступ к toast через window
onMounted(() => {
  window.toast = {
    success: (msg, duration) => addToast(msg, 'success', duration),
    error: (msg, duration) => addToast(msg, 'error', duration),
    info: (msg, duration) => addToast(msg, 'info', duration),
    warning: (msg, duration) => addToast(msg, 'warning', duration)
  }
})

onUnmounted(() => {
  delete window.toast
})
</script>

<template>
  <div class="fixed bottom-4 right-4 z-50 space-y-2">
    <transition-group name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg min-w-[300px] max-w-md"
        :class="toastClasses(toast.type)"
      >
        <component :is="toastIcon(toast.type)" class="w-5 h-5 flex-shrink-0" />
        <div class="flex-1 text-sm" :class="toastTextClasses(toast.type)">
          {{ toast.message }}
        </div>
        <button 
          @click="removeToast(toast.id)"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="w-4 h-4" />
        </button>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-enter-active {
  transition: all 0.3s ease;
}

.toast-leave-active {
  transition: all 0.2s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>