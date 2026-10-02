<script setup>
import { useBoardStore } from '@/Stores/board';
import { useDebounceFn } from '@vueuse/core'
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';
import DescriptionEditor from './DescriptionEditor.vue';
import ActivityLog from './ActivityLog.vue';

const store = useBoardStore()
const card = computed(() => store.selectedCard)
const originalDescription = ref('')

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
        <div class="relative bg-white rounded-md shadow-2xl w-full max-w-4xl overflow-hidden">
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
              <div class="col-span-2 space-y-6">
                <!-- Описание -->
                <DescriptionEditor :value="card.description" @update="updateDescription" />

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
                  <DateRangePicker :start="card.start_date" :end="card.due_date" @change="updateDates" />
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