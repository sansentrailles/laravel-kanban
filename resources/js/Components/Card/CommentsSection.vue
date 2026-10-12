<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Avatar from '@/Components/UI/Avatar.vue'
import Comment from './Comment.vue'

const props = defineProps({
  comments: {
    type: Array,
    default: () => []
  }
})

const page = usePage()
const currentUser = computed(() => page.props.auth.user)
const newComment = ref('')
const emit = defineEmits(['change', 'comment-edit', 'delete-comment', 'edit-comment'])

const addComment = () => {
  if (!newComment.value.trim()) return

  emit('change',newComment.value)
  
  newComment.value = ''
}
</script>

<template>
  <div class="space-y-4">
    <h3 class="text-sm font-semibold text-gray-900">Комментарии</h3>
    
    <!-- Comments list -->
    <div class="space-y-3">
      <Comment
        v-for="comment in comments"
        :comment="comment"
        :key="comment.id"
        @delete="(id) => emit('delete-comment', id)"
        @edit="(params) => emit('edit-comment', params)"
      />
    </div>
    
    <!-- Add comment -->
    <div class="flex gap-3 pt-4 border-t border-gray-200">
      <Avatar :user="currentUser" size="sm" />
      <div class="flex-1">
        <textarea
          v-model="newComment"
          @keydown.ctrl.enter="addComment"
          placeholder="Написать комментарий..."
          class="w-full p-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-100 resize-none"
          rows="3"
        ></textarea>
        <div class="mt-2 flex justify-end">
          <button 
            @click="addComment"
            :disabled="!newComment.trim()"
            class="px-2 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50"
          >
            Отправить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>