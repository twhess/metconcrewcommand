<template>
  <q-layout view="hHh lpR fFf">
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-btn
          v-if="authStore.isAuthenticated"
          flat
          dense
          round
          icon="menu"
          @click="toggleSidebar"
          class="q-mr-sm"
        />
        <q-toolbar-title>
          MetCon Crew Command
        </q-toolbar-title>
        <q-btn
          v-if="authStore.isAuthenticated"
          flat
          label="Logout"
          @click="handleLogout"
        />
      </q-toolbar>
    </q-header>

    <Sidebar v-if="authStore.isAuthenticated" ref="sidebarRef" />

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import Sidebar from '@/components/Sidebar.vue'

const authStore = useAuthStore()
const router = useRouter()
const $q = useQuasar()
const sidebarRef = ref<InstanceType<typeof Sidebar> | null>(null)

const handleLogout = async (): Promise<void> => {
  try {
    await apiClient.post('/auth/logout')
  } catch (error) {
    console.error('Logout error:', error)
  } finally {
    authStore.clearAuth()
    $q.notify({
      type: 'positive',
      message: 'Logged out successfully'
    })
    router.push('/login')
  }
}

function toggleSidebar() {
  if (sidebarRef.value) {
    sidebarRef.value.drawerOpen = !sidebarRef.value.drawerOpen
  }
}
</script>
