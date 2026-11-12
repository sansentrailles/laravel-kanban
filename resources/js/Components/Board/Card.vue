<script setup>
import { computed, ref } from 'vue'
import {
  CheckCircleIcon,
  PaperClipIcon,
  CalendarIcon,
  ChatBubbleBottomCenterIcon,
  ArchiveBoxIcon
} from '@heroicons/vue/24/outline'
import { SquareCheckBig } from '@lucide/vue';

import Avatar from '@/Components/UI/Avatar.vue'
import LabelBadge from '@/Components/UI/LabelBadge.vue'
import ConfirmDialog from '../UI/ConfirmDialog.vue';

const props = defineProps({
  card: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['archive'])
const isArchiveOpen = ref(false)

const confirmArchive = () => {
  isArchiveOpen.value = false
  emit('archive', props.card.id)
}

const isCompleted = computed(() => Boolean(props.card.completed_at))

const isOverdue = computed(() => {
  if (isCompleted.value) return false
  if (!props.card.due_date) return false
  return new Date(props.card.due_date) < new Date()
})

// Значения и подписи совпадают с App\Enums\Kanban\CardPriority.
const priorities = {
  low: { label: 'Низкий', class: 'bg-green-50 text-green-700 border-green-200' },
  medium: { label: 'Средний', class: 'bg-blue-50 text-blue-700 border-blue-200' },
  high: { label: 'Высокий', class: 'bg-orange-50 text-orange-700 border-orange-200' },
  urgent: { label: 'Срочный', class: 'bg-red-50 text-red-700 border-red-200' }
}

const priority = computed(() => priorities[props.card.priority] ?? null)

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
  <div v-bind="$attrs"
    class="relative group bg-white rounded-lg shadow-sm border border-gray-200 p-3 cursor-pointer hover:shadow-md transition-shadow"
    :class="{
      'border-l-4 border-l-red-500': isOverdue,
      'border-l-4 border-l-green-500': isCompleted,
    }"> <!-- Приоритет и метки -->
    <button class="absolute top-1.5 right-1.5 z-10 p-1 rounded-md bg-white/90 text-gray-400 shadow-sm
         opacity-0 group-hover:opacity-100 hover:text-amber-600 hover:bg-white transition-opacity"
      title="Архивировать карточку" aria-label="Архивировать карточку" @mousedown.stop @touchstart.stop
      @click.stop="isArchiveOpen = true">
      <ArchiveBoxIcon class="w-3.5 h-3.5" />
    </button>
    <div v-if="priority || card.labels.length" class="flex flex-wrap items-center gap-1 mb-2">
      <span v-if="priority" class="inline-flex items-center px-1.5 py-0.5 rounded border text-[11px] font-medium"
        :class="priority.class" title="Приоритет">
        {{ priority.label }}
      </span>

      <LabelBadge v-for="label in card.labels" :key="label.id" :label="label" />
    </div>

    <!-- Заголовок -->
    <div class="flex items-start gap-1.5 mb-2">
      <CheckCircleIcon v-if="isCompleted" class="w-4 h-4 flex-shrink-0 text-green-500 mt-0.5" />
      <h4 class="text-sm font-medium line-clamp-2"
        :class="isCompleted ? 'line-through text-gray-400' : 'text-gray-900'">
        {{ card.title }}
      </h4>
    </div>

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
          <div class="h-full bg-green-500"
            :style="{ width: `${(card.checklist.completed / card.checklist.total) * 100}%` }" />
        </div>
      </div>
    </div>

    <!-- Футер -->
    <div class="flex items-center justify-between text-xs text-gray-500">
      <div class="flex items-center gap-2">
        <!-- Исполнители -->
        <div v-if="card.assignees?.length" class="flex -space-x-1">
          <div v-for="assignee in card.assignees.slice(0, 3)" :key="assignee.id" class="relative cursor-pointer"
            @mouseenter="showTooltip(assignee, $event)" @mouseleave="hideTooltip">
            <Avatar :user="assignee" size="xs" />
          </div>

          <!-- Индикатор, если исполнителей больше 3 -->
          <div v-if="card.assignees.length > 3"
            class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-medium text-gray-600 border border-white"
            title="И еще {{ card.assignees.length - 3 }}">
            +{{ card.assignees.length - 3 }}
          </div>
        </div>

        <!-- Комментарии -->
        <div v-if="card.comments_count" class="flex items-center gap-1" title="Комментарии">
          <ChatBubbleBottomCenterIcon class="w-3 h-3" />
          <span>{{ card.comments_count }}</span>
        </div>

        <!-- Чеклисты -->
        <div v-if="card.checklists_count" class="flex items-center gap-1" title="Чеклисты">
          <SquareCheckBig size="12" />
          <span>{{ card.checklists_count }}</span>
        </div>

        <!-- Вложения -->
        <div v-if="card.attachments_count" class="flex items-center gap-1">
          <PaperClipIcon class="w-3 h-3" />
          <span>{{ card.attachments_count }}</span>
        </div>
      </div>

      <!-- Дедлайн -->
      <div v-if="card.due_date" class="flex items-center gap-1" :class="{ 'text-red-600 font-medium': isOverdue }"
        title="Дедлайн">
        <CalendarIcon class="w-3 h-3" />
        <span>{{ formatDate(card.due_date) }}</span>
      </div>
    </div>

    <ConfirmDialog :show="isArchiveOpen" title="Архивировать карточку?" confirm-label="Архивировать"
      :message="`Карточка «${card.title}» переместится в архив. Её можно будет вернуть.`" @confirm="confirmArchive"
      @cancel="isArchiveOpen = false" />
  </div>

  <!-- TELEPORT: Тултип вынесен в <body> -->
  <Teleport to="body">
    <div v-if="isTooltipVisible && tooltipUser"
      class="z-[100] bg-gray-900 text-white text-xs rounded-lg shadow-2xl p-2.5 whitespace-nowrap pointer-events-none"
      :style="tooltipStyle">
      <div class="flex items-center gap-2.5">
        <Avatar :user="tooltipUser" size="sm" />
        <div class="flex flex-col">
          <span class="font-semibold text-sm">{{ tooltipUser.name }}</span>
          <span class="text-gray-300 text-xs">{{ tooltipUser.email }}</span>
        </div>
      </div>
      <!-- Стрелочка вниз (указывает на аватар) -->
      <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900">
      </div>
    </div>
  </Teleport>
</template>