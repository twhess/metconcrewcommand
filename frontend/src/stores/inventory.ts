import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import type {
  InventoryItem,
  InventoryLocation,
  InventoryStock,
  InventoryTransaction,
  LowStockAlert,
  OrderSuggestion,
  LocationInventory,
  ProjectUsageSummary,
  ApiResponse
} from '@/types'

export const useInventoryStore = defineStore('inventory', () => {
  const items = ref<InventoryItem[]>([])
  const locations = ref<InventoryLocation[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // CRUD operations for items
  async function fetchItems(filters?: { is_active?: boolean; type?: string }) {
    loading.value = true
    error.value = null
    try {
      const params = new URLSearchParams()
      if (filters?.is_active !== undefined) {
        params.append('is_active', filters.is_active.toString())
      }
      if (filters?.type) {
        params.append('type', filters.type)
      }

      const response = await apiClient.get<ApiResponse<InventoryItem[]>>(
        `/inventory?${params.toString()}`
      )
      items.value = response.data.data
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load inventory items'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchItem(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<InventoryItem>>(`/inventory/${id}`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load inventory item'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createItem(data: Partial<InventoryItem>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<InventoryItem>>('/inventory', data)
      items.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create inventory item'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateItem(id: number, data: Partial<InventoryItem>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put<ApiResponse<InventoryItem>>(`/inventory/${id}`, data)
      const index = items.value.findIndex(i => i.id === id)
      if (index !== -1) {
        items.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update inventory item'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteItem(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/inventory/${id}`)
      items.value = items.value.filter(i => i.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete inventory item'
      throw err
    } finally {
      loading.value = false
    }
  }

  // CRUD operations for locations
  async function fetchLocations(filters?: { is_active?: boolean }) {
    loading.value = true
    error.value = null
    try {
      const params = new URLSearchParams()
      if (filters?.is_active !== undefined) {
        params.append('is_active', filters.is_active.toString())
      }

      const response = await apiClient.get<ApiResponse<InventoryLocation[]>>(
        `/inventory-locations?${params.toString()}`
      )
      locations.value = response.data.data
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load locations'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Movement operations
  async function moveInventory(
    itemId: number,
    fromLocationId: number,
    toLocationId: number,
    quantity: number,
    notes?: string
  ) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<InventoryTransaction>>(
        `/inventory/${itemId}/move`,
        {
          from_location_id: fromLocationId,
          to_location_id: toLocationId,
          quantity,
          notes
        }
      )
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to move inventory'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function recordUsage(
    itemId: number,
    fromLocationId: number,
    projectId: number,
    quantity: number,
    notes?: string
  ) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<InventoryTransaction>>(
        `/inventory/${itemId}/usage`,
        {
          from_location_id: fromLocationId,
          project_id: projectId,
          quantity,
          notes
        }
      )
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to record usage'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function receiveInventory(
    itemId: number,
    locationId: number,
    quantity: number,
    notes?: string
  ) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<InventoryTransaction>>(
        '/inventory/receive',
        {
          inventory_item_id: itemId,
          location_id: locationId,
          quantity,
          notes
        }
      )
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to receive inventory'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function receiveBatch(
    locationId: number,
    items: Array<{ inventory_item_id: number; quantity: number; notes?: string }>,
    notes?: string
  ) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post<ApiResponse<InventoryTransaction[]>>(
        '/inventory/receive-batch',
        {
          location_id: locationId,
          items,
          notes
        }
      )
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to receive inventory batch'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Reports
  async function fetchLowStockAlerts(): Promise<LowStockAlert[]> {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<LowStockAlert[]>>('/inventory/low-stock')
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load low stock alerts'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchOrderSuggestions(): Promise<OrderSuggestion[]> {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<OrderSuggestion[]>>(
        '/inventory/order-suggestions'
      )
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load order suggestions'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchLocationInventory(locationId: number): Promise<LocationInventory> {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<LocationInventory>>(
        `/inventory/by-location/${locationId}`
      )
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load location inventory'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchProjectUsage(projectId: number): Promise<ProjectUsageSummary> {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<ApiResponse<ProjectUsageSummary>>(
        `/inventory/by-project/${projectId}`
      )
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to load project usage'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    locations,
    loading,
    error,
    fetchItems,
    fetchItem,
    createItem,
    updateItem,
    deleteItem,
    fetchLocations,
    moveInventory,
    recordUsage,
    receiveInventory,
    receiveBatch,
    fetchLowStockAlerts,
    fetchOrderSuggestions,
    fetchLocationInventory,
    fetchProjectUsage
  }
})
