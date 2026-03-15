import axios, { type AxiosInstance, type AxiosResponse, type AxiosError } from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL + '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: true
})

apiClient.interceptors.response.use(
  (response: AxiosResponse): AxiosResponse => {
    return response
  },
  async (error: AxiosError): Promise<AxiosResponse | never> => {
    // Offline: the service worker's BackgroundSync has queued write requests.
    // Notify the user and return a synthetic response so callers don't hard-fail.
    if (!error.response && !navigator.onLine) {
      const { Notify } = await import('quasar')
      Notify.create({
        type: 'warning',
        message: 'You are offline. This action has been queued.',
        icon: 'cloud_off',
        timeout: 3000
      })
      return {
        data: { success: true, queued: true, message: 'Queued for sync' },
        status: 202,
        statusText: 'Accepted (Queued)',
        headers: {},
        config: error.config!
      } as AxiosResponse
    }

    if (error.response && error.response.status === 401) {
      const authStore = useAuthStore()
      authStore.clearAuth()
      if (router.currentRoute.value.path !== '/login') {
        router.push('/login')
      }
    }
    return Promise.reject(error)
  }
)

export default apiClient
