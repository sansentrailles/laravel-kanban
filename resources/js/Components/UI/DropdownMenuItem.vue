<script setup>
const props = defineProps({
  destructive: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])

const handleClick = () => {
  emit('click')
}
</script>

<template>
  <button
    @click="handleClick"
    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 transition-colors"
    :class="destructive ? 'text-red-600' : 'text-gray-700'"
  >
    <slot />
  </button>
</template>