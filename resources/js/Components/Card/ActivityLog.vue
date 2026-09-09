<script setup>
import Avatar from '@/Components/UI/Avatar.vue'

defineProps({
  activities: {
    type: Array,
    default: () => []
  }
})

const formatDate = (date) => {
  const now = new Date()
  const activityDate = new Date(date)
  const diffMs = now - activityDate
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  
  if (diffMins < 1) return 'только что'
  if (diffMins < 60) return `${diffMins} мин назад`
  if (diffHours < 24) return `${diffHours} ч назад`
  if (diffDays < 7) return `${diffDays} дн назад`
  
  return activityDate.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short'
  })
}
</script>

<template>
  <div class="space-y-3">
    <div 
      v-for="activity in activities"
      :key="activity.id"
      class="flex gap-3"
    >
      <Avatar :user="activity.user" size="xs" />
      <div class="flex-1 min-w-0">
        <div class="text-sm text-gray-700">
          <span class="font-medium text-gray-900">{{ activity.user.name }}</span>
          {{ activity.action }}
        </div>
        <div class="text-xs text-gray-500 mt-0.5">
          {{ formatDate(activity.created_at) }}
        </div>
      </div>
    </div>
    
    <div v-if="!activities.length" class="text-sm text-gray-400 text-center py-4">
      История пуста
    </div>
  </div>
</template>