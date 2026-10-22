<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import DropdownMenu from '../UI/DropdownMenu.vue'
import DropdownMenuItem from '../UI/DropdownMenuItem.vue'
import WipLimitBar from '../UI/WipLimitBar.vue'
import Card from './Card.vue'
import AddCardForm from './AddCardForm.vue'
import { useDraggable } from 'vue-draggable-plus'
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useBoardStore } from '@/Stores/board'

const props = defineProps({
  column: { type: Object, required: true }
})

const store = useBoardStore()

const emit = defineEmits(['card-click', 'card-drag-end'])
const isDragging = ref(false)
const cardsListRef = ref(null)
const showAddCard = ref(false)

// vue-draggable-plus перетаскивает локальную КОПИЮ списка.
// Истиной всегда является стор: после дропа BoardView вызывает store.applyCardMove,
// стор мутируется один раз, и watcher ниже обновляет локальную копию —
// Vue полностью перестраивает DOM из данных стора (нет дублей карточек).
const localCards = ref([])

watch(
  () => [...props.column.cards],
  (cards) => { localCards.value = [...cards] },
  { immediate: true }
)

const draggable = useDraggable(cardsListRef, localCards, {
  group: 'cards',
  animation: 150,
  forceFallback: true,
  ghostClass: 'ghost-card',       // Класс для плейсхолдера (куда упадет карточка)
  chosenClass: 'chosen-card2',    // Класс для карточки, которую начали тянуть
  dragClass: 'drag-card2',        // Класс для перемещаемой карточки
  fallbackClass: 'fallback-card2',
  onStart: () => {
    // Снимаем порядок карточек ДО того, как изменим стор в applyCardMove,
    // чтобы было чем откатиться, если сервер отклонит запрос
    store.snapshotBoard()
    isDragging.value = true
  },
  onEnd: (event) => {
    setTimeout(() => { isDragging.value = false }, 0)
    emit('card-drag-end', event) // Сообщаем родителю, что драг окончен (для API)
  }
})

// SortableJS перенес DOM-узел карточки сам, Vue об этом не знает — после дропа
// делаем полную перерисовку контейнера из стор-данных (по ключу) и заново
// инициализируем Sortable на новом DOM-узле контейнера.
// ВАЖНО: источник watch — геттер. store.cardsRenderTick разворачивается Pinia
// в число, и watch по нему никогда не сработает.
const cardsListKey = computed(() => `${props.column.id}-cards-${store.cardsRenderTick}`)

watch(
  () => store.cardsRenderTick,
  () => {
    // flush: 'post' — к этому моменту контейнер уже пересобран, cardsListRef указывает
    // на новый DOM-узел: пересоздаем Sortable на нем
    draggable?.start()
  },
  { flush: 'post' }
)

const handleCardClick = (card) => {
  if (isDragging.value) return
  emit('card-click', card)
}

const addCard = (title) => {
  // Вызываем экшен в сторе, передаем ID колонки и название
  store.createCard(props.column.id, title)
  showAddCard.value = false
}

// --- Смена цвета колонки через меню ---
const colorInputRef = ref(null)
const columnMenuRef = ref(null)

const changeColor = () => {
  columnMenuRef.value?.close()
  // Открываем нативный диалог выбора цвета браузера
  colorInputRef.value?.click()
}

const onColorChange = (event) => {
  // Инпут типа color всегда возвращает HEX #RRGGBB — валидация на бэке совпадает
  store.updateColumnField(props.column.id, 'color', event.target.value)
}

const saveWipLimit = () => {
  store.updateColumnField(props.column.id, 'wip_limit', wipLimitDraft.value)
  closeWipPopover()
}

const resetWipLimit = () => {
  store.updateColumnField(props.column.id, 'wip_limit', null)
  closeWipPopover()
}



// --- Переименование колонки (инлайн в шапке) ---
const isRenaming = ref(false)
const titleDraft = ref('')
const renameInputRef = ref(null)
const wipLimit = ref(0);

