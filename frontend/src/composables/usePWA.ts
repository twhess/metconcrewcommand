import { ref, onMounted, onUnmounted, watch } from 'vue'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'

export function usePWA() {
  const isOnline = ref(navigator.onLine)
  const needRefresh = ref(false)
  const offlineReady = ref(false)

  let registration: ServiceWorkerRegistration | undefined

  async function registerSW() {
    if (!('serviceWorker' in navigator)) return

    try {
      registration = await navigator.serviceWorker.register('/sw.js', { type: 'module' })

      // Check for updates every 60 minutes
      setInterval(() => {
        registration?.update()
      }, 60 * 60 * 1000)

      // Listen for waiting SW (new version available)
      if (registration.waiting) {
        needRefresh.value = true
      }

      registration.addEventListener('updatefound', () => {
        const newWorker = registration?.installing
        if (!newWorker) return

        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            needRefresh.value = true
          } else if (newWorker.state === 'activated' && !navigator.serviceWorker.controller) {
            offlineReady.value = true
          }
        })
      })
    } catch (error) {
      console.error('SW registration error:', error)
    }
  }

  function handleOnline() {
    isOnline.value = true
  }

  function handleOffline() {
    isOnline.value = false
  }

  // When coming back online, validate the auth session
  watch(isOnline, async (online, wasOnline) => {
    if (online && wasOnline === false) {
      const authStore = useAuthStore()
      if (authStore.isAuthenticated) {
        try {
          await apiClient.get('/me')
        } catch {
          // 401 will be caught by the existing interceptor
          // which calls clearAuth() and redirects to login
        }
      }
    }
  })

  onMounted(() => {
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
    registerSW()
  })

  onUnmounted(() => {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
  })

  async function applyUpdate(): Promise<void> {
    const waitingSW = registration?.waiting
    if (waitingSW) {
      waitingSW.postMessage({ type: 'SKIP_WAITING' })
      waitingSW.addEventListener('statechange', () => {
        if (waitingSW.state === 'activated') {
          window.location.reload()
        }
      })
    }
  }

  function dismissUpdate(): void {
    needRefresh.value = false
  }

  return {
    isOnline,
    needRefresh,
    offlineReady,
    applyUpdate,
    dismissUpdate
  }
}
