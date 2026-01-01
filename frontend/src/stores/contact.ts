import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import type { Contact, ApiResponse } from '@/types'

export const useContactStore = defineStore('contact', () => {
  const contacts = ref<Contact[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchContacts(companyId?: number) {
    loading.value = true
    error.value = null
    try {
      const params = companyId ? { company_id: companyId } : {}
      const response = await apiClient.get<ApiResponse<Contact[]>>('/contacts', { params })
      contacts.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load contacts'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createContact(data: Partial<Contact>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<Contact>>('/contacts', data)
      contacts.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create contact'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateContact(id: number, data: Partial<Contact>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<Contact>>(`/contacts/${id}`, data)
      const index = contacts.value.findIndex(c => c.id === id)
      if (index !== -1) {
        contacts.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update contact'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteContact(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/contacts/${id}`)
      contacts.value = contacts.value.filter(c => c.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete contact'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    contacts,
    loading,
    error,
    fetchContacts,
    createContact,
    updateContact,
    deleteContact,
  }
})