const startRenaming = () => {
  columnMenuRef.value?.close()
  titleDraft.value = props.column.title
  isRenaming.value = true
  // Фокус и выделение текста после монтирования input
  nextTick(() => {
    renameInputRef.value?.focus()
    renameInputRef.value?.select()
  })
}

const commitRename = () => {
  const newTitle = titleDraft.value.trim()
  isRenaming.value = false
  titleDraft.value = ''

  // Пустое название не отправляем; совпадение со старым — тоже
  // (нет изменений, запрос не нужен)
  if (!newTitle || newTitle === props.column.title) return

  // Оптимистичное обновление в сторе; при ошибке сервер вернет (например, не
  // уникальное название в этой доске -> 422) и значение откатится
  store.updateColumnField(props.column.id, 'title', newTitle)
}

const cancelRenaming = () => {
  isRenaming.value = false
  titleDraft.value = ''
}

// --- WIP-лимит (поповер под шапкой колонки) ---
// Пока только верстка: сохранение лимита добавим следующим шагом
const showWipPopover = ref(false)
const wipLimitDraft = ref('')
const wipInputRef = ref(null)
const wipPopoverRef = ref(null)

const setWipLimit = () => {
  columnMenuRef.value?.close()
  // В инпуте показываем текущий лимит (или пусто, если его нет)
  wipLimitDraft.value = props.column.wip_limit ?? ''
  showWipPopover.value = true
  nextTick(() => {
    wipInputRef.value?.focus()
  })
}

const closeWipPopover = () => {
  showWipPopover.value = false
  wipLimitDraft.value = ''
}

// --- Закрытие поповера по клику вне его ---
// Слушатель вешаем только пока поповер открыт. Важно: attach через nextTick —
// клик по пункту меню «WIP Лимит» ещё пузырится до document в момент открытия,
// и постоянный слушатель закрыл бы поповер тем же самым кликом.
const onDocumentClick = (event) => {
  if (wipPopoverRef.value && !wipPopoverRef.value.contains(event.target)) {
    closeWipPopover()
  }
}

watch(showWipPopover, (isOpen) => {
  if (isOpen) {
    // setTimeout, а не nextTick: nextTick — микротаск и сработает МЕЖДУ
    // слушателями всплывающего клика, то есть тот же клик по пункту меню
    // сам же попадёт в свежеприкреплённый слушатель и мгновенно закроет
    // поповер. Макротаск же исполняется уже после завершения таска всплытия.
    setTimeout(() => document.addEventListener('click', onDocumentClick))
  } else {
    document.removeEventListener('click', onDocumentClick)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick)
})
</script>

