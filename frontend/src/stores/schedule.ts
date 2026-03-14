import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import type { Schedule, ApiResponse } from '@/types'

export const useScheduleStore = defineStore('schedule', () => {
  const schedules = ref<Schedule[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const selectedDate = ref<string>(new Date().toISOString().split('T')[0])

  const schedulesForSelectedDate = computed(() =>
    schedules.value.filter(s => s.date === selectedDate.value)
  )

  const schedulesGroupedByProject = computed(() => {
    const grouped: Record<string, Schedule[]> = {}
    schedulesForSelectedDate.value.forEach(schedule => {
      const projectName = schedule.project?.name || 'Unknown Project'
      if (!grouped[projectName]) {
        grouped[projectName] = []
      }
      grouped[projectName].push(schedule)
    })
    return grouped
  })

  async function fetchSchedules(filters?: { date?: string; project_id?: number; status?: string }) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Schedule[]>>('/schedules', { params: filters })
      schedules.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load schedules'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchSchedule(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Schedule>>(`/schedules/${id}`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load schedule'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createSchedule(data: Partial<Schedule>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Schedule>>('/schedules', data)
      schedules.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create schedule'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateSchedule(id: number, data: Partial<Schedule>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<Schedule>>(`/schedules/${id}`, data)
      const index = schedules.value.findIndex(s => s.id === id)
      if (index !== -1) {
        schedules.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update schedule'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteSchedule(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/schedules/${id}`)
      schedules.value = schedules.value.filter(s => s.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete schedule'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function assignCrew(scheduleId: number, userIds: number[], foremanId?: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Schedule>>(`/schedules/${scheduleId}/crew`, {
        user_ids: userIds,
        foreman_id: foremanId,
      })
      const index = schedules.value.findIndex(s => s.id === scheduleId)
      if (index !== -1) {
        schedules.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to assign crew'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function assignEquipment(scheduleId: number, equipmentIds: number[]) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Schedule>>(`/schedules/${scheduleId}/equipment`, {
        equipment_ids: equipmentIds,
      })
      const index = schedules.value.findIndex(s => s.id === scheduleId)
      if (index !== -1) {
        schedules.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to assign equipment'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function duplicateSchedule(scheduleId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Schedule>>(`/schedules/${scheduleId}/duplicate`)
      schedules.value.push(response.data.data)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to duplicate schedule'
      throw err
    } finally {
      loading.value = false
    }
  }

  function setSelectedDate(date: string) {
    selectedDate.value = date
  }

  return {
    schedules,
    loading,
    error,
    selectedDate,
    schedulesForSelectedDate,
    schedulesGroupedByProject,
    fetchSchedules,
    fetchSchedule,
    createSchedule,
    updateSchedule,
    deleteSchedule,
    assignCrew,
    assignEquipment,
    duplicateSchedule,
    setSelectedDate,
  }
})
