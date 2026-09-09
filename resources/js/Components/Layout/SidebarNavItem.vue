<script setup>
import { computed } from 'vue'
import { 
  InboxIcon, 
  CalendarIcon, 
  ChartBarIcon, 
  UsersIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  icon: {
    type: String,
    required: true
  },
  label: {
    type: String,
    required: true
  },
  count: {
    type: Number,
    default: undefined
  },
  active: {
    type: Boolean,
    default: false
  }
})

defineEmits(['click'])

const iconComponent = computed(() => {
  const icons = {
    inbox: InboxIcon,
    calendar: CalendarIcon,
    chart: ChartBarIcon,
    users: UsersIcon,
  }
  return icons[props.icon] || InboxIcon
})
</script>

<template>
  <button
    @click="$emit('click')"
    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors"
    :class="active 
      ? 'bg-slate-800 text-white border-r-2 border-blue-500' 
      : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
  >
    <component :is="iconComponent" class="w-5 h-5 flex-shrink-0" />
    <span class="flex-1 text-left">{{ label }}</span>
    <span 
      v-if="count !== undefined && count > 0"
      class="bg-blue-600 text-white text-xs font-medium px-2 py-0.5 rounded-full"
    >
      {{ count > 99 ? '99+' : count }}
    </span>
  </button>
</template>
