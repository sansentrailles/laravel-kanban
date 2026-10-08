import { useBoardStore } from "@/Stores/board";
import { usePage } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted } from "vue";

export function useBoardRealtime() {
    const page = usePage()
    const store = useBoardStore()
    const workspaceId = computed(() => page.props.currentWorkspace?.id)

    console.log('ws id: ', workspaceId.value)

//     onMounted(() => {
//       if (!workspaceId.value) return
// console.log(`board.${workspaceId.value}`);

//       window.Echo.private(`board.${workspaceId.value}`)
//         .listen('.card.updated', (e) => {
//             console.log('🔔 Карточка обновлена:', e.card)
//             store.updateCard(e.card)
//         })
//         .listen('.card.moved', (e) => {
//             // Можно добавить обработку перемещения карточки
//             console.log('🔀 Карточка перемещена:', e.card)
//             store.updateCard(e.card)
//         })
//         .listen('.card.deleted', (e) => {
//             console.log('🗑️ Карточка удалена:', e.cardId)
//             store.removeCard(e.cardId)
//         })
//     })

  onUnmounted(() => {
    if (workspaceId.value) {
      window.Echo.leave(`board.${workspaceId.value}`)
    }
  })
}