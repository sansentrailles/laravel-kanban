<script setup>
import { ref } from 'vue'
import { 
  DocumentIcon, 
  PhotoIcon, 
  ArrowDownTrayIcon, 
  TrashIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  attachments: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['upload', 'delete'])

const isDragging = ref(false)
const fileInput = ref(null)

const fileIcon = (type) => {
  if (type?.startsWith('image/')) return PhotoIcon
  return DocumentIcon
}

const formatSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  emit('upload', files)
  event.target.value = ''
}

const handleDrop = (event) => {
  isDragging.value = false
  const files = Array.from(event.dataTransfer.files)
  emit('upload', files)
}

const deleteAttachment = (id) => {
  emit('delete', id)
}
</script>

<template>
  <div class="space-y-2">
    <div v-if="attachments.length" class="space-y-1.5">
      <div 
        v-for="attachment in attachments"
        :key="attachment.id"
        class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group"
      >
        <component :is="fileIcon(attachment.type)" class="w-5 h-5 text-gray-400 flex-shrink-0" />
        <div class="flex-1 min-w-0">
          <div class="text-sm font-medium text-gray-900 truncate">
            {{ attachment.name }}
          </div>
          <div class="text-xs text-gray-500">
            {{ formatSize(attachment.size) }}
          </div>
        </div>
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <a 
            :href="attachment.url"
            download
            class="p-1 text-gray-400 hover:text-blue-600"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
          </a>
          <button 
            @click="deleteAttachment(attachment.id)"
            class="p-1 text-gray-400 hover:text-red-600"
          >
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
    
    <!-- Upload area -->
    <div 
      @dragover.prevent="isDragging = true"
      @dragleave="isDragging = false"
      @drop="handleDrop"
      class="border-2 border-dashed rounded-lg p-4 text-center transition-colors"
      :class="isDragging ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
    >
      <input
        ref="fileInput"
        @change="handleFileSelect"
        type="file"
        multiple
        class="hidden"
      />
      <button 
        @click="$refs.fileInput.click()"
        class="text-sm text-blue-600 hover:text-blue-700 font-medium"
      >
        Загрузить файл
      </button>
      <p class="text-xs text-gray-500 mt-1">или перетащите сюда</p>
    </div>
  </div>
</template>