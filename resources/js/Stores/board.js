// Stores/board.js
import axios from 'axios'
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useBoardStore = defineStore('board', () => {
  const currentBoard = ref(null)
  const columns = ref([])
  const selectedCard = ref(null)
  const filters = ref({})
  const labels = ref([])

  // Геттер для отсортированных колонок (используем order)
  const sortedColumns = computed(() => {
    return [...columns.value].sort((a, b) => a.ord - b.ord)
  })

  const selectCard = (card) => { selectedCard.value = card }
  const setFilters = (newFilters) => { filters.value = newFilters }
  const findCard = (cardId) => {
    for (const column of columns.value) {
      const card = column.cards.find(c => c.id === Number(cardId))
      if (card) return card
    }
    return null
  }

  const setBoardData = (boardData) => {
    currentBoard.value = boardData
    // Сохраняем колонки и сразу сортируем
    columns.value = (boardData.columns?.data || []).sort((a, b) => a.ord - b.ord)
  }

  const setLabels = (workspaceLabels) => {
    labels.value = workspaceLabels || []
  }

  // --- Realtime (Echo) ---
  const connectRealtime = (workspaceId) => {
    window.Echo.private(`board.${workspaceId}`)
      .listen('.card.updated', (e) => {
        updateCard(e.card, true) // true = пришло по WS
      })
      .listen('.card.moved', (e) => {
        moveCardRemote(e.card)
      })
      .listen('.card.created', (e) => {
        const column = columns.value.find(c => c.id === e.card.column_id)
        if (column) {
          const exists = column.cards.some(c => c.id === e.card.id)
          if (!exists) {
            column.cards.push(e.card)
          }
        }
      })
      .listen('.column.updated', (e) => {
        updateColumn(e.column)
      })
      .listen('.column.reordered', (e) => {
        reorderColumns(e.columns)
      })
  }

  const disconnectRealtime = (workspaceId) => {
    window.Echo.leave(`board.${workspaceId}`)
  }

  const updateColumn = (updatedColumn, fromRealtime = false) => {
    const index = columns.value.findIndex(c => c.id === updatedColumn.id)
    if (index !== -1) {
      // Мержим данные (например, если изменилось название колонки или WIP-лимит)
      Object.assign(columns.value[index], updatedColumn)
    }
  }

  const reorderColumns = (cols) => {
    for (const col of cols) {
      const column = columns.value.find(c => c.id === Number(col.id))
      if (column)  {
        column.ord = col.ord
      }
    }
  }

  // --- Обновление карточки ---
  const updateCard = (updatedCard, fromRealtime = false) => {
    for (const column of columns.value) {
      const cardIndex = column.cards.findIndex(c => c.id === updatedCard.id)
      if (cardIndex !== -1) {
        // Если карточка открыта в модалке у текущего юзера
        if (selectedCard.value && selectedCard.value.id === updatedCard.id && !fromRealtime) {
          Object.assign(selectedCard.value, updatedCard)
        } else if (fromRealtime) {
           // При обновлении по WS просто мержим данные
          Object.assign(column.cards[cardIndex], updatedCard)
        }
        return
      }
    }
  }

  // --- Drag & Drop Колонок ---
  const saveColumnsOrder = async (ids, boardId) => {
    console.log(ids)
    // Оптимистично обновляем order
    columns.value.forEach((col, index) => {
      col.ord = ids.indexOf(col.id)
    })

    try {
      await axios.patch(route('boards.columns.orders', boardId), { ids })
    } catch (error) {
      console.error('Ошибка сохранения порядка колонок:', error)
      // TODO: Откат на предыдущий порядок
    }
  }

  // --- Создание карточки ---
  const createCard = async (columnId, title) => {
    // Находим колонку, в которую добавляем карточку
    const column = columns.value.find(c => c.id === Number(columnId))
    if (!column) return

    try {
      // Отправляем запрос на создание карточки
      const response = await axios.post(route('boards.cards.store', columnId), {
        title: title
      })

      const newCard = response.data.card // Предполагаем, что бэкенд вернет объект карточки

      if (newCard) {
        // Добавляем карточку в конец массива этой колонки
        column.cards.push(newCard)
      }
    } catch (error) {
      console.error('Ошибка создания карточки:', error)
    }
  }

  // --- Перенос карточки, пришедший по realtime ---
  // Карточка у нас уже есть (доска полная), в payload приходит ее новая колонка и ord.
  // Убираем карточку из текущей колонки и вставляем в целевую по порядку ord.
  const moveCardRemote = (updatedCard) => {
    const cardId = updatedCard?.id
    const targetColumnId = updatedCard?.column_id
    if (!cardId || targetColumnId == null) return

    // Находим, где карточка сейчас на этой доске
    let ownerColumn = null
    for (const column of columns.value) {
      if (column.cards.some((c) => c.id === cardId)) {
        ownerColumn = column
        break
      }
    }

    if (!ownerColumn) return // карточки нет на доске — двигать нечего

    const card = ownerColumn.cards.find((c) => c.id === cardId)
    Object.assign(card, updatedCard)

    if (ownerColumn.id === targetColumnId) {
      // Перенос внутри той же колонки — сортируем по ord
      ownerColumn.cards.sort((a, b) => Number(a.ord) - Number(b.ord))
      return
    }

    // Убираем из старой колонки
    ownerColumn.cards = ownerColumn.cards.filter((c) => c.id !== cardId)

    // Вставляем в целевую колонку на позицию по ord
    const target = columns.value.find((col) => col.id === targetColumnId)
    if (!target) {
      ownerColumn.cards.push(card) // целевую колонку не нашли — вернем на место
      return
    }

    let position = 0
    while (position < target.cards.length && Number(target.cards[position].ord) < Number(updatedCard.ord)) {
      position++
    }
    target.cards.splice(position, 0, card)
  }

  // --- Drag & Drop Карточек ---

  // Единая точка изменения состояния при перемещении карточки.
  // vue-draggable-plus работает с локальными копиями массивов в Column.vue,
  // поэтому после дропа мы сами детерминированно переносим карточку в сторе,
  // и Vue перестраивает обе колонки из данных стора.
  const applyCardMove = (fromColumnId, toColumnId, cardId, targetIndex) => {
    const source = columns.value.find((col) => col.id === fromColumnId)
    const target = columns.value.find((col) => col.id === toColumnId)
    if (!source || !target) return

    // [инвариант] Карточка должна числиться ровно в одной колонке.
    // Если вдруг она уже есть в целевой — считаем это следствием гонки и чиним.
    const alreadyInTarget = target.cards.findIndex((card) => card.id === cardId)
    if (alreadyInTarget !== -1) {
      console.warn('Карточка уже числится в целевой колонке, дубль устранён', {
        cardId, fromColumnId, toColumnId
      })
    }

    const index = source.cards.findIndex((card) => card.id === cardId)
    if (index === -1) {
      if (alreadyInTarget !== -1) return // карточка уже в целевой, ничего делать не надо
      console.error('Карточка не найдена в исходной колонке стора!', { cardId, fromColumnId })
      return
    }

    // Splice-out + splice-in корректно работает и для тех же колонок (переупорядочивание),
    // и для переноса между колонками, потому что sortable уже выставил финальный DOM-индекс.
    const [card] = source.cards.splice(index, 1)
    card.column_id = toColumnId
    if (alreadyInTarget === -1) {
      target.cards.splice(targetIndex, 0, card)
    }
    bumpCardsRenderTick()
  }

  // Счётчик для принудительной перерисовки контейнеров карточек после DnD.
  // SortableJS физически перемещает DOM-узлы сам, Vue об этом не знает, и в целевой
  // колонке всегда остаётся «призрак» перенесённого узла + узел Vue. Смена key
  // заставляет Vue полностью пересобрать контейнеры из данных стора.
  const cardsRenderTick = ref(0)
  const bumpCardsRenderTick = () => {
    cardsRenderTick.value++
  }

  // Снимок порядка карточек ДО перемещения (для отката, если сервер отклонит запрос).
  // Должен снимать ДО мутации массивов, поэтому вызывается в onStart перетаскивания.
  const boardSnapshot = ref(null)

  const snapshotBoard = () => {
    boardSnapshot.value = columns.value.map((col) => ({
      colId: col.id,
      cardIds: col.cards.map((c) => c.id),
    }))
  }

  // Восстанавливает массивы карточек (и column_id карточек) по снапшоту
  const restoreBoardSnapshot = () => {
    if (!boardSnapshot.value) return

    const allCards = columns.value.flatMap((col) => col.cards)

    for (const { colId, cardIds } of boardSnapshot.value) {
      const column = columns.value.find((c) => c.id === colId)
      if (!column) continue

      column.cards = cardIds
        .map((id) => allCards.find((c) => c.id === id))
        .filter(Boolean)

      // Карточка снова числится в своей старой колонке
      column.cards.forEach((card) => {
        card.column_id = colId
      })
    }

    bumpCardsRenderTick()
    boardSnapshot.value = null
  }

  // Отправляет итоговую позицию карточки (prev/next уже посчитаны по финальному
  // состоянию стора в applyCardMove)
  const saveCardOrder = async (movedCardId, targetColumnId) => {
    const cardId = Number(movedCardId)
    const orderedCardIds = columns.value
      .find((col) => col.id === Number(targetColumnId))
      ?.cards.map((c) => c.id) ?? []

    const index = orderedCardIds.indexOf(cardId)
    const prev = index > 0 ? orderedCardIds[index - 1] : null
    const next = index !== -1 && index < orderedCardIds.length - 1 ? orderedCardIds[index + 1] : null

    try {
      await axios.patch(route('boards.cards.orders'), {
        cardId: cardId,
        columnId: Number(targetColumnId),
        prevCardId: prev,
        nextCardId: next
      })
      boardSnapshot.value = null
    } catch (error) {
      console.warn('Не удалось сохранить позицию карточки', {
        cardId,
        targetColumnId,
        response: error?.response?.status,
        message: error?.response?.data?.message,
      })
      restoreBoardSnapshot()
    }
  }

  // --- Обновление полей карточки (Optimistic UI) ---
  const updateCardField = async (cardId, fieldName, displayValue, payloadKey = null, payloadValue = null) => {
    const card = findCard(cardId)
    if (!card) return

    const oldValue = card[fieldName]
    // Оптимистично обновляем UI (и на доске, и в модалке, так как ссылка одна)
    card[fieldName] = displayValue

    try {
      const data = { [payloadKey || fieldName]: payloadValue !== null ? payloadValue : displayValue }
      await axios.patch(route('boards.cards.update', cardId), data)
    } catch (error) {
      // Откат при ошибке
      card[fieldName] = oldValue
      console.error('Update error:', error)
    }
  }

  // Обновление дат (т.к. два поля сразу)
  const updateCardDates = async (cardId, dates) => {
    const card = findCard(cardId)
    if (!card) return

    const oldStart = card.start_date
    const oldDue = card.due_date

    card.start_date = dates.start_date || null
    card.due_date = dates.due_date || null

    try {
      await axios.patch(route('boards.cards.update', cardId), {
        start_date: card.start_date,
        due_date: card.due_date
      })
    } catch (error) {
      card.start_date = oldStart
      card.due_date = oldDue
      console.error('Ошибка обновления дат:', error)
    }
  }

  // Логика комментариев
  const addComment = async (cardId, comment) => {
    const card = findCard(cardId)
    if (!card) return
    
    try {
      const response = await axios.post(route('cards.comments.store', cardId), {
        content: comment
      })

      const newComment = response.data.createdComment

      if (newComment) {
        const card = findCard(cardId)
        if (card) card.comments.push(newComment)
      }
    } catch (error) {
      console.error('Ошибка создания чеклиста:', error)
    }
  }

  const deleteComment = async (cardId, commentId) => {
    const card = findCard(cardId)
    if (!card) return
    const oldComments = [...card.comments]
    
    card.comments = card.comments.filter(c => c.id !== commentId)

    try {
      await axios.delete(route('cards.comments.delete', { card: card.id, comment: commentId }))
    } catch (error) {
      card.comments = oldComments // Откат
      console.error('Ошибка удаления комментария:', error)
    }
  }

  const editComment = async (cardId, params) => {
    const card = findCard(cardId)
    if (!card) return
    const { id, content } = params

    const comment = card.comments.find(c => c.id === id)
    if (!comment) return

    const oldContent = comment.content
    const oldIsEdited = comment.is_edited // Сохраняем старый фла

    // 1. Оптимистично обновляем текст в UI
    comment.content = content
    comment.is_edited = true

    try {
      await axios.patch(route('cards.comments.edit', { 
        card: cardId, 
        comment: id 
      }), {
        content: content
      })
    } catch (error) {
      comment.content = oldContent
      comment.is_edited = oldIsEdited // Возвращаем старый флаг
      console.error('Ошибка редактирования комментария:', error)
    }
  }

  // --- Логика Чеклистов ---
  const createChecklist = async (cardId, title) => {
    try {
      const response = await axios.post(route('cards.checklist.store', cardId), { title })
      const newChecklist = response.data.createdChecklist
      if (newChecklist) {
        const card = findCard(cardId)
        if (card) card.checklists.push(newChecklist)
      }
    } catch (error) {
      console.error('Ошибка создания чеклиста:', error)
    }
  }

  const deleteChecklist = async (cardId, checklistId) => {
    const card = findCard(cardId)
    if (!card) return
    const oldChecklists = [...card.checklists]
    
    // Оптимистичное удаление
    card.checklists = card.checklists.filter(c => c.id !== checklistId)

    try {
      await axios.delete(route('cards.checklist.delete', checklistId))
    } catch (error) {
      card.checklists = oldChecklists // Откат
      console.error('Ошибка удаления чеклиста:', error)
    }
  }

  const addChecklistItem = async (cardId, checklistId, content) => {
    try {
      const response = await axios.post(route('cards.checklist.items.store', checklistId), { content })
      const newItem = response.data.createdChecklistItem
      
      const card = findCard(cardId)
      if (card) {
        const checklist = card.checklists.find(c => c.id === checklistId)
        if (checklist) checklist.items.push(newItem)
      }
    } catch (error) {
      console.error('Ошибка создания пункта:', error)
    }
  }

  const toggleChecklistItem = async (cardId, checklistId, itemId) => {
    const card = findCard(cardId)
    if (!card) return
    const checklist = card.checklists.find(c => c.id === checklistId)
    if (!checklist) return
    
    const item = checklist.items.find(i => i.id === itemId)
    if (!item) return

    const oldValue = item.completed
    item.completed = !oldValue // Оптимистично меняем статус

    try {
      await axios.patch(route('cards.checklists.items.toggle', { checklist: checklistId, item: itemId }))
    } catch (error) {
      item.completed = oldValue // Откат
      console.error('Ошибка переключения пункта:', error)
    }
  }

  const deleteChecklistItem = async (cardId, checklistId, itemId) => {
    const card = findCard(cardId)
    if (!card) return
    const checklist = card.checklists.find(c => c.id === checklistId)
    if (!checklist) return

    const oldItems = [...checklist.items]
    checklist.items = checklist.items.filter(i => i.id !== itemId)

    try {
      await axios.delete(route('cards.checklists.items.delete', { checklist: checklistId, item: itemId }))
    } catch (error) {
      checklist.items = oldItems // Откат
      console.error('Ошибка удаления пункта:', error)
    }
  }

  const convertItemToCard = async (cardId, itemId) => {
  }

  const createLabel = async (workspaceId, labelData) => {
    try {
      const response = await axios.post(route('workspaces.labels.store', workspaceId), labelData)
      const newLabel = response.data.createdLabel || response.data
      
      if (newLabel) {
        labels.value.push(newLabel)
        return newLabel
      }
    } catch (error) {
      console.error('Ошибка создания метки:', error)
      throw error // Пробрасываем ошибку для обработки в компоненте
    }
  }

  // Вложения
  const uploadAttachment = async (cardId, files) => {

    const card = findCard(cardId)
    if (!card || !files.length) return

    if (!card.attachments) card.attachments = []

    for (const file of files) {
      const formData = new FormData()
      formData.append('file', file)

      try {
        const response = await axios.post(route('cards.attachments.store', cardId), formData)

        if (response.data.attachment) {
          card.attachments.push(response.data.attachment)
        }
      } catch (error) {
        console.error('Ошибка загрузки файла: ', error)
      }
    }
  }

  const deleteAttachment = async (cardId, attachmentId) => {
    const card = findCard(cardId)
    if (!card) return

    const oldAttachments = [...card.attachments]

    card.attachments = card.attachmens.filter(a => a.id !== attachmentId)

    try {
      await axios.delete(route('cards.attachments.destroy', {card: cardId, attachment: attachmentId}))
    } catch (error) {
      card.attachments = oldAttachments
      console.error('Ошибка удаления вложения:', error)
    }
  }

  return {
    currentBoard, columns, selectedCard, filters, sortedColumns, labels,
    createCard, selectCard, setFilters, setBoardData, connectRealtime, disconnectRealtime,
    saveColumnsOrder, saveCardOrder, applyCardMove, findCard, updateCard,
    updateCardField, updateCardDates, snapshotBoard,
    cardsRenderTick, bumpCardsRenderTick,
    createChecklist, deleteChecklist, addChecklistItem, toggleChecklistItem, deleteChecklistItem,
    addComment, deleteComment, editComment,
    setLabels, createLabel,
    uploadAttachment, deleteAttachment
  }
})