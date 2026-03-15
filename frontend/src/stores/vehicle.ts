import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import type { Vehicle, VehicleMovement, MileageLog, ApiResponse } from '@/types'

export const useVehicleStore = defineStore('vehicle', () => {
  const vehicles = ref<Vehicle[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const activeVehicles = computed(() =>
    vehicles.value.filter(v => v.status === 'active')
  )

  const vehiclesInMaintenance = computed(() =>
    vehicles.value.filter(v => v.status === 'maintenance')
  )

  async function fetchVehicles(filters?: { status?: string; vehicle_type?: string; fuel_type?: string; search?: string }) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Vehicle[]>>('/vehicles', { params: filters })
      vehicles.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load vehicles'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createVehicle(data: Partial<Vehicle>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Vehicle>>('/vehicles', data)
      vehicles.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create vehicle'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateVehicle(id: number, data: Partial<Vehicle>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<Vehicle>>(`/vehicles/${id}`, data)
      const index = vehicles.value.findIndex(v => v.id === id)
      if (index !== -1) {
        vehicles.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update vehicle'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteVehicle(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/vehicles/${id}`)
      vehicles.value = vehicles.value.filter(v => v.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete vehicle'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function moveVehicle(id: number, data: { movement_type: string; to_location_type: string; to_location_id: number | null; odometer_reading?: number; notes?: string }) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post(`/vehicles/${id}/move`, data)
      await fetchVehicles()
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to move vehicle'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateOdometer(id: number, odometerReading: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post(`/vehicles/${id}/update-odometer`, {
        odometer_reading: odometerReading,
      })
      const index = vehicles.value.findIndex(v => v.id === id)
      if (index !== -1) {
        vehicles.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update odometer'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function assignOperator(id: number, userId: number | null) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post(`/vehicles/${id}/assign-operator`, {
        user_id: userId,
      })
      const index = vehicles.value.findIndex(v => v.id === id)
      if (index !== -1) {
        vehicles.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to assign operator'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchVehicleHistory(id: number): Promise<VehicleMovement[]> {
    try {
      const response = await apiClient.get<ApiResponse<VehicleMovement[]>>(`/vehicles/${id}/history`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load vehicle history'
      throw err
    }
  }

  async function fetchMileageLogs(vehicleId: number, filters?: { start_date?: string; end_date?: string; trip_type?: string }): Promise<MileageLog[]> {
    try {
      const response = await apiClient.get<ApiResponse<MileageLog[]>>(`/vehicles/${vehicleId}/mileage`, { params: filters })
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load mileage logs'
      throw err
    }
  }

  async function createMileageLog(vehicleId: number, data: Partial<MileageLog>) {
    try {
      const response = await apiClient.post<ApiResponse<MileageLog>>(`/vehicles/${vehicleId}/mileage`, data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create mileage log'
      throw err
    }
  }

  async function updateMileageLog(logId: number, data: Partial<MileageLog>) {
    try {
      const response = await apiClient.put<ApiResponse<MileageLog>>(`/mileage-logs/${logId}`, data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update mileage log'
      throw err
    }
  }

  async function deleteMileageLog(logId: number) {
    try {
      await apiClient.delete(`/mileage-logs/${logId}`)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete mileage log'
      throw err
    }
  }

  return {
    vehicles,
    loading,
    error,
    activeVehicles,
    vehiclesInMaintenance,
    fetchVehicles,
    createVehicle,
    updateVehicle,
    deleteVehicle,
    moveVehicle,
    updateOdometer,
    assignOperator,
    fetchVehicleHistory,
    fetchMileageLogs,
    createMileageLog,
    updateMileageLog,
    deleteMileageLog,
  }
})
