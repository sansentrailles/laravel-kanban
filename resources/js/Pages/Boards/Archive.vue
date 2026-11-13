<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArchiveBoxIcon, ArrowLeftIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useBoardStore } from '@/Stores/board'

const props = defineProps({
  board: {
    type: Object,
    required: true
  },
  archivedCards: {
    type: Object,
    required: true
  },
  currentWorkspace: {
    type: Object,
    required: true
  }
})

const store = useBoardStore()

// Сбрасываем карточку, открытую на доске, до первого рендера: AppLayout
// рисует CardEditModal по selectedCard, иначе модалка мигнёт на один кадр.
store.selectCard(null)

// Локальная копия: после восстановления строка исчезает без перезагрузки.
const cards = ref([...props.archivedCards.data])
const restoringId = ref(null)

// Inertia переиспользует компонент при переходе по страницам пагинации.
watch(
  () => props.archivedCards,
  (value) => { cards.value = [...value.data] }
)

const priorities = {
  low: { label: 'Низкий', class: 'bg-green-50 text-green-700 border-green-200' },
  medium: { label: 'Средний', class: 'bg-blue-50 text-blue-700 border-blue-200' },
  high: { label: 'Высокий', class: 'bg-orange-50 text-orange-700 border-orange-200' },
  urgent: { label: 'Срочный', class: 'bg-red-50 text-red-700 border-red-200' }
}

const priorityOf = (value) => priorities[value] ?? null

const boardUrl = computed(() => route('kanban.board', {
  workspace: props.currentWorkspace.slug,
  board: props.board.uuid,
}))

const formatArchivedAt = (value) => value
  ? new Date(value).toLocaleString('ru-RU', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  : ''

const restore = async (card) => {
  if (restoringId.value) return

  restoringId.value = card.id
  try {
    const restored = await store.restoreCard(card.id)
    if (restored) {
      cards.value = cards.value.filter(item => item.id !== card.id)
    }
  } finally {
    restoringId.value = null
  }
}

const goToPage = (url) => {
  if (url) router.get(url)
}
</script>

<template>
  <Head :title="`Архив: ${board.name}`" />

  <AppLayout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <Link :href="boardUrl" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <ArrowLeftIcon class="w-4 h-4" />
        К доске
      </Link>

      <div class="flex items-center gap-3 mt-4 mb-6">
        <ArchiveBoxIcon class="w-7 h-7 text-gray-400" />
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Архив доски «{{ board.name }}»</h1>
          <p class="text-sm text-gray-500">{{ archivedCards.meta.total }} карточек в архиве</p>
        </div>
      </div>

      <div v-if="cards.length" class="space-y-2">
        <div v-for="card in cards" :key="card.id"
          class="bg-white border border-gray-200 rounded-lg px-4 py-3 flex items-center gap-3">
          <div class="flex-1 min-w-0">
            <h2 class="text-sm font-medium text-gray-900 truncate">{{ card.title }}</h2>
            <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-gray-500">
              <span class="px-1.5 py-0.5 bg-gray-100 rounded">
                {{ card.column_title || 'Колонка удалена' }}
              </span>
              <span v-if="priorityOf(card.priority)" class="px-1.5 py-0.5 rounded border"
                :class="priorityOf(card.priority).class">
                {{ priorityOf(card.priority).label }}
              </span>
              <span>{{ formatArchivedAt(card.archived_at) }}</span>
            </div>
          </div>

          <button @click="restore(card)" :disabled="restoringId === card.id"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg border border-gray-200
                   text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
            <ArrowUturnLeftIcon class="w-4 h-4" />
            Вернуть
          </button>
        </div>
      </div>

      <div v-else class="text-center py-16 bg-gray-50 rounded-xl">
        <ArchiveBoxIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
        <h2 class="text-lg font-medium text-gray-900">Архив пуст</h2>
        <p class="text-sm text-gray-500 mt-1">Сюда попадают карточки, которые вы архивировали.</p>
      </div>

      <div v-if="archivedCards.meta.last_page > 1" class="flex justify-between items-center mt-6">
        <button :disabled="!archivedCards.links.prev" @click="goToPage(archivedCards.links.prev)"
          class="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-50">
          Назад
        </button>
        <span class="text-sm text-gray-500">
          {{ archivedCards.meta.current_page }} / {{ archivedCards.meta.last_page }}
        </span>
        <button :disabled="!archivedCards.links.next" @click="goToPage(archivedCards.links.next)"
          class="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-50">
          Вперёд
        </button>
      </div>
    </div>
  </AppLayout>
</template>