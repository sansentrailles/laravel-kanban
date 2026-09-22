<script setup>
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  workspaces: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <Head title="Воркспейсы" />

  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Воркспейсы</h1>

    <ul v-if="props.workspaces.data.length" class="space-y-2">
      <li
        v-for="workspace in props.workspaces.data"
        :key="workspace.id"
        class="p-3 border rounded"
      >
        {{ workspace.name }}
      </li>
    </ul>

    <p v-else class="text-gray-500">Воркспейсов пока нет</p>
  </div>
</template>