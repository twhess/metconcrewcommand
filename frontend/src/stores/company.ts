import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import type { Company, ApiResponse } from '@/types'

export const useCompanyStore = defineStore('company', () => {
  const companies = ref<Company[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const activeCompanies = computed(() =>
    companies.value.filter(c => c.is_active)
  )

  const companiesByType = computed(() => {
    const grouped: Record<string, Company[]> = {}
    companies.value.forEach(company => {
      if (!grouped[company.type]) {
        grouped[company.type] = []
      }
      grouped[company.type].push(company)
    })
    return grouped
  })

  const customers = computed(() =>
    companies.value.filter(c => c.type === 'customer')
  )

  const vendors = computed(() =>
    companies.value.filter(c => c.type === 'vendor')
  )

  async function fetchCompanies() {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Company[]>>('/companies')
      companies.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load companies'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchCompany(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<Company>>(`/companies/${id}`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load company'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createCompany(data: Partial<Company>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Company>>('/companies', data)
      companies.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create company'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateCompany(id: number, data: Partial<Company>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<Company>>(`/companies/${id}`, data)
      const index = companies.value.findIndex(c => c.id === id)
      if (index !== -1) {
        companies.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update company'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteCompany(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/companies/${id}`)
      companies.value = companies.value.filter(c => c.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete company'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    companies,
    loading,
    error,
    activeCompanies,
    companiesByType,
    customers,
    vendors,
    fetchCompanies,
    fetchCompany,
    createCompany,
    updateCompany,
    deleteCompany,
  }
})
