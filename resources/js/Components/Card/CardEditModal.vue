<script setup>
import { useBoardStore } from '@/Stores/board';
import { useDebounceFn } from '@vueuse/core'
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';
import DescriptionEditor from './DescriptionEditor.vue';
import ActivityLog from './ActivityLog.vue';
import FormField from '../UI/FormField.vue';
import StatusDropdown from './StatusDropdown.vue';
import PrioritySelector from './PrioritySelector.vue';
import AssigneePicker from './AssigneePicker.vue';
import LabelSelector from './LabelSelector.vue';
import DateRangePicker from './DateRangePicker.vue';
import AttachmentsList from './AttachmentsList.vue';
import ChecklistSection from './ChecklistSection.vue';
import { usePage } from '@inertiajs/vue3';

const store = useBoardStore()
const page = usePage()

const card = computed(() => store.selectedCard)
const originalDescription = ref('')

// Получаем метки воркспейса и его ID для корректной работы LabelSelector
const workspaceLabels = computed(() => page.props.workspaceLabels?.data || [])
const workspaceId = computed(() => page.props.currentWorkspace?.id)
const availableUsers = computed(() => {
  return page.props.currentWorkspace?.members?.data || []
})

defineEmits(['update'])

// ✅ Сохраняем исходное значение при открытии модалки
watch(card, (newCard) => {
  if (newCard) {
    originalDescription.value = newCard.description || ''
  }
}, { immediate: true })

const close = () => {
  store.selectCard(null)
}

// ✅ Функция для немедленного обновления UI
const updateDescription = (value) => {
  // Обновляем интерфейс сразу
  card.value.description = value

  // Отправляем запрос на сервер с задержкой
  debouncedSave(value)
}

const debouncedSave = useDebounceFn(async (value) => {
  try {
    const response = await axios.patch(`/cards/${card.value.id}`, { description: value })
    
    if (response.status === 200) {
      if (response.data?.data?.description !== value) {
        card.value.description = response.data.data.description
        originalDescription.value = response.data.data.description
      }
    } else {
      returnBackupedDescription()
    }
  } catch (error) {
    returnBackupedDescription()
  }
}, 1000)

const returnBackupedDescription = () => {
    toast.error('Не удалось обновить. Возвращаем значение')
    card.value.description = originalDescription.value
}

// ─────────────────────────────────────────────
//  Optimistic UI Helpers
// ─────────────────────────────────────────────

/**
 * Универсальная функция для обновления полей с откатом при ошибке
 */
const updateField = async (fieldName, displayValue, payloadKey = null, payloadValue = null) => {
  const oldValue = card.value[fieldName]
  
  // Мгновенное обновление UI (Optimistic)
  card.value[fieldName] = displayValue

  try {
    // Отправка на сервер
    // Используем payloadKey, если имя поля на бэкенде отличается (например, label_ids)
     const data = { [payloadKey || fieldName]: payloadValue !== null ? payloadValue : displayValue }
    await axios.patch(route('boards.cards.update', card.value.id), data)
  } catch (error) {
    // Откат при ошибке
    card.value[fieldName] = oldValue
    console.error('Update error:', error)
  }
}

const updateLabels = (labels) => {
  // Бэкенд ожидает массив ID меток
  const labelIds = labels.map(l => l.id)
  
  // Передаем:
  // - fieldName: 'labels'
  // - displayValue: labels (объекты для UI)
  // - payloadKey: 'label_ids'
  // - payloadValue: labelIds (ID для бэкенда)
  updateField('labels', labels, 'label_ids', labelIds)
}

const updateAssignees = (users) => {
  // Бэкенд ожидает массив ID пользователей
  const assigneeIds = users.map(l => l.id)
  
  // Передаем:
  // - fieldName: 'labels'
  // - displayValue: labels (объекты для UI)
  // - payloadKey: 'label_ids'
  // - payloadValue: labelIds (ID для бэкенда)
  updateField('assignees', users, 'assignee_ids', assigneeIds)
}

</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="card" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div 
          class="absolute inset-0 bg-black/50 backdrop-blur-sm"
          @click="close"
        />

        <!-- Modal -->
        <div class="relative bg-white rounded-md shadow-2xl w-full max-w-4xl overflow-hidden h-[80vh] flex flex-col">
          <!-- Header -->
          <div class="flex items-center justify-between px-4 py-2 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
              {{ card.title }}
            </h2>
            <button
              @click="close"
              class="text-gray-400 hover:text-gray-600 transition-colors"
              aria-label="Закрыть"
            >
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-3 gap-6">
              <!-- Основная область (2/3) -->
              <div class="col-span-2 space-y-6 border-r-2 pr-3 border-gray-200">
                <!-- Описание -->
                <DescriptionEditor :value="card.description" @update="updateDescription" />

                <!-- Приоритет -->
                <FormField label="Приоритет">
                  <PrioritySelector :value="card.priority" @change="updatePriority" />
                </FormField>

                <!-- Исполнители -->
                <FormField label="Исполнители">
                  <AssigneePicker
                    :value="card.assignees"
                    :available-users="availableUsers" 
                    @change="updateAssignees"
                  />
                </FormField>

                <!-- Метки -->
                <FormField label="Метки">
                  <LabelSelector
                    :value="card.labels"
                    :available-labels="workspaceLabels"
                    :workspace-id="workspaceId"
                    @change="updateLabels"
                  />
                </FormField>

                <!-- Даты -->
                <FormField label="Сроки">
                  <DateRangePicker :start="card.start_date" :end="card.due_date" @change="updateDates" />
                </FormField>

                <!-- Вложения -->
                <FormField label="Вложения">
                  <AttachmentsList :attachments="card.attachments" />
                </FormField>

                <!-- Чек-листы -->
                <ChecklistSection :checklists="card.checklists" />

                <!-- Комментарии -->
                <CommentsSection :comments="card.comments" />

                <!-- Статус -->
                <FormField label="Статус">
                  <StatusDropdown :value="card.status" @change="updateStatus" />
                </FormField>
              </div>

              <!-- Сайдбар (1/3) -->
              <div class="space-y-4">
                <!-- История -->
                <FormField label="История">
                  <ActivityLog :activities="card.activities" />
                </FormField>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.2s ease-out;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.95) translateY(10px);
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: all 0.2s ease-out;
}
</style>