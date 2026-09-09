<script setup>
import { computed } from 'vue'
import { 
  CheckCircleIcon, 
  ChatBubbleIcon, 
  PaperClipIcon, 
  CalendarIcon 
} from '@heroicons/vue/24/outline'
import Avatar from '@/Components/UI/Avatar.vue'
import LabelBadge from '@/Components/UI/LabelBadge.vue'

const props = defineProps({
  card: {
    type: Object,
    required: true
  }
})

const isOverdue = computed(() => {
  if (!props.card.due_date) return false
  return new Date(props.card.due_date) < new Date()
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short'
  })
}
</script>

<template>
  <div 
    class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 cursor-pointer hover:shadow-md transition-shadow"
    :class="{ 'border-l-4 border-l-red-500': isOverdue }"
  >
    <!-- Метки -->
    <div v-if="card.labels.length" class="flex flex-wrap gap-1 mb-2">
      <LabelBadge 
        v-for="label in card.labels"
        :key="label.id"
        :label="label"
      />
    </div>
    
    <!-- Заголовок -->
    <h4 class="text-sm font-medium text-gray-900 mb-2 line-clamp-2">
      {{ card.title }}
    </h4>
    
    <!-- Превью описания -->
    <p v-if="card.description" class="text-xs text-gray-500 mb-2 line-clamp-2">
      {{ card.description }}
    </p>
    
    <!-- Чек-лист прогресс -->
    <div v-if="card.checklist" class="mb-2">
      <div class="flex items-center gap-2 text-xs text-gray-600">
        <CheckCircleIcon class="w-3 h-3" />
        <span>{{ card.checklist.completed }}/{{ card.checklist.total }}</span>
        <div class="flex-1 h-1 bg-gray-200 rounded-full overflow-hidden">
          <div 
            class="h-full bg-green-500"
            :style="{ width: `${(card.checklist.completed / card.checklist.total) * 100}%` }"
          />
        </div>
      </div>
    </div>
    
    <!-- Футер -->
    <div class="flex items-center justify-between text-xs text-gray-500">
      <div class="flex items-center gap-2">
        <!-- Исполнители -->
        <div v-if="card.assignees.length" class="flex -space-x-1">
          <Avatar 
            v-for="assignee in card.assignees.slice(0, 3)"
            :key="assignee.id"
            :user="assignee"
            size="xs"
          />
        </div>
        
        <!-- Комментарии -->
        <div v-if="card.comments_count" class="flex items-center gap-1">
          <ChatBubbleIcon class="w-3 h-3" />
          <span>{{ card.comments_count }}</span>
        </div>
        
        <!-- Вложения -->
        <div v-if="card.attachments_count" class="flex items-center gap-1">
          <PaperClipIcon class="w-3 h-3" />
          <span>{{ card.attachments_count }}</span>
        </div>
      </div>
      
      <!-- Дедлайн -->
      <div 
        v-if="card.due_date"
        class="flex items-center gap-1"
        :class="{ 'text-red-600 font-medium': isOverdue }"
      >
        <CalendarIcon class="w-3 h-3" />
        <span>{{ formatDate(card.due_date) }}</span>
      </div>
    </div>
  </div>
</template>