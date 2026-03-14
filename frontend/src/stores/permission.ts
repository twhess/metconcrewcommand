import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import type { Permission } from '@/types'
import type { AxiosError } from 'axios'

interface PermissionsByModule {
  [module: string]: Permission[]
}

export const usePermissionStore = defineStore('permission', () => {
  const permissions = ref<Permission[]>([])
  const permissionsByModule = ref<PermissionsByModule>({})
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchAllPermissions(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await apiClient.get<{ success: boolean; data: PermissionsByModule }>('/permissions')

      if (response.data.success) {
        // Store permissions grouped by module
        permissionsByModule.value = response.data.data

        // Also store flattened array for convenience
        permissions.value = Object.values(response.data.data).flat()
      }
    } catch (err) {
      const axiosError = err as AxiosError<{ message: string }>
      error.value = axiosError.response?.data?.message || 'Failed to fetch permissions'
      console.error('Error fetching permissions:', err)
    } finally {
      loading.value = false
    }
  }

  function getPermissionsByModule(module: string): Permission[] {
    return permissionsByModule.value[module] || []
  }

  function getAllModules(): string[] {
    return Object.keys(permissionsByModule.value).sort()
  }

  return {
    permissions,
    permissionsByModule,
    loading,
    error,
    fetchAllPermissions,
    getPermissionsByModule,
    getAllModules,
  }
})
