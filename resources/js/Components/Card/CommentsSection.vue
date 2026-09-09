<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Avatar from '@/Components/UI/Avatar.vue'

const props = defineProps({
  comments: {
    type: Array,
    default: () => []
  }
})

const page = usePage()
const currentUser = computed(() => page.props.auth.user)
const newComment = ref('')

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const addComment = () => {
  if (!newComment.value.trim()) return
  
  // Add comment logic
  newComment.value = ''
}
</script>

<template>
  <div class="space-y-4">
    <h3 class="text-sm font-semibold text-gray-900">Комментарии</h3>
    
    <!-- Comments list -->
    <div class="space-y-3">
      <div 
        v-for="comment in comments"
        :key="comment.id"
        class="flex gap-3"
      >
        <Avatar :user="comment.user" size="sm" />
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-900">{{ comment.user.name }}</span>
            <span class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</span>
          </div>
          <div class="mt-1 text-sm text-gray-700 whitespace-pre-wrap">
            {{ comment.content }}
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add comment -->
    <div class="flex gap-3 pt-4 border-t border-gray-200">
      <Avatar :user="currentUser" size="sm" />
      <div class="flex-1">
        <textarea
          v-model="newComment"
          @keydown.ctrl.enter="addComment"
          placeholder="Написать комментарий..."
          class="w-full p-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
          rows="3"
        ></textarea>
        <div class="mt-2 flex justify-end">
          <button 
            @click="addComment"
            :disabled="!newComment.trim()"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50"
          >
            Отправить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>