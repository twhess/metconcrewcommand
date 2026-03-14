<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import type { InventoryItem } from '@/types'

const inventoryStore = useInventoryStore()

// Filters
const searchQuery = ref('')
const categoryFilter = ref<string>('all')
const typeFilter = ref<string>('all')
const lowStockOnly = ref(false)

// View mode
const viewMode = ref<'cards' | 'table'>('table')

// Dialogs
const showFormDialog = ref(false)
const showStockDialog = ref(false)
const selectedItem = ref<InventoryItem | null>(null)

// Low stock count
const lowStockCount = ref(0)

// Filtered items
const filteredItems = computed(() => {
  let items = inventoryStore.items

  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    items = items.filter(item =>
      item.name.toLowerCase().includes(query) ||
      item.sku?.toLowerCase().includes(query) ||
      item.barcode?.toLowerCase().includes(query) ||
      item.category?.toLowerCase().includes(query)
    )
  }

  // Category filter
  if (categoryFilter.value !== 'all') {
    items = items.filter(item => item.category === categoryFilter.value)
  }

  // Type filter
  if (typeFilter.value !== 'all') {
    items = items.filter(item => item.type === typeFilter.value)
  }

  // Low stock filter
  if (lowStockOnly.value) {
    items = items.filter(item => {
      if (!item.min_quantity) return false
      const totalStock = item.stock?.reduce((sum, s) => sum + s.quantity, 0) || 0
      return totalStock < item.min_quantity
    })
  }

  return items
})

// Unique categories
const categories = computed(() => {
  const cats = new Set<string>()
  inventoryStore.items.forEach(item => {
    if (item.category) cats.add(item.category)
  })
  return Array.from(cats).sort()
})

// Category options for filter
const categoryOptions = computed(() => [
  { label: 'All Categories', value: 'all' },
  ...categories.value.map(cat => ({ label: cat, value: cat }))
])

// Type options
const typeOptions = [
  { label: 'All Types', value: 'all' },
  { label: 'Trackable', value: 'trackable' },
  { label: 'Non-Trackable', value: 'non_trackable' }
]

// Calculate total stock for an item
function getTotalStock(item: InventoryItem): number {
  return item.stock?.reduce((sum, s) => sum + s.quantity, 0) || 0
}

// Check if item is low stock
function isLowStock(item: InventoryItem): boolean {
  if (!item.min_quantity) return false
  return getTotalStock(item) < item.min_quantity
}

// Get stock status color
function getStockColor(item: InventoryItem): string {
  const totalStock = getTotalStock(item)
  if (!item.min_quantity) return 'grey'
  if (totalStock === 0) return 'red'
  if (totalStock < item.min_quantity) return 'orange'
  if (item.max_quantity && totalStock >= item.max_quantity) return 'blue'
  return 'green'
}

// Get stock status label
function getStockLabel(item: InventoryItem): string {
  const totalStock = getTotalStock(item)
  if (totalStock === 0) return 'Out of Stock'
  if (!item.min_quantity) return 'OK'
  if (totalStock < item.min_quantity) return 'Low Stock'
  if (item.max_quantity && totalStock >= item.max_quantity) return 'Max Stock'
  return 'In Stock'
}

// Actions
function openCreateDialog(): void {
  selectedItem.value = null
  showFormDialog.value = true
}

function openEditDialog(item: InventoryItem): void {
  selectedItem.value = item
  showFormDialog.value = true
}

function openStockDialog(item: InventoryItem): void {
  selectedItem.value = item
  showStockDialog.value = true
}

async function handleDelete(item: InventoryItem): Promise<void> {
  // TODO: Add confirmation dialog
  try {
    await inventoryStore.deleteItem(item.id)
  } catch (error) {
    console.error('Failed to delete item:', error)
  }
}

function handleItemSaved(): void {
  inventoryStore.fetchItems()
  showFormDialog.value = false
}

// Count low stock items
function updateLowStockCount(): void {
  lowStockCount.value = inventoryStore.items.filter(item => isLowStock(item)).length
}

// Load data
onMounted(async () => {
  await inventoryStore.fetchItems()
  updateLowStockCount()
})
</script>

<template>
  <q-page padding>
    <!-- Low Stock Alert Banner -->
    <q-banner
      v-if="lowStockCount > 0"
      class="bg-orange-2 text-orange-9 q-mb-md"
      rounded
    >
      <template #avatar>
        <q-icon name="warning" color="orange" />
      </template>
      <div class="text-weight-medium">
        {{ lowStockCount }} item{{ lowStockCount !== 1 ? 's' : '' }} below minimum stock level
      </div>
      <template #action>
        <q-btn
          flat
          label="Show Low Stock"
          color="orange-9"
          @click="lowStockOnly = true"
        />
      </template>
    </q-banner>

    <!-- Header -->
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h4">Inventory</div>
        <div class="text-subtitle2 text-grey-7">
          {{ filteredItems.length}} item{{ filteredItems.length !== 1 ? 's' : '' }}
        </div>
      </div>
      <div class="col-auto">
        <q-btn
          color="primary"
          icon="add"
          label="New Item"
          @click="openCreateDialog"
        />
      </div>
    </div>

    <!-- Filters -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-sm-6 col-md-4">
            <q-input
              v-model="searchQuery"
              outlined
              dense
              placeholder="Search items..."
              clearable
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="categoryFilter"
              :options="categoryOptions"
              outlined
              dense
              label="Category"
              option-label="label"
              option-value="value"
              emit-value
              map-options
            />
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="typeFilter"
              :options="typeOptions"
              outlined
              dense
              label="Type"
              option-label="label"
              option-value="value"
              emit-value
              map-options
            />
          </div>

          <div class="col-12 col-sm-6 col-md-2">
            <q-checkbox
              v-model="lowStockOnly"
              label="Low Stock Only"
              dense
            />
          </div>
        </div>

        <div class="row q-mt-sm">
          <div class="col">
            <q-btn-toggle
              v-model="viewMode"
              :options="[
                { label: 'Cards', value: 'cards', icon: 'view_module' },
                { label: 'Table', value: 'table', icon: 'view_list' }
              ]"
              outline
              dense
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Loading State -->
    <div v-if="inventoryStore.loading" class="row justify-center q-mt-lg">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Cards View -->
    <div v-else-if="viewMode === 'cards'" class="row q-col-gutter-md">
      <div
        v-for="item in filteredItems"
        :key="item.id"
        class="col-12 col-sm-6 col-md-4"
      >
        <q-card flat bordered class="cursor-pointer hover-shadow" @click="openStockDialog(item)">
          <q-card-section>
            <div class="row items-start q-mb-sm">
              <div class="col">
                <div class="text-h6">{{ item.name }}</div>
                <div v-if="item.sku" class="text-caption text-grey-7">SKU: {{ item.sku }}</div>
              </div>
              <div class="col-auto">
                <q-badge :color="getStockColor(item)" :label="getStockLabel(item)" />
              </div>
            </div>

            <div v-if="item.category" class="text-body2 q-mb-sm">
              <q-icon name="category" size="xs" class="q-mr-xs" />
              {{ item.category }}
            </div>

            <div class="text-body2 q-mb-sm">
              <q-icon name="inventory_2" size="xs" class="q-mr-xs" />
              Stock: {{ getTotalStock(item) }} {{ item.unit || 'units' }}
            </div>

            <div v-if="item.min_quantity" class="text-caption text-grey-7">
              Min: {{ item.min_quantity }} / Max: {{ item.max_quantity || 'N/A' }}
            </div>
          </q-card-section>

          <q-separator />

          <q-card-actions>
            <q-btn flat dense color="primary" label="View Stock" @click.stop="openStockDialog(item)" />
            <q-btn flat dense color="primary" label="Edit" @click.stop="openEditDialog(item)" />
            <q-space />
            <q-btn flat dense color="negative" icon="delete" @click.stop="handleDelete(item)" />
          </q-card-actions>
        </q-card>
      </div>

      <!-- Empty State -->
      <div v-if="filteredItems.length === 0" class="col-12">
        <q-card flat bordered>
          <q-card-section class="text-center q-pa-lg">
            <q-icon name="inbox" size="4em" color="grey-5" />
            <div class="text-h6 q-mt-md">No items found</div>
            <div class="text-grey-7 q-mt-sm">
              {{ searchQuery || categoryFilter !== 'all' || typeFilter !== 'all'
                ? 'Try adjusting your filters'
                : 'Create your first inventory item to get started'
              }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Table View -->
    <q-table
      v-else
      :rows="filteredItems"
      :columns="[
        { name: 'name', label: 'Name', field: 'name', align: 'left', sortable: true },
        { name: 'sku', label: 'SKU', field: 'sku', align: 'left', sortable: true },
        { name: 'category', label: 'Category', field: 'category', align: 'left', sortable: true },
        { name: 'type', label: 'Type', field: 'type', align: 'left', sortable: true },
        { name: 'stock', label: 'Total Stock', field: (row) => getTotalStock(row), align: 'center', sortable: true },
        { name: 'unit', label: 'Unit', field: 'unit', align: 'center' },
        { name: 'min_max', label: 'Min / Max', align: 'center' },
        { name: 'status', label: 'Status', align: 'center', sortable: true },
        { name: 'actions', label: 'Actions', align: 'right' }
      ]"
      row-key="id"
      flat
      bordered
      :rows-per-page-options="[10, 25, 50, 100]"
    >
      <template #body-cell-type="props">
        <q-td :props="props">
          <q-badge
            :color="props.row.type === 'trackable' ? 'blue' : 'grey'"
            :label="props.row.type === 'trackable' ? 'Trackable' : 'Non-Trackable'"
          />
        </q-td>
      </template>

      <template #body-cell-stock="props">
        <q-td :props="props">
          <span class="text-weight-medium">{{ getTotalStock(props.row) }}</span>
        </q-td>
      </template>

      <template #body-cell-min_max="props">
        <q-td :props="props">
          <span v-if="props.row.min_quantity">
            {{ props.row.min_quantity }} / {{ props.row.max_quantity || 'N/A' }}
          </span>
          <span v-else class="text-grey-7">—</span>
        </q-td>
      </template>

      <template #body-cell-status="props">
        <q-td :props="props">
          <q-badge :color="getStockColor(props.row)" :label="getStockLabel(props.row)" />
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn flat dense round icon="inventory_2" color="primary" @click="openStockDialog(props.row)">
            <q-tooltip>View Stock</q-tooltip>
          </q-btn>
          <q-btn flat dense round icon="edit" color="primary" @click="openEditDialog(props.row)">
            <q-tooltip>Edit</q-tooltip>
          </q-btn>
          <q-btn flat dense round icon="delete" color="negative" @click="handleDelete(props.row)">
            <q-tooltip>Delete</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>

    <!-- TODO: Add InventoryItemFormDialog -->
    <!-- TODO: Add StockLevelsDialog -->
  </q-page>
</template>

<style scoped>
.hover-shadow {
  transition: box-shadow 0.3s ease;
}

.hover-shadow:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
