import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useBoardStore = defineStore('board', () => {
  const currentBoard = ref(null)
  const columns = ref([])
  const selectedCard = ref(null)
  const filters = ref({})
  // const showCreateWorkspaceModal = ref(false)

  const sortedColumns = computed(() => {
    return [...columns.value].sort((a, b) => a.order - b.order)
  })

  const selectCard = (card) => {
    selectedCard.value = card
  }

  // TODO: ????
  // const showWorkspaceModal = () => {
  //   showCreateWorkspaceModal.value = true
  // }

  const setFilters = (newFilters) => {
    filters.value = newFilters
  }

  const moveCardOptimistic = (data) => {
    // Optimistic update logic
    const card = findCard(data.cardId)
    if (card) {
      card.column_id = data.columnId
      card.order = data.position
    }
  }

  const rollbackCardMove = (data) => {
    // Rollback logic
  }

  const findCard = (cardId) => {
    for (const column of columns.value) {
      const card = column.cards.find(c => c.id === cardId)
      if (card) return card
    }
    return null
  }

  const setBoardData = (boardData, columnsData) => {
    currentBoard.value = boardData
    columns.value = columnsData
  }

  return {
    currentBoard,
    columns,
    selectedCard,
    filters,
    sortedColumns,
    selectCard,
    setFilters,
    moveCardOptimistic,
    rollbackCardMove,
    setBoardData
  }
})