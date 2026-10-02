import axios from 'axios'
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useBoardStore = defineStore('board', () => {
  const currentBoard = ref(null)
  const columns = ref([])
  const selectedCard = ref(null)
  const filters = ref({})

  const sortedColumns = computed(() => {
    return [...columns.value].sort((a, b) => a.ord - b.ord)
  })

  const selectCard = (card) => {
    selectedCard.value = card
  }

  const setFilters = (newFilters) => {
    filters.value = newFilters
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

  const saveColumnsOrder = async (ids) => {
    //  Оптимистично обновляем локальный state (чтобы UI не ждал ответа сервера)
    columns.value = columns.value.map((col, index) => {
      const newOrder = ids.indexOf(col.id)
      return { ...col, order: newOrder !== -1 ? newOrder : index }
    })

    // Отправляем на сервер
    try {
      return await axios.patch(`/columns/orders`, { ids })
    } catch (error) {
      console.error('Ошибка сохранения порядка колонок:', error)
      // Логика отката (перечитывание с сервера) при ошибке
    }
  }

  const saveCardOrder = async (cardId, columnId, neighbors) => {
    try {
      return await axios.patch(`/cards/orders`, {
        cardId,
        columnId,
        prevCardId: neighbors.prev,
        nextCardId: neighbors.next
      })
    } catch (error) {
      console.log('error ')
    }
  }

  return {
    currentBoard,
    columns,
    selectedCard,
    filters,
    sortedColumns,
    selectCard,
    setFilters,
    setBoardData,
    saveColumnsOrder,
    saveCardOrder
  }
})