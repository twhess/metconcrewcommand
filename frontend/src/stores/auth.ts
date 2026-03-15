import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const storedUser = localStorage.getItem('user')
  const user = ref<User | null>(
    storedUser && storedUser !== 'undefined'
      ? JSON.parse(storedUser)
      : null
  )

  const isAuthenticated = computed<boolean>(() => !!user.value)

  function setAuth(userData: User): void {
    user.value = userData
    localStorage.setItem('user', JSON.stringify(userData))
  }

  function clearAuth(): void {
    user.value = null
    localStorage.removeItem('user')

    // Clear service worker caches to prevent data leaking between users
    if ('caches' in window) {
      caches.keys().then(keys => keys.forEach(k => caches.delete(k)))
    }
  }

  return {
    user,
    isAuthenticated,
    setAuth,
    clearAuth
  }
})
