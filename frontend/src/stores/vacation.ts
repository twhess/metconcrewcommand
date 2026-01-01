import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import type { Vacation, ApiResponse } from '@/types'

export const useVacationStore = defineStore('vacation', () => {
  const vacations = ref<Vacation[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchVacations(filters?: { user_id?: number; approved?: boolean; type?: string }) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Vacation[]>>('/vacations', { params: filters })
      vacations.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load vacations'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createVacation(data: Partial<Vacation>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Vacation>>('/vacations', data)
      vacations.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create vacation'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateVacation(id: number, data: Partial<Vacation>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<Vacation>>(`/vacations/${id}`, data)
      const index = vacations.value.findIndex(v => v.id === id)
      if (index !== -1) {
        vacations.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update vacation'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteVacation(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/vacations/${id}`)
      vacations.value = vacations.value.filter(v => v.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete vacation'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function approveVacation(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Vacation>>(`/vacations/${id}/approve`)
      const index = vacations.value.findIndex(v => v.id === id)
      if (index !== -1) {
        vacations.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to approve vacation'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    vacations,
    loading,
    error,
    fetchVacations,
    createVacation,
    updateVacation,
    deleteVacation,
    approveVacation,
  }
})
