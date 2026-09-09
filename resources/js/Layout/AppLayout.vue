<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import WorkspaceSwitcher from '@/Components/Layout/WorkspaceSwitcher.vue'
import SidebarNavItem from '@/Components/Layout/SidebarNavItem.vue'
import BoardList from '@/Components/Layout/BoardList.vue'
import UserProfileDropdown from '@/Components/Layout/UserProfileDropdown.vue'
import Topbar from '@/Components/Layout/Topbar.vue'
import CardDetailDrawer from '@/Components/Card/CardDetailDrawer.vue'
import ToastContainer from '@/Components/UI/ToastContainer.vue'
import { useBoardStore } from '@/Stores/board'


const page = usePage()
const store = useBoardStore()

const auth = computed(() => page.props.auth)
const workspaces = computed(() => page.props.workspaces)
const currentWorkspace = computed(() => page.props.currentWorkspace)
const boards = computed(() => page.props.boards)
const currentBoard = computed(() => page.props.board)
const unreadCount = computed(() => page.props.unreadNotifications || 0)
const selectedCard = computed(() => store.selectedCard)

const handleWorkspaceSelect = (workspace) => {
  router.visit(`/workspaces/${workspace.id}`)
}

const handleBoardSelect = (board) => {
  router.visit(`/boards/${board.id}`)
}

const handleFilterChange = (filters) => {
  store.setFilters(filters)
}
</script>

<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex-shrink-0 hidden lg:flex flex-col">
      <!-- Workspace Switcher -->
      <WorkspaceSwitcher 
        :workspaces="workspaces"
        :current-workspace="currentWorkspace"
        @select="handleWorkspaceSelect"
      />
      
      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4">
        <SidebarNavItem 
          icon="inbox" 
          label="Мои задачи" 
          :count="unreadCount"
          :active="route().current('tasks')"
          @click="router.visit('/tasks')"
        />
        <SidebarNavItem 
          icon="calendar" 
          label="Календарь"
          :active="route().current('calendar')"
          @click="router.visit('/calendar')"
        />
        
        <div class="mt-6 px-4">
          <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            Доски
          </h3>
        </div>
        <BoardList 
          :boards="boards"
          :active-board-id="currentBoard?.id"
          @select="handleBoardSelect"
          @create="showCreateBoardModal = true"
        />
      </nav>
      
      <!-- User Profile -->
      <UserProfileDropdown :user="auth.user" />
    </aside>
    
    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden">
      <!-- Topbar -->
      <Topbar 
        @invite="showInviteModal = true"
        @share="showShareModal = true"
        @filter-change="handleFilterChange"
      />
      
      <!-- Page Content -->
      <div class="flex-1 overflow-auto">
        <slot />
      </div>
    </main>
    
    <!-- Global Overlays -->
    <CardDetailDrawer v-if="selectedCard" :card="selectedCard" />
    <ToastContainer />
  </div>
</template>