<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { PlusIcon, ChevronRightIcon, UsersIcon, BriefcaseIcon } from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import CreateWorkspaceModal from '@/Components/Workspace/CreateWorkspaceModal.vue'

defineProps({
  workspaces: {
    type: Array,
    default: () => []
  }
})

</script>

<template>
  <AppLayout>
    {{ currentWorkspace }}
  </AppLayout>
</template>