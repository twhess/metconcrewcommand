<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import type { LowStockAlert, OrderSuggestion } from '@/types'
import { useRouter } from 'vue-router'

const inventoryStore = useInventoryStore()
const router = useRouter()

// Data
const lowStockAlerts = ref<LowStockAlert[]>([])
const orderSuggestions = ref<OrderSuggestion[]>([])
const loading = ref(false)

// Quick stats
const totalItems = ref(0)
const itemsBelowMin = ref(0)

// Load dashboard data
async function loadDashboard(): Promise<void> {
  loading.value = true
  try {
    const [alerts, suggestions] = await Promise.all([
      inventoryStore.fetchLowStockAlerts(),
      inventoryStore.fetchOrderSuggestions()
    ])

    lowStockAlerts.value = alerts
    orderSuggestions.value = suggestions
    itemsBelowMin.value = alerts.length

    // Get total items count
    await inventoryStore.fetchItems({ is_active: true })
    totalItems.value = inventoryStore.items.length
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  } finally {
    loading.value = false
  }
}

// Navigate to inventory page with low stock filter
function showLowStockItems(): void {
  router.push('/inventory?lowStock=true')
}

// Export order suggestions to CSV
function exportOrderSuggestions(): void {
  if (orderSuggestions.value.length === 0) return

  const headers = ['Item Name', 'SKU', 'Current Stock', 'Min', 'Max', 'Suggested Order Qty']
  const rows = orderSuggestions.value.map(s => [
    s.item.name,
    s.item.sku || '',
    s.current_stock,
    s.min_quantity,
    s.max_quantity,
    s.suggested_order_qty
  ])

  const csvContent = [
    headers.join(','),
    ...rows.map(row => row.join(','))
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `order-suggestions-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
  window.URL.revokeObjectURL(url)
}

// Load data
onMounted(() => {
  loadDashboard()
})
</script>

<template>
  <q-page padding>
    <!-- Header -->
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h4">Inventory Dashboard</div>
        <div class="text-subtitle2 text-grey-7">
          Overview of inventory status and ordering needs
        </div>
      </div>
      <div class="col-auto">
        <q-btn
          flat
          icon="refresh"
          label="Refresh"
          @click="loadDashboard"
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="row justify-center q-mt-lg">
      <q-spinner color="primary" size="3em" />
    </div>

    <template v-else>
      <!-- Quick Stats -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-h4 text-primary">{{ totalItems }}</div>
              <div class="text-grey-7">Total Items</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="cursor-pointer" @click="showLowStockItems">
            <q-card-section>
              <div class="text-h4 text-orange">{{ itemsBelowMin }}</div>
              <div class="text-grey-7">Items Below Minimum</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-h4 text-blue">{{ orderSuggestions.length }}</div>
              <div class="text-grey-7">Items to Order</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-h4 text-green">
                {{ orderSuggestions.reduce((sum, s) => sum + s.suggested_order_qty, 0).toFixed(0) }}
              </div>
              <div class="text-grey-7">Total Units to Order</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Low Stock Alerts -->
      <q-card flat bordered class="q-mb-md">
        <q-card-section>
          <div class="row items-center q-mb-md">
            <div class="col">
              <div class="text-h6">Low Stock Alerts</div>
              <div class="text-caption text-grey-7">
                Items currently below minimum stock level
              </div>
            </div>
            <div class="col-auto">
              <q-badge color="orange" :label="`${lowStockAlerts.length} items`" />
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="lowStockAlerts.length === 0" class="text-center q-pa-md">
            <q-icon name="check_circle" size="3em" color="green" />
            <div class="text-h6 q-mt-md text-green">All items adequately stocked</div>
            <div class="text-grey-7">No items below minimum threshold</div>
          </div>

          <!-- Alert Cards -->
          <div v-else class="row q-col-gutter-md">
            <div
              v-for="alert in lowStockAlerts"
              :key="alert.item.id"
              class="col-12 col-sm-6 col-md-4"
            >
              <q-card flat bordered class="bg-orange-1">
                <q-card-section>
                  <div class="text-weight-medium">{{ alert.item.name }}</div>
                  <div v-if="alert.item.sku" class="text-caption text-grey-7">
                    SKU: {{ alert.item.sku }}
                  </div>

                  <div class="q-mt-sm">
                    <div class="text-caption text-grey-7">Current Stock</div>
                    <div class="text-h6 text-orange">{{ alert.total_stock }}</div>
                  </div>

                  <div class="q-mt-sm">
                    <div class="text-caption text-grey-7">Minimum Required</div>
                    <div class="text-body2">{{ alert.min_quantity }}</div>
                  </div>

                  <!-- Locations Breakdown -->
                  <div v-if="alert.locations.length > 0" class="q-mt-sm">
                    <div class="text-caption text-grey-7 q-mb-xs">Locations:</div>
                    <div
                      v-for="loc in alert.locations"
                      :key="loc.location.id"
                      class="text-caption"
                    >
                      {{ loc.location.name }}: {{ loc.quantity }}
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Order Suggestions -->
      <q-card flat bordered>
        <q-card-section>
          <div class="row items-center q-mb-md">
            <div class="col">
              <div class="text-h6">Order Suggestions</div>
              <div class="text-caption text-grey-7">
                Recommended quantities to order based on min/max thresholds
              </div>
            </div>
            <div class="col-auto">
              <q-btn
                v-if="orderSuggestions.length > 0"
                flat
                dense
                icon="download"
                label="Export CSV"
                color="primary"
                @click="exportOrderSuggestions"
              />
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="orderSuggestions.length === 0" class="text-center q-pa-md">
            <q-icon name="shopping_cart" size="3em" color="grey-5" />
            <div class="text-h6 q-mt-md">No orders needed</div>
            <div class="text-grey-7">All items are within acceptable stock levels</div>
          </div>

          <!-- Order Suggestions Table -->
          <q-table
            v-else
            :rows="orderSuggestions"
            :columns="[
              { name: 'name', label: 'Item', field: (row) => row.item.name, align: 'left', sortable: true },
              { name: 'sku', label: 'SKU', field: (row) => row.item.sku, align: 'left' },
              { name: 'category', label: 'Category', field: (row) => row.item.category, align: 'left', sortable: true },
              { name: 'current', label: 'Current Stock', field: 'current_stock', align: 'center', sortable: true },
              { name: 'min', label: 'Min', field: 'min_quantity', align: 'center', sortable: true },
              { name: 'max', label: 'Max', field: 'max_quantity', align: 'center', sortable: true },
              { name: 'order', label: 'Order Quantity', field: 'suggested_order_qty', align: 'center', sortable: true }
            ]"
            row-key="item.id"
            flat
            :rows-per-page-options="[10, 25, 50]"
          >
            <template #body-cell-current="props">
              <q-td :props="props">
                <span class="text-orange text-weight-medium">{{ props.row.current_stock }}</span>
              </q-td>
            </template>

            <template #body-cell-order="props">
              <q-td :props="props">
                <q-badge color="blue" :label="props.row.suggested_order_qty.toString()" />
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </template>
  </q-page>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
  transition: background-color 0.2s;
}

.cursor-pointer:hover {
  background-color: rgba(0, 0, 0, 0.03);
}
</style>
