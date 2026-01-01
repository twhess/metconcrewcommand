import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import type { Equipment, ApiResponse } from '@/types'

export const useEquipmentStore = defineStore('equipment', () => {
  const equipment = ref<Equipment[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const activeEquipment = computed(() =>
    equipment.value.filter(e => e.status === 'active')
  )

  const trackableEquipment = computed(() =>
    equipment.value.filter(e => e.type === 'trackable')
  )

  async function fetchEquipment(filters?: { status?: string; type?: string }) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Equipment[]>>('/equipment', { params: filters })
      equipment.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load equipment'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchAvailableEquipment(date: string) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Equipment[]>>(`/equipment/available/${date}`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load available equipment'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createEquipment(data: Partial<Equipment>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Equipment>>('/equipment', data)
      equipment.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create equipment'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateEquipment(id: number, data: Partial<Equipment>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<Equipment>>(`/equipment/${id}`, data)
      const index = equipment.value.findIndex(e => e.id === id)
      if (index !== -1) {
        equipment.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update equipment'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteEquipment(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/equipment/${id}`)
      equipment.value = equipment.value.filter(e => e.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete equipment'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function moveEquipment(id: number, locationType: string, locationId: number | null, notes?: string) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post(`/equipment/${id}/move`, {
        location_type: locationType,
        location_id: locationId,
        notes,
      })
      await fetchEquipment()
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to move equipment'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    equipment,
    loading,
    error,
    activeEquipment,
    trackableEquipment,
    fetchEquipment,
    fetchAvailableEquipment,
    createEquipment,
    updateEquipment,
    deleteEquipment,
    moveEquipment,
  }
})
