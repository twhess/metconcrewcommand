import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import type { SpecificationTemplate, ApiResponse } from '@/types'

export const useSpecificationTemplateStore = defineStore('specificationTemplate', () => {
  const templates = ref<SpecificationTemplate[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const activeTemplates = computed(() =>
    templates.value.filter(t => t.is_active)
  )

  const templatesByProjectType = computed(() => {
    const grouped: Record<string, SpecificationTemplate[]> = {}
    templates.value.forEach(template => {
      if (!grouped[template.project_type]) {
        grouped[template.project_type] = []
      }
      grouped[template.project_type].push(template)
    })
    return grouped
  })

  async function fetchTemplates(projectType?: string) {
    loading.value = true
    error.value = null
    try {
      const url = projectType
        ? `/specification-templates?project_type=${projectType}`
        : '/specification-templates'
      const response = await apiClient.get<ApiResponse<SpecificationTemplate[]>>(url)
      templates.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load specification templates'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchTemplate(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<SpecificationTemplate>>(`/specification-templates/${id}`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load template'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createTemplate(data: Partial<SpecificationTemplate>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<SpecificationTemplate>>('/specification-templates', data)
      templates.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create template'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateTemplate(id: number, data: Partial<SpecificationTemplate>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<SpecificationTemplate>>(`/specification-templates/${id}`, data)
      const index = templates.value.findIndex(t => t.id === id)
      if (index !== -1) {
        templates.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update template'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteTemplate(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/specification-templates/${id}`)
      templates.value = templates.value.filter(t => t.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete template'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function duplicateTemplate(id: number, newName: string, newSlug: string) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<SpecificationTemplate>>(
        `/specification-templates/${id}/duplicate`,
        { name: newName, slug: newSlug }
      )
      templates.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to duplicate template'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function applyToProject(templateId: number, projectId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<any>>(
        `/specification-templates/${templateId}/apply-to-project`,
        { project_id: projectId }
      )
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to apply template'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    templates,
    loading,
    error,
    activeTemplates,
    templatesByProjectType,
    fetchTemplates,
    fetchTemplate,
    createTemplate,
    updateTemplate,
    deleteTemplate,
    duplicateTemplate,
    applyToProject,
  }
})
