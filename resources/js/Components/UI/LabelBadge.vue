<script setup>
defineProps({
  label: {
    type: Object,
    required: true
  }
})
</script>

<template>
  <span 
    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
    :style="{ backgroundColor: label.color + '20', color: label.color }"
  >
    {{ label.name }}
  </span>
</template>
