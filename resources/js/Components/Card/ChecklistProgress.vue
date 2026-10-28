<script setup>
import { computed } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
})

const completed = computed(() => props.items.filter(i => i.completed))
const completedCount = computed(() => completed.value.length)
const allCount = computed(() => props.items.length)

const percentage = computed(() => {
    // Пустой чеклист не должен давать NaN в width.
    if (allCount.value === 0) {
        return 0
    }

    return Math.min((completedCount.value / allCount.value) * 100, 100)
})

</script>

<template>
    <div class="pb-2">
        <div class="flex items-center justify-between text-xs mb-1">
            <span class="text-gray-600">Прогресс</span>
            <span class="font-medium text-gray-700">
                {{ completedCount }} / {{ allCount }}
            </span>
        </div>
        <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full transition-all duration-300"
                :style="{ width: `${percentage}%`, backgroundColor: '#10b981' }" />
        </div>
    </div>
</template>
