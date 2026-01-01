import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import type { CompanyLocation, ApiResponse } from '@/types'

export const useCompanyLocationStore = defineStore('companyLocation', () => {
  const locations = ref<CompanyLocation[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchLocations(companyId?: number) {
    loading.value = true
    error.value = null
    try {
      const params = companyId ? { company_id: companyId } : {}
      const response = await apiClient.get<ApiResponse<CompanyLocation[]>>('/company-locations', { params })
      locations.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load locations'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createLocation(data: Partial<CompanyLocation>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<CompanyLocation>>('/company-locations', data)
      locations.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create location'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateLocation(id: number, data: Partial<CompanyLocation>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<CompanyLocation>>(`/company-locations/${id}`, data)
      const index = locations.value.findIndex(l => l.id === id)
      if (index !== -1) {
        locations.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update location'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteLocation(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/company-locations/${id}`)
      locations.value = locations.value.filter(l => l.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete location'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    locations,
    loading,
    error,
    fetchLocations,
    createLocation,
    updateLocation,
    deleteLocation,
  }
})
