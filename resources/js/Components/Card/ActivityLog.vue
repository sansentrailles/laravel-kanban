<script setup>
import Avatar from '@/Components/UI/Avatar.vue'
import { computed } from 'vue'

const props = defineProps({
  activities: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  hasMore: {
    type: Boolean,
    default: false,
  },
  columns: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['load-more'])

const priorityLabels = {
  low: 'Низкий',
  medium: 'Средний',
  high: 'Высокий',
  urgent: 'Срочный'
}

const fieldLabels = {
  title: 'Название',
  description: 'Описание',
  priority: 'Приоритет',
  start_date: 'Дата начала',
  due_date: 'Дедлайн',
  completed_at: 'Завершение',
}

const isSnapshot = (value) => (
  value !== null
  && typeof value === 'object'
  && 'preview' in value
  && 'length' in value
)

const columnTitle = (id) => {
  if (!id) return '-'

  const column = props.columns.find((c) => c.id === Number(id))

  return column?.title ?? `Колонка #${id}`
}

const formatValue = (field, value) => {
  if (value === null || value === undefined || value === '') return '-'
  if (isSnapshot(value)) return value.preview || '-'
  if (field === 'priority') return priorityLabels[value] ?? value
  if (field === 'completed_at') return new Date(value).toLocaleDateString('ru-RU')

  return value
}

// Для card.updated: [{ field, label, from, to }]
const fieldChanges = (changes) => {
  if (!changes || Array.isArray(changes)) return []

  return Object.entries(changes).map(([field, change]) => ({
    field,
    label: fieldLabels[field] ?? field,
    from: formatValue(field, change?.from),
    to: formatValue(field, change?.to),
  }))
}

const relationChanges = (changes, key) => {
  const list = changes?.[key]

  return Array.isArray(list) ? list : []
}

// Превью текста для comment.* (снимка или from/to-снимки)
const textPreview = (changes) => {
  const content = changes?.content

  if (!content) return null
  if (isSnapshot(content)) return content.preview

  return content?.to?.preview ?? content?.from?.preview ?? null
}

const attachmentSize = (bytes) => {
  if (!bytes) return null

  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`
}

const actionText = (activity) => {
  const context = activity?.context ?? {}

  switch (activity?.type) {
    case 'card.created':
      return 'создал(а) карточку'
    case 'card.updated':
      return 'изменил(а) карточку'
    case 'card.moved': {
      const from = columnTitle(context.from_column_id)
      const to = columnTitle(context.to_column_id)

      return from === to
        ? `изменил(а) порядок в колонке «${to}»`
        : `переместил(а) карточку из «${from}» в «${to}»`
    }
    case 'card.labels_changed':
      return 'изменил(а) метки'
    case 'card.assignees_changed':
      return 'изменил(а) исполнителей'
    case 'card.deleted':
      return 'удалил(а) карточку'
    case 'card.restored':
      return 'восстановил(а) карточку'
    case 'card.completed':
      return 'завершил(а) карточку'
    case 'card.reopened':
      return 'вернул(а) карточку в работу'
    case 'comment.created':
      return 'добавил(а) комментарий'
    case 'comment.updated':
      return 'изменил(а) комментарий'
    case 'comment.deleted':
      return 'удалил(а) комментарий'
    case 'checklist.created':
      return 'создал(а) чек-лист'
    case 'checklist.deleted':
      return 'удалил(а) чек-лист'
    case 'checklist_item.created':
      return 'добавил(а) пункт чек-листа'
    case 'checklist_item.toggled':
      return activity?.changes?.is_completed?.to
        ? 'отметил(а) пункт выполненным'
        : 'снял(а) отметку с пункта'
    case 'checklist_item.deleted':
      return 'удалил(а) пункт чек-листа'
    case 'attachment.uploaded':
      return 'загрузил(а) вложение'
    case 'attachment.deleted':
      return 'удалил(а) вложение'
    default:
      return activity?.type_label ?? activity?.type ?? 'действие'
  }
}

// Обогащаем один раз, чтобы не пересчитывать в шаблоне на каждый v-if
const items = computed(() => props.activities.map((activity) => {
  const changes = activity.changes ?? {}
  const context = activity.context ?? {}

  return {
    ...activity,
    actor: activity.actor ?? { id: 0, name: 'Неизвестный' },
    action: actionText(activity),
    fields: activity.type === 'card.updated' ? fieldChanges(changes) : [],
    added: relationChanges(changes, 'added'),
    removed: relationChanges(changes, 'removed'),
    preview: textPreview(changes),
    size: attachmentSize(context.size)
  }
}))

const formatDate = (date) => {
  const now = new Date()
  const activityDate = new Date(date)
  const diffMs = now - activityDate
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  
  if (diffMins < 1) return 'только что'
  if (diffMins < 60) return `${diffMins} мин назад`
  if (diffHours < 24) return `${diffHours} ч назад`
  if (diffDays < 7) return `${diffDays} дн назад`
  
  return activityDate.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short'
  })
}

const absoluteDate = (date) => new Date(date).toLocaleString('ru-RU')
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="item in items"
      :key="item.id"
      class="flex gap-3"
    >
      <Avatar :user="item.actor" size="xs" />

      <div class="flex-1 min-w-0">
        <div class="text-sm text-gray-700">
          <span class="font-medium text-gray-900">{{ item.actor.name }}</span>
          {{ item.action }}
        </div>

        <!-- Изменения полей карточки -->
        <ul
          v-if="item.fields.length"
          class="mt-1 space-y-0.5"
        >
          <li
            v-for="change in item.fields"
            :key="change.field"
            class="text-xs text-gray-500"
          >
            <span class="text-gray-600">{{ change.label }}:</span>
            <span class="text-gray-400 line-through">{{ change.from }}</span>
            <span class="mx-1 text-gray-400">→</span>
            <span class="text-gray-700">{{ change.to }}</span>
          </li>
        </ul>

        <!-- Метки -->
        <div
          v-if="item.type === 'card.labels_changed'"
          class="mt-1 flex flex-wrap gap-1"
        >
          <span
            v-for="label in item.added"
            :key="`add-${label.id}`"
            class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs"
            :style="{ backgroundColor: `${label.color}20`, color: label.color }"
          >
            <span
              class="w-1.5 h-1.5 rounded-full"
              :style="{ backgroundColor: label.color }"
            />
            {{ label.name }}
          </span>

          <span
            v-for="label in item.removed"
            :key="`remove-${label.id}`"
            class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs text-gray-400 line-through"
          >
            {{ label.name }}
          </span>
        </div>

        <!-- Исполнители -->
        <div
          v-if="item.type === 'card.assignees_changed'"
          class="mt-1 text-xs text-gray-500"
        >
          <div v-if="item.added.length">
            + {{ item.added.map((user) => user.name).join(', ') }}
          </div>
          <div v-if="item.removed.length">
            − {{ item.removed.map((user) => user.name).join(', ') }}
          </div>
        </div>

        <!-- Комментарии -->
        <p
          v-if="item.preview && item.type.startsWith('comment.')"
          class="mt-1 text-xs text-gray-500 italic line-clamp-2"
        >
          «{{ item.preview }}»
        </p>

        <!-- Чек-листы -->
        <p
          v-if="(item.type === 'checklist.created' || item.type === 'checklist.deleted') && item.changes?.title"
          class="mt-1 text-xs text-gray-500"
        >
          «{{ item.changes.title }}»
        </p>

        <p
          v-if="item.type.startsWith('checklist_item.') && item.context?.content"
          class="mt-1 text-xs text-gray-500"
        >
          «{{ item.context.content }}»
          <span
            v-if="item.context?.checklist_title"
            class="text-gray-400"
          >
            · {{ item.context.checklist_title }}
          </span>
        </p>

        <!-- Вложения -->
        <p
          v-if="item.type.startsWith('attachment.') && item.context?.name"
          class="mt-1 text-xs text-gray-500"
        >
          {{ item.context.name }}
          <span v-if="item.size" class="text-gray-400">
            · {{ item.size }}
          </span>
        </p>

        <div
          class="text-xs text-gray-500 mt-0.5"
          :title="absoluteDate(item.created_at)"
        >
          {{ formatDate(item.created_at) }}
        </div>
      </div>
    </div>

    <!-- Скелетон при первой загрузке -->
    <div
      v-if="!items.length && loading"
      class="space-y-2"
    >
      <div
        v-for="i in 3"
        :key="i"
        class="flex gap-3 animate-pulse"
      >
        <div class="w-5 h-5 rounded-full bg-gray-200 flex-shrink-0" />
        <div class="flex-1 space-y-1.5">
          <div class="h-3 bg-gray-200 rounded w-2/3" />
          <div class="h-2.5 bg-gray-100 rounded w-1/3" />
        </div>
      </div>
    </div>

    <div
      v-else-if="!items.length"
      class="text-sm text-gray-400 text-center py-4"
    >
      История пуста
    </div>

    <button
      v-if="hasMore"
      type="button"
      :disabled="loading"
      class="w-full text-xs text-blue-600 hover:text-blue-700 font-medium py-2 disabled:text-gray-400 transition-colors"
      @click="emit('load-more')"
    >
      {{ loading ? 'Загрузка…' : 'Показать ещё' }}
    </button>
  </div>
</template>