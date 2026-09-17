<script setup>
import { reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { XIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits(['close', 'created'])

const form = useForm({
  name: '',
  description: '',
})

const errors = reactive({
  name: '',
})

const submit = () => {
  errors.name = ''

  form.post('/workspaces', {
    onSuccess: (page) => {
      // Если есть ошибки валидации
      if (page.props.errors?.name) {
        errors.name = page.props.errors.name
        return
      }

      // Успешно создано
      emit('created', page.props.workspace)
    },
    onError: (error) => {
      if (error.name) {
        errors.name = error.name
      }
    },
  })
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Backdrop -->
    <div
      class="absolute inset-0 bg-black/50"
      @click="$emit('close')"
    />

    <!-- Modal -->
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-900">
          Создать воркспейс
        </h2>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XIcon class="w-6 h-6" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Название *
          </label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Например, Project Orion"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.name }"
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-600">
            {{ errors.name }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Описание
          </label>
          <textarea
            v-model="form.description"
            placeholder="Краткое описание воркспейса (опционально)"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Отмена
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? 'Создание...' : 'Создать' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>