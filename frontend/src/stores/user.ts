import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import type { User, ApiResponse } from '@/types'

export const useUserStore = defineStore('user', () => {
  const users = ref<User[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const activeUsers = computed(() =>
    users.value.filter(u => u.is_active)
  )

  const availableUsers = computed(() =>
    users.value.filter(u => u.is_active && u.is_available)
  )

  async function fetchUsers(filters?: { is_active?: boolean; is_available?: boolean }) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<User[]>>('/users', { params: filters })
      users.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load users'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchAvailableUsers(date: string) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<User[]>>(`/users/available/${date}`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load available users'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createUser(data: Partial<User>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<User>>('/users', data)
      users.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create user'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateUser(id: number, data: Partial<User>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<User>>(`/users/${id}`, data)
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update user'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteUser(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/users/${id}`)
      users.value = users.value.filter(u => u.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete user'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function assignRoles(userId: number, roleIds: number[]) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<User>>(`/users/${userId}/roles`, {
        role_ids: roleIds,
      })
      const index = users.value.findIndex(u => u.id === userId)
      if (index !== -1) {
        users.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to assign roles'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    users,
    loading,
    error,
    activeUsers,
    availableUsers,
    fetchUsers,
    fetchAvailableUsers,
    createUser,
    updateUser,
    deleteUser,
    assignRoles,
  }
})