<template>
  <div class="column w-80 flex-shrink-0 bg-gray-100 rounded-lg flex flex-col max-h-full overflow-hidden">
    <!-- Полоска цвета колонки (если цвет установлен) -->
    <div
      v-if="column.color"
      class="h-1.5 w-full"
      :style="{ backgroundColor: column.color }"
    />

    <!-- Header -->
    <div class="p-3 relative flex items-center justify-between column-handle">
      <div class="flex items-center gap-2 min-w-0">
        <template v-if="!isRenaming">
          <h3 class="font-semibold text-gray-900 truncate">{{ column.title }} - {{ column.id }}</h3>
          <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">
            {{ localCards.length }}
          </span>
        </template>
        <input
          v-else
          ref="renameInputRef"
          v-model="titleDraft"
          type="text"
          maxlength="255"
          class="font-semibold text-gray-900 border-2 border-blue-500 rounded px-1.5 py-0.5 outline-none w-full"
          @blur="commitRename"
          @keyup.enter="commitRename"
          @keyup.esc="cancelRenaming"
        >
      </div>
      <DropdownMenu ref="columnMenuRef">
        <DropdownMenuItem @click="startRenaming">Переименовать</DropdownMenuItem>
        <DropdownMenuItem @click="changeColor">Сменить цвет</DropdownMenuItem>
        <DropdownMenuItem @click="setWipLimit">WIP Лимит</DropdownMenuItem>
        <DropdownMenuItem @click="archiveColumn" class="text-red-600">
          Архивировать
        </DropdownMenuItem>
      </DropdownMenu>
      <!-- Нативный color-picker: открываем программно из пункта меню,
           диалог выбора цвета не требует дополнительных компонентов -->
      <input
        ref="colorInputRef"
        type="color"
        :value="column.color || '#4b82f6'"
        class="sr-only"
        @change="onColorChange"
      >

      <!-- WIP-лимит: поповер под шапкой колонки. Пока только верстка,
           кнопки сохранения не отправляют запросов -->
      <div
        v-if="showWipPopover"
        ref="wipPopoverRef"
        class="absolute top-full left-3 right-3 mt-1 z-20 bg-white rounded-lg shadow-lg border border-gray-200 p-3"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-semibold text-gray-900">WIP-лимит</span>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 rounded p-0.5"
            title="Закрыть"
            @click="closeWipPopover"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>

        <p class="text-xs text-gray-500 mb-2">
          Максимум карточек в колонке (1–100). Когда лимит достигнут,
          перенести сюда еще одну карточку нельзя
        </p>

        <label class="sr-only" :for="`wip-input-${column.id}`">WIP-лимит</label>
        <input
          :id="`wip-input-${column.id}`"
          ref="wipInputRef"
          v-model="wipLimitDraft"
          type="number"
          min="1"
          max="100"
          class="w-full border-gray-300 rounded-md text-sm px-2 py-1.5 focus:border-blue-500 outline-none"
          @keyup.esc="closeWipPopover"
        >

        <div class="flex items-center gap-2 mt-3">
          <button
            @click="saveWipLimit()"
            type="button"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md px-2 py-1.5"
          >
            Сохранить
          </button>
          <button
            type="button"
            @click="resetWipLimit()"
            class="text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md px-2 py-1.5"
            :disabled="!column.wip_limit"
            :class="{ 'opacity-40 cursor-not-allowed': !column.wip_limit }"
          >
            Снять лимит
          </button>
        </div>
      </div>
    </div>

    <!-- WIP Limit Bar (если установлен) -->
    <WipLimitBar 
      class="cursor-pointer"
      v-if="column.wip_limit"
      :current="localCards.length"
      :limit="column.wip_limit"
      :color="column.color"
      @open="setWipLimit"
    />

    <!-- Список карточек -->
    <div
      class="flex-1 overflow-y-auto p-2 space-y-2"
      ref="cardsListRef"
      :key="cardsListKey"
      :data-column-id="column.id"
    >
      <Card
        v-for="card in localCards"
        :key="card.id"
        :card="card"
        :data-card-id="card.id"
        @click="handleCardClick(card)"
      />
    </div>
    
    <div class="p-2">
      <button 
        @click="showAddCard = !showAddCard"
        class="w-full text-left text-gray-600 hover:bg-gray-200 rounded px-2 py-1.5 text-sm flex items-center gap-2"
      >
        <PlusIcon class="w-4 h-4" />
        Добавить карточку
      </button>
      
      <AddCardForm 
        v-if="showAddCard"
        :column-id="column.id"
        @submit="addCard"
        @cancel="showAddCard = false"
      />
    </div>
  </div>
</template>

<style scoped>
/* Стили для перетаскиваемых карточек (используем :deep) */
/* Плесхолдер - место, куда встанет карточка */
:deep(.ghost-card) {
  background: rgba(168, 85, 247, 0.1); /* Полупрозрачный фиолетовый фон */
  border: 2px dashed #a855f7;          /* Пунктирная граница */
  border-radius: 8px;
  opacity: 0.2;
  /* Можно задать фиксированную высоту, чтобы плейсхолдер не "прыгал" */
  /* height: 60px; */
}

/* Элемент в момент клика до начала движения */
:deep(.chosen-card2) {
  cursor: grabbing;
  opacity: 0.5 !important;
  /* transform: rotate(1deg); */
}

/* "Призрак" карточки, который висит под курсором мыши */
:deep(.drag-card2),
:deep(.fallback-card2) 
{
  background: #ffffff !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
  transform: rotate(2deg); /* Легкий наклон, как у Trello */
  opacity: 0.85 !important;
  cursor: grabbing !important;
  border-radius: 8px;
}
</style>