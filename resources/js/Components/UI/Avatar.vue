<script setup>
import { computed } from 'vue'

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value)
  }
})

const initials = computed(() => {
  const name = props.user.name || ''
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
})

const avatarColor = computed(() => {
  const colors = [
    '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', 
    '#10b981', '#06b6d4', '#ef4444', '#6366f1'
  ]
  const index = (props.user.id || 0) % colors.length
  return colors[index]
})

const sizeClasses = computed(() => {
  const sizes = {
    xs: 'w-5 h-5',
    sm: 'w-8 h-8',
    md: 'w-10 h-10',
    lg: 'w-12 h-12'
  }
  return sizes[props.size]
})

const textSizeClasses = computed(() => {
  const sizes = {
    xs: 'text-[8px]',
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base'
  }
  return sizes[props.size]
})
</script>

<template>
  <div 
    class="rounded-full flex items-center justify-center overflow-hidden flex-shrink-0"
    :class="sizeClasses"
    :style="user.avatar_url ? {} : { backgroundColor: avatarColor }"
  >
    <img 
      v-if="user.avatar_url"
      :src="user.avatar_url"
      :alt="user.name"
      class="w-full h-full object-cover"
    />
    <span 
      v-else
      class="text-white font-medium"
      :class="textSizeClasses"
    >
      {{ initials }}
    </span>
  </div>
</template>