<script setup>
import { useBoardStore } from '@/Stores/board';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import FormField from '../UI/FormField.vue';
import DescriptionEditor from './DescriptionEditor.vue';
import ActivityLog from './ActivityLog.vue';
import AssigneePicker from './AssigneePicker.vue';
import LabelSelector from './LabelSelector.vue';
import DateRangePicker from './DateRangePicker.vue';
import AttachmentsList from './AttachmentsList.vue';
import ChecklistSection from './ChecklistSection.vue';
import CommentsSection from './CommentsSection.vue';

const store = useBoardStore()
const page = usePage()

const card = computed(() => store.selectedCard)

// const workspaceLabels = computed(() => page.props.workspaceLabels?.data || [])
const workspaceLabels = computed(() => store.labels || [])
const workspaceId = computed(() => page.props.currentWorkspace?.id)
const availableUsers = computed(() => page.props.currentWorkspace?.members?.data || [])

const close = () => store.selectCard(null)

// --- Делегирование событий в Store ---
const updateDescription = (description) => {
  store.updateCardField(card.value.id, 'description', description)
}

const updateLabels = (labels) => {
  const labelIds = labels.map(l => l.id)
  store.updateCardField(card.value.id, 'labels', labels, 'label_ids', labelIds)
}

const updateAssignees = (users) => {
  const assigneeIds = users.map(u => u.id)
  store.updateCardField(card.value.id, 'assignees', users, 'assignee_ids', assigneeIds)
}

const updateDates = (dates) => {
  store.updateCardDates(card.value.id, dates)
}

// Прокси-методы для чеклистов
const handleCreateChecklist = (title) => store.createChecklist(card.value.id, title)
const handleDeleteChecklist = (checklistId) => store.deleteChecklist(card.value.id, checklistId)
const handleAddItem = (checklistId, content) => store.addChecklistItem(card.value.id, checklistId, content)
const handleToggleItem = (checklistId, itemId) => store.toggleChecklistItem(card.value.id, checklistId, itemId)
const handleDeleteItem = (checklistId, itemId) => store.deleteChecklistItem(card.value.id, checklistId, itemId)
const convertItemToCard = (checklistId, itemId) => {
  store.convertItemToCard(card.value.id, itemId)
  console.log('Конвертация в карточку:', checklistId, itemId)
}

// Прокси методы для комментариев
const handleCreateComment = (comment) => store.addComment(card.value.id, comment)
const handleDeleteComment = (commentId) => store.deleteComment(card.value.id, commentId);
const handleEditComment = (params) => store.editComment(card.value.id, params);

</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="card" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close" />

        <div class="relative bg-white rounded-md shadow-2xl w-full max-w-4xl overflow-hidden h-[80vh] flex flex-col">
          <div class="flex items-center justify-between px-4 py-2 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ card.title }}</h2>
            <button @click="close" class="text-gray-400 hover:text-gray-600" aria-label="Закрыть">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-3 gap-6">
              <div class="col-span-2 space-y-6 border-r-2 pr-3 border-gray-200">
                <DescriptionEditor :value="card.description" @update="updateDescription" />

                <FormField label="Исполнители">
                  <AssigneePicker :value="card.assignees" :available-users="availableUsers" @change="updateAssignees" />
                </FormField>

                <FormField label="Метки">
                  <LabelSelector
                    :value="card.labels"
                    :available-labels="workspaceLabels"
                    :workspace-id="workspaceId"
                    @change="updateLabels"
                  />
                </FormField>

                <!-- Передаем card.id и колбэки для чеклистов -->
                <ChecklistSection
                  :card-id="card.id"
                  :checklists="card.checklists"
                  @create-checklist="handleCreateChecklist"
                  @delete-checklist="handleDeleteChecklist"
                  @add-item="handleAddItem"
                  @toggle-item="handleToggleItem"
                  @delete-item="handleDeleteItem"
                  @to-card="convertItemToCard"
                />

                <FormField label="Сроки">
                  <DateRangePicker :start="card.start_date" :end="card.due_date" @change="updateDates" />
                </FormField>

                <FormField label="Вложения">
                  <AttachmentsList :attachments="card.attachments" />
                </FormField>

                <CommentsSection
                  :comments="card.comments"
                  @change="handleCreateComment"
                  @delete-comment="handleDeleteComment"
                  @edit-comment="handleEditComment"
                />
              </div>

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