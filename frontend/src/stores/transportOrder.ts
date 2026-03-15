import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import type {
  TransportOrder,
  DispatchSummary,
  CreateTransportOrderRequest,
  AssignDriverRequest,
  TransportActionRequest,
  AdhocPickupRequest,
} from '@/types'

export const useTransportOrderStore = defineStore('transportOrder', () => {
  const orders = ref<TransportOrder[]>([])
  const myAssignments = ref<TransportOrder[]>([])
  const dispatchSummary = ref<DispatchSummary | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const pendingOrders = computed(() =>
    orders.value.filter((o) => o.status === 'requested')
  )

  const assignedOrders = computed(() =>
    orders.value.filter((o) => o.status === 'assigned')
  )

  const inTransitOrders = computed(() =>
    orders.value.filter((o) => o.status === 'picked_up')
  )

  const completedOrders = computed(() =>
    orders.value.filter((o) => o.status === 'completed')
  )

  // Actions
  async function fetchOrders(filters?: Record<string, string>): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<{ success: boolean; data: TransportOrder[] }>(
        '/transport-orders',
        { params: filters }
      )
      orders.value = response.data.data || []
    } catch (e: any) {
      error.value = e?.response?.data?.message || 'Failed to load transport orders'
    } finally {
      loading.value = false
    }
  }

  async function fetchDispatchSummary(date?: string): Promise<void> {
    try {
      const response = await apiClient.get<{ success: boolean; data: DispatchSummary }>(
        '/transport-orders/dispatch-summary',
        { params: date ? { date } : {} }
      )
      dispatchSummary.value = response.data.data
    } catch (e: any) {
      console.error('Failed to load dispatch summary:', e)
    }
  }

  async function fetchMyAssignments(date?: string): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get<{ success: boolean; data: TransportOrder[] }>(
        '/transport-orders/my-assignments',
        { params: date ? { date } : {} }
      )
      myAssignments.value = response.data.data || []
    } catch (e: any) {
      error.value = e?.response?.data?.message || 'Failed to load assignments'
    } finally {
      loading.value = false
    }
  }

  async function createOrder(data: CreateTransportOrderRequest): Promise<TransportOrder> {
    const response = await apiClient.post<{ success: boolean; data: TransportOrder }>(
      '/transport-orders',
      data
    )
    await fetchOrders()
    return response.data.data
  }

  async function updateOrder(id: number, data: Partial<CreateTransportOrderRequest>): Promise<TransportOrder> {
    const response = await apiClient.put<{ success: boolean; data: TransportOrder }>(
      `/transport-orders/${id}`,
      data
    )
    await fetchOrders()
    return response.data.data
  }

  async function assignDriver(id: number, data: AssignDriverRequest): Promise<TransportOrder> {
    const response = await apiClient.post<{ success: boolean; data: TransportOrder }>(
      `/transport-orders/${id}/assign`,
      data
    )
    await fetchOrders()
    return response.data.data
  }

  async function executePickup(id: number, data: TransportActionRequest): Promise<TransportOrder> {
    const response = await apiClient.post<{ success: boolean; data: TransportOrder }>(
      `/transport-orders/${id}/pickup`,
      data
    )
    await fetchMyAssignments()
    return response.data.data
  }

  async function executeDropoff(id: number, data: TransportActionRequest): Promise<TransportOrder> {
    const response = await apiClient.post<{ success: boolean; data: TransportOrder }>(
      `/transport-orders/${id}/dropoff`,
      data
    )
    await fetchMyAssignments()
    return response.data.data
  }

  async function adhocPickup(data: AdhocPickupRequest): Promise<TransportOrder> {
    const response = await apiClient.post<{ success: boolean; data: TransportOrder }>(
      '/transport-orders/adhoc-pickup',
      data
    )
    await fetchMyAssignments()
    return response.data.data
  }

  async function cancelOrder(id: number, reason: string): Promise<TransportOrder> {
    const response = await apiClient.post<{ success: boolean; data: TransportOrder }>(
      `/transport-orders/${id}/cancel`,
      { reason }
    )
    await fetchOrders()
    return response.data.data
  }

  return {
    orders,
    myAssignments,
    dispatchSummary,
    loading,
    error,
    pendingOrders,
    assignedOrders,
    inTransitOrders,
    completedOrders,
    fetchOrders,
    fetchDispatchSummary,
    fetchMyAssignments,
    createOrder,
    updateOrder,
    assignDriver,
    executePickup,
    executeDropoff,
    adhocPickup,
    cancelOrder,
  }
})
