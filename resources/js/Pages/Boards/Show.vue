<script setup>
import BoardView from '@/Components/Board/BoardView.vue'
import AppLayout from '@/Layout/AppLayout.vue'
import { ref } from 'vue'
// import AppLayout from '@/Layouts/AppLayout.vue'
// import BoardView from '@/Components/Board/BoardView.vue'

// Мок-данные для демонстрации (потом заменим на данные из Laravel)
const board = ref({
  id: 1,
  name: 'Project Orion',
  description: 'Разработка нового продукта',
  color: '#3b82f6'
})

const columns = ref([
  {
    id: 1,
    title: 'Backlog',
    order: 1,
    wip_limit: null,
    cards: [
      {
        id: 1,
        title: 'Настроить CI/CD пайплайн',
        description: 'Настроить GitHub Actions для автоматического деплоя',
        status: 'backlog',
        priority: 'high',
        due_date: '2026-09-20',
        column_id: 1,
        order: 1,
        labels: [
          { id: 1, name: 'Backend', color: '#10b981' },
          { id: 2, name: 'DevOps', color: '#f59e0b' }
        ],
        assignees: [
          { id: 1, name: 'Alex Johnson', email: 'alex@example.com' }
        ],
        checklist: { total: 5, completed: 2 },
        comments_count: 3,
        attachments_count: 1
      },
      {
        id: 2,
        title: 'Дизайн главной страницы',
        description: 'Создать макет в Figma',
        status: 'backlog',
        priority: 'medium',
        due_date: '2026-09-25',
        column_id: 1,
        order: 2,
        labels: [
          { id: 3, name: 'Design', color: '#a855f7' }
        ],
        assignees: [
          { id: 2, name: 'Sarah Smith', email: 'sarah@example.com' }
        ],
        checklist: null,
        comments_count: 0,
        attachments_count: 0
      },
      {
        id: 3,
        title: 'Написать документацию API',
        description: null,
        status: 'backlog',
        priority: 'low',
        due_date: null,
        column_id: 1,
        order: 3,
        labels: [
          { id: 1, name: 'Backend', color: '#10b981' }
        ],
        assignees: [],
        checklist: { total: 10, completed: 0 },
        comments_count: 1,
        attachments_count: 0
      }
    ]
  },
  {
    id: 2,
    title: 'In Progress',
    order: 2,
    wip_limit: 5,
    cards: [
      {
        id: 4,
        title: 'Реализовать авторизацию',
        description: 'JWT токены + refresh токены',
        status: 'in_progress',
        priority: 'high',
        due_date: '2026-09-15',
        column_id: 2,
        order: 1,
        labels: [
          { id: 1, name: 'Backend', color: '#10b981' },
          { id: 4, name: 'Security', color: '#ef4444' }
        ],
        assignees: [
          { id: 1, name: 'Alex Johnson', email: 'alex@example.com' },
          { id: 3, name: 'Mike Brown', email: 'mike@example.com' }
        ],
        checklist: { total: 8, completed: 5 },
        comments_count: 7,
        attachments_count: 2
      },
      {
        id: 5,
        title: 'Верстка дашборда',
        description: 'Адаптивная верстка с графиками',
        status: 'in_progress',
        priority: 'medium',
        due_date: '2026-09-18',
        column_id: 2,
        order: 2,
        labels: [
          { id: 5, name: 'Frontend', color: '#3b82f6' }
        ],
        assignees: [
          { id: 2, name: 'Sarah Smith', email: 'sarah@example.com' }
        ],
        checklist: { total: 12, completed: 4 },
        comments_count: 2,
        attachments_count: 0
      }
    ]
  },
  {
    id: 3,
    title: 'Review',
    order: 3,
    wip_limit: null,
    cards: [
      {
        id: 6,
        title: 'Оптимизация запросов к БД',
        description: 'Добавить индексы, исправить N+1',
        status: 'review',
        priority: 'high',
        due_date: '2026-09-12',
        column_id: 3,
        order: 1,
        labels: [
          { id: 1, name: 'Backend', color: '#10b981' },
          { id: 6, name: 'Performance', color: '#ec4899' }
        ],
        assignees: [
          { id: 3, name: 'Mike Brown', email: 'mike@example.com' }
        ],
        checklist: { total: 6, completed: 6 },
        comments_count: 4,
        attachments_count: 1
      }
    ]
  },
  {
    id: 4,
    title: 'Done',
    order: 4,
    wip_limit: null,
    cards: [
      {
        id: 7,
        title: 'Инициализация проекта',
        description: 'Настроить Laravel + Vue + Inertia',
        status: 'done',
        priority: 'high',
        due_date: '2026-09-01',
        column_id: 4,
        order: 1,
        labels: [
          { id: 1, name: 'Backend', color: '#10b981' },
          { id: 5, name: 'Frontend', color: '#3b82f6' }
        ],
        assignees: [
          { id: 1, name: 'Alex Johnson', email: 'alex@example.com' }
        ],
        checklist: { total: 15, completed: 15 },
        comments_count: 12,
        attachments_count: 3
      },
      {
        id: 8,
        title: 'Настройка ESLint и Prettier',
        description: null,
        status: 'done',
        priority: 'low',
        due_date: '2026-09-02',
        column_id: 4,
        order: 2,
        labels: [
          { id: 5, name: 'Frontend', color: '#3b82f6' }
        ],
        assignees: [
          { id: 2, name: 'Sarah Smith', email: 'sarah@example.com' }
        ],
        checklist: { total: 3, completed: 3 },
        comments_count: 1,
        attachments_count: 0
      }
    ]
  }
])

const showAddColumnModal = ref(false)

const openCardDetail = (card) => {
  console.log('Open card:', card)
  // Здесь можно открыть CardDetailDrawer
}

const handleCardDrop = async (data) => {
  console.log('Card drop:', data)
  // Здесь будет логика перемещения карточки
}

const handleColumnDrop = async (data) => {
  console.log('Column drop:', data)
  // Здесь будет логика перемещения колонки
}
</script>

<template>
  <AppLayout>
    <BoardView 
      :board="board"
      :columns="columns"
      @card-click="openCardDetail"
      @card-drop="handleCardDrop"
      @column-drop="handleColumnDrop"
      @add-column="showAddColumnModal = true"
    />
  </AppLayout>
</template>