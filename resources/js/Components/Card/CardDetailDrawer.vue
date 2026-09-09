<script setup>
import { computed } from 'vue'
// import { XIcon } from '@heroicons/vue/24/outline'
import { useBoardStore } from '@/Stores/board'
import { useDebounceFn } from '@vueuse/core'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const store = useBoardStore()
const card = computed(() => store.selectedCard)

const close = () => {
  store.selectCard(null)
}

// Debounced updates
const updateDescription = useDebounceFn(async (value) => {
  await axios.patch(`/api/cards/${card.value.id}`, { description: value })
}, 1000)

const updateStatus = async (status) => {
  await axios.patch(`/api/cards/${card.value.id}`, { status })
}

const updatePriority = async (priority) => {
  await axios.patch(`/api/cards/${card.value.id}`, { priority })
}

const updateAssignees = async (assignees) => {
  await axios.patch(`/api/cards/${card.value.id}`, { assignees })
}

const updateLabels = async (labels) => {
  await axios.patch(`/api/cards/${card.value.id}`, { labels })
}

const updateDates = async (dates) => {
  await axios.patch(`/api/cards/${card.value.id}`, dates)
}
</script>

<template>
  <Teleport to="body">
    <Transition name="drawer">
      <div v-if="card" class="fixed inset-0 z-50 flex justify-end">
        <!-- Backdrop -->
        <div 
          class="absolute inset-0 bg-black/50"
          @click="close"
        />
        
        <!-- Drawer Panel -->
        <div class="relative w-full max-w-2xl bg-white shadow-2xl flex flex-col h-full overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b">
            <h2 class="text-xl font-semibold">{{ card.title }}</h2>
            <button @click="close" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>
          
          <!-- Content -->
          <div class="flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-3 gap-6">
              <!-- Основная область (2/3) -->
              <div class="col-span-2 space-y-6">
                <!-- Описание -->
                <DescriptionEditor 
                  :value="card.description"
                  @update="updateDescription"
                />
                
                <!-- Чек-листы -->
                <ChecklistSection :checklists="card.checklists" />
                
                <!-- Комментарии -->
                <CommentsSection :comments="card.comments" />
              </div>
              
              <!-- Сайдбар (1/3) -->
              <div class="space-y-4">
                <!-- Статус -->
                <FormField label="Статус">
                  <StatusDropdown :value="card.status" @change="updateStatus" />
                </FormField>
                
                <!-- Приоритет -->
                <FormField label="Приоритет">
                  <PrioritySelector :value="card.priority" @change="updatePriority" />
                </FormField>
                
                <!-- Исполнители -->
                <FormField label="Исполнители">
                  <AssigneePicker :value="card.assignees" @change="updateAssignees" />
                </FormField>
                
                <!-- Метки -->
                <FormField label="Метки">
                  <LabelSelector :value="card.labels" @change="updateLabels" />
                </FormField>
                
                <!-- Даты -->
                <FormField label="Сроки">
                  <DateRangePicker 
                    :start="card.start_date"
                    :end="card.due_date"
                    @change="updateDates"
                  />
                </FormField>
                
                <!-- Вложения -->
                <FormField label="Вложения">
                  <AttachmentsList :attachments="card.attachments" />
                </FormField>
                
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
.drawer-enter-active,
.drawer-leave-active {
  transition: all 0.3s ease;
}

.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
}

.drawer-enter-from .drawer-panel,
.drawer-leave-to .drawer-panel {
  transform: translateX(100%);
}
</style>