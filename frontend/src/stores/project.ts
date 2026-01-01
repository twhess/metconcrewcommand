import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import type { Project, ApiResponse } from '@/types'

export const useProjectStore = defineStore('project', () => {
  const projects = ref<Project[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const activeProjects = computed(() =>
    projects.value.filter(p => p.status === 'active')
  )

  const inactiveProjects = computed(() =>
    projects.value.filter(p => p.status === 'inactive')
  )

  const completedProjects = computed(() =>
    projects.value.filter(p => p.status === 'completed')
  )

  async function fetchProjects() {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Project[]>>('/projects')
      projects.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load projects'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchProject(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Project>>(`/projects/${id}`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load project'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createProject(data: Partial<Project>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Project>>('/projects', data)
      projects.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create project'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateProject(id: number, data: Partial<Project>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<Project>>(`/projects/${id}`, data)
      const index = projects.value.findIndex(p => p.id === id)
      if (index !== -1) {
        projects.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update project'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteProject(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/projects/${id}`)
      projects.value = projects.value.filter(p => p.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete project'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    projects,
    loading,
    error,
    activeProjects,
    inactiveProjects,
    completedProjects,
    fetchProjects,
    fetchProject,
    createProject,
    updateProject,
    deleteProject,
  }
})
