<template>
  <router-view />

  <!-- PWA Update Banner -->
  <q-banner
    v-if="needRefresh"
    class="bg-primary text-white fixed-bottom"
    style="z-index: 9999"
  >
    <template v-slot:avatar>
      <q-icon name="system_update" color="white" />
    </template>
    A new version is available.
    <template v-slot:action>
      <q-btn flat label="Update" @click="applyUpdate" />
      <q-btn flat label="Later" @click="dismissUpdate" />
    </template>
  </q-banner>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { usePWA } from '@/composables/usePWA'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/api/client'

const { needRefresh, applyUpdate, dismissUpdate } = usePWA()
const authStore = useAuthStore()

onMounted(async () => {
  if (authStore.isAuthenticated) {
    try {
      const response = await apiClient.get('/me')
      authStore.setAuth(response.data.user)
    } catch {
      // Cookie expired or invalid — the 401 interceptor handles redirect
    }
  }
})
</script>
