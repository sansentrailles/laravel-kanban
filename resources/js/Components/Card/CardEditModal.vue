<script setup>
import { useBoardStore } from '@/Stores/board';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

// UI Components
import FormField from '../UI/FormField.vue';
import DescriptionEditor from './DescriptionEditor.vue';
import ActivityLog from './ActivityLog.vue';
import AssigneePicker from './AssigneePicker.vue';
import LabelSelector from './LabelSelector.vue';
import DateRangePicker from './DateRangePicker.vue';
import AttachmentsList from './AttachmentsList.vue';
import ChecklistSection from './ChecklistSection.vue';
import CommentsSection from './CommentsSection.vue'; // Был не импортирован, но использовался в template

const store = useBoardStore()
const page = usePage()

const card = computed(() => store.selectedCard)
const originalDescription = ref('')

// Получаем метки воркспейса и его ID
const workspaceLabels = computed(() => page.props.workspaceLabels?.data || [])
const cardChecklists = computed(() => card.value?.checklists || [])
const workspaceId = computed(() => page.props.currentWorkspace?.id)
const availableUsers = computed(() => page.props.currentWorkspace?.members?.data || [])

// ✅ Сохраняем исходное значение при открытии модалки
watch(card, (newCard) => {
  if (newCard) {
    originalDescription.value = newCard.description || ''
  }
}, { immediate: true })

const close = () => {
  store.selectCard(null)
}

// ─────────────────────────────────────────────
//  Optimistic UI Helpers
// ─────────────────────────────────────────────

const updateField = async (fieldName, displayValue, payloadKey = null, payloadValue = null) => {
  const oldValue = card.value[fieldName]
  
  // Мгновенное обновление UI
  card.value[fieldName] = displayValue

  try {
    const data = { [payloadKey || fieldName]: payloadValue !== null ? payloadValue : displayValue }
    await axios.patch(route('boards.cards.update', card.value.id), data)
  } catch (error) {
    // Откат при ошибке
    card.value[fieldName] = oldValue
    console.error('Update error:', error)
  }
}

const updateLabels = (labels) => {
  const labelIds = labels.map(l => l.id)
  updateField('labels', labels, 'label_ids', labelIds)
}

const updateAssignees = (users) => {
  const assigneeIds = users.map(u => u.id)
  updateField('assignees', users, 'assignee_ids', assigneeIds)
}

const updateDescription = (description) => {
  updateField('description', description)
}

const updateDates = async (dates) => {
  const oldStartDate = card.value.start_date
  const oldDueDate = card.value.due_date
  
  card.value.start_date = dates.start_date || null
  card.value.due_date = dates.due_date || null

  try {
    await axios.patch(route('boards.cards.update', card.value.id), {
      start_date: card.value.start_date,
      due_date: card.value.due_date
    })
  } catch (error) {
    card.value.start_date = oldStartDate
    card.value.due_date = oldDueDate
    console.error('Ошибка обновления дат:', error)
  }
}

const updateChecklists = (newChecklists) => {
  card.value.checklists = newChecklists
}

const convertItemToCard = (checklistId, itemId) => {
  console.log('Конвертация в карточку:', checklistId, itemId)
  // Реализуй логику, если нужно
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
                <DescriptionEditor
                  :value="card.description"
                  @update="updateDescription"
                />

                <FormField label="Исполнители">
                  <AssigneePicker
                    :value="card.assignees"
                    :available-users="availableUsers" 
                    @change="updateAssignees"
                  />
                </FormField>

                <FormField label="Метки">
                  <LabelSelector
                    :value="card.labels"
                    :available-labels="workspaceLabels"
                    :workspace-id="workspaceId"
                    @change="updateLabels"
                  />
                </FormField>

                <ChecklistSection
                  :card-id="card.id"
                  :checklists="cardChecklists"
                  @change="updateChecklists"
                  @to-card="convertItemToCard"
                />

                <FormField label="Сроки">
                  <DateRangePicker
                    :start="card.start_date"
                    :end="card.due_date"
                    @change="updateDates"
                  />
                </FormField>

                <FormField label="Вложения">
                  <AttachmentsList :attachments="card.attachments" />
                </FormField>

                <!-- Если CommentsSection не используется, удали -->
                <CommentsSection :comments="card.comments" />
              </div>

              <!-- Сайдбар (1/3) -->
              <div class="space-y-4">
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