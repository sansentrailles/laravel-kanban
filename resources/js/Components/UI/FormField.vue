<script setup>
defineProps({
  label: {
    type: String,
    required: true
  }
})
</script>

<template>
  <div class="space-y-1.5">
    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">
      {{ label }}
    </label>
    <div class="text-sm">
      <slot />
    </div>
  </div>
</template>