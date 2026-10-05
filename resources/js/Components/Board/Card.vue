<script setup>
import { computed, ref } from 'vue'
import { 
  CheckCircleIcon, 
  PaperClipIcon, 
  CalendarIcon, 
  ChatBubbleBottomCenterIcon
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

// --- Логика для тултипа ---
const isTooltipVisible = ref(false)
const tooltipStyle = ref({})
const tooltipUser = ref(null)

const showTooltip = (user, event) => {
  tooltipUser.value = user
  const rect = event.currentTarget.getBoundingClientRect()
  
  tooltipStyle.value = {
    // Верхний край тултипа на 8px выше аватара
    top: `${rect.top - 8}px`,
    // По центру аватара по горизонтали
    left: `${rect.left + (rect.width / 2)}px`,
    // Сдвигаем ВЛЕВО на 50% ширины и ВВЕРХ на 100% высоты тултипа
    transform: 'translate(-50%, -100%)',
    position: 'fixed'
  }
  isTooltipVisible.value = true
}

const hideTooltip = () => {
  isTooltipVisible.value = false
  tooltipUser.value = null
}
</script>

<template>
  <div 
    v-bind="$attrs"
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
      {{ card.title }} {{ card.id }}
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
        <div v-if="card.assignees?.length" class="flex -space-x-1">
          <div 
            v-for="assignee in card.assignees.slice(0, 3)"
            :key="assignee.id"
            class="relative cursor-pointer"
            @mouseenter="showTooltip(assignee, $event)"
            @mouseleave="hideTooltip"
          >
            <Avatar :user="assignee" size="xs" />
          </div>

          <!-- Индикатор, если исполнителей больше 3 -->
          <div 
            v-if="card.assignees.length > 3"
            class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-medium text-gray-600 border border-white"
            title="И еще {{ card.assignees.length - 3 }}"
          >
            +{{ card.assignees.length - 3 }}
          </div>
        </div>
        
        <!-- Комментарии -->
        <div v-if="card.comments_count" class="flex items-center gap-1">
          <ChatBubbleBottomCenterIcon class="w-3 h-3" />
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

  <!-- TELEPORT: Тултип вынесен в <body> -->
  <Teleport to="body">
    <div 
      v-if="isTooltipVisible && tooltipUser"
      class="z-[100] bg-gray-900 text-white text-xs rounded-lg shadow-2xl p-2.5 whitespace-nowrap pointer-events-none"
      :style="tooltipStyle"
    >
      <div class="flex items-center gap-2.5">
        <Avatar :user="tooltipUser" size="sm" />
        <div class="flex flex-col">
          <span class="font-semibold text-sm">{{ tooltipUser.name }}</span>
          <span class="text-gray-300 text-xs">{{ tooltipUser.email }}</span>
        </div>
      </div>
      <!-- Стрелочка вниз (указывает на аватар) -->
      <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></div>
    </div>
  </Teleport>
</template>