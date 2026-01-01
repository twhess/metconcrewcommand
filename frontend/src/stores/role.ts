import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import type { Role, ApiResponse } from '@/types'

export const useRoleStore = defineStore('role', () => {
  const roles = ref<Role[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchRoles() {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Role[]>>('/roles')
      roles.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load roles'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createRole(data: Partial<Role>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Role>>('/roles', data)
      roles.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create role'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateRole(id: number, data: Partial<Role>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<Role>>(`/roles/${id}`, data)
      const index = roles.value.findIndex(r => r.id === id)
      if (index !== -1) {
        roles.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update role'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteRole(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/roles/${id}`)
      roles.value = roles.value.filter(r => r.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete role'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function assignPermissions(roleId: number, permissionIds: number[]) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Role>>(`/roles/${roleId}/permissions`, {
        permission_ids: permissionIds,
      })
      const index = roles.value.findIndex(r => r.id === roleId)
      if (index !== -1) {
        roles.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to assign permissions'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    roles,
    loading,
    error,
    fetchRoles,
    createRole,
    updateRole,
    deleteRole,
    assignPermissions,
  }
})
