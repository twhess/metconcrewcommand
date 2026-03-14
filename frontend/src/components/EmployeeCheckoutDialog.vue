<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import { useAuthStore } from '@/stores/auth'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import type { InventoryItem, Project, ApiResponse } from '@/types'

const props = defineProps<{
  modelValue: boolean
  projectId?: number
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'checked-out': []
}>()

const $q = useQuasar()
const inventoryStore = useInventoryStore()
const authStore = useAuthStore()

// Form state
const inputMode = ref<'scan' | 'dropdown'>('scan')
const scannedCode = ref('')
const selectedItemId = ref<number | null>(null)
const selectedItem = ref<InventoryItem | null>(null)
const quantity = ref(1)
const selectedProjectId = ref<number | null>(props.projectId || null)
const notes = ref('')
const loading = ref(false)

// Available items for dropdown
const availableItems = ref<InventoryItem[]>([])
const projects = ref<Project[]>([])

// Default shop location (from localStorage or first warehouse)
const defaultShopLocationId = ref<number>(
  parseInt(localStorage.getItem('employee_default_shop_location_id') || '1')
)

// Computed item options for dropdown
const itemOptions = computed(() =>
  availableItems.value.map(item => {
    const stockAtLocation = item.stock?.find(
      s => s.inventory_location_id === defaultShopLocationId.value
    )
    return {
      label: item.name,
      value: item.id,
      sku: item.sku,
      unit: item.unit || 'units',
      stock: stockAtLocation?.quantity || 0,
      category: item.category
    }
  }).sort((a, b) => {
    // Group by category, then alphabetically
    if (a.category !== b.category) {
      return (a.category || '').localeCompare(b.category || '')
    }
    return a.label.localeCompare(b.label)
  })
)

// Project options
const projectOptions = computed(() =>
  projects.value.map(project => ({
    label: project.name,
    value: project.id,
    sublabel: project.project_number || ''
  }))
)

// Available stock at default location
const availableStock = computed(() => {
  if (!selectedItem.value?.stock) return null
  const stock = selectedItem.value.stock.find(
    s => s.inventory_location_id === defaultShopLocationId.value
  )
  return stock?.quantity || 0
})

// Toggle input mode
function toggleInputMode(): void {
  // Reset selection when switching modes
  scannedCode.value = ''
  selectedItemId.value = null
  selectedItem.value = null
}

// Load all items for dropdown
async function loadItems(): Promise<void> {
  try {
    const items = await inventoryStore.fetchItems({ is_active: true })
    availableItems.value = items
  } catch (error) {
    console.error('Failed to load items:', error)
  }
}

// Load projects
async function loadProjects(): Promise<void> {
  try {
    const response = await apiClient.get<ApiResponse<Project[]>>('/projects')
    projects.value = response.data.data
  } catch (error) {
    console.error('Failed to load projects:', error)
  }
}

// Scan for item
async function handleScan(): Promise<void> {
  if (!scannedCode.value.trim()) return

  loading.value = true
  try {
    const response = await apiClient.get<ApiResponse<InventoryItem>>(`/inventory/scan/${scannedCode.value}`)
    selectedItem.value = response.data.data

    $q.notify({
      type: 'positive',
      message: `Found: ${selectedItem.value.name}`,
      position: 'top',
      timeout: 1500
    })

    // Auto-focus quantity input
    nextTick(() => {
      const quantityInput = document.querySelector<HTMLInputElement>('input[type="number"]')
      quantityInput?.focus()
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Item not found. Try scanning again.',
      position: 'top'
    })
    selectedItem.value = null
  } finally {
    loading.value = false
  }
}

// Handle dropdown selection
function handleDropdownSelect(): void {
  if (!selectedItemId.value) return

  const item = availableItems.value.find(i => i.id === selectedItemId.value)
  if (item) {
    selectedItem.value = item

    $q.notify({
      type: 'positive',
      message: `Selected: ${item.name}`,
      position: 'top',
      timeout: 1500
    })

    // Auto-focus quantity input
    nextTick(() => {
      const quantityInput = document.querySelector<HTMLInputElement>('input[type="number"]')
      quantityInput?.focus()
    })
  }
}

// Submit checkout
async function handleCheckout(): Promise<void> {
  if (!selectedItem.value || !selectedProjectId.value) return

  // Validate quantity
  if (quantity.value <= 0) {
    $q.notify({
      type: 'negative',
      message: 'Quantity must be greater than 0',
      position: 'top'
    })
    return
  }

  if (availableStock.value !== null && quantity.value > availableStock.value) {
    $q.notify({
      type: 'negative',
      message: `Insufficient stock. Only ${availableStock.value} available.`,
      position: 'top'
    })
    return
  }

  loading.value = true
  try {
    await inventoryStore.recordUsage(
      selectedItem.value.id,
      defaultShopLocationId.value,
      selectedProjectId.value,
      quantity.value,
      notes.value || undefined
    )

    $q.notify({
      type: 'positive',
      message: 'Material checked out successfully',
      position: 'top'
    })

    emit('checked-out')
    resetForm()
  } catch (error) {
    console.error('Checkout error:', error)
    $q.notify({
      type: 'negative',
      message: 'Failed to check out material',
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

function resetForm(): void {
  scannedCode.value = ''
  selectedItemId.value = null
  selectedItem.value = null
  quantity.value = 1
  notes.value = ''
  // Keep project and input mode selected for next checkout
}

function closeDialog(): void {
  emit('update:modelValue', false)
  setTimeout(resetForm, 300)
}

// Load items on mount if dropdown mode
watch(inputMode, (newMode) => {
  if (newMode === 'dropdown' && availableItems.value.length === 0) {
    loadItems()
  }
})

onMounted(() => {
  // Pre-load items and projects
  loadItems()
  loadProjects()
})
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="min-width: 350px; max-width: 500px">
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">Check Out Shop Material</div>
        <div class="text-caption">Scan QR code or select item</div>
      </q-card-section>

      <q-card-section>
        <!-- Mode Toggle -->
        <div class="row items-center q-mb-md">
          <div class="col">
            <q-btn-toggle
              v-model="inputMode"
              toggle-color="primary"
              :options="[
                { label: 'Scan Code', value: 'scan', icon: 'qr_code_scanner' },
                { label: 'Select Item', value: 'dropdown', icon: 'list' }
              ]"
              @update:model-value="toggleInputMode"
            />
          </div>
        </div>

        <!-- SCAN MODE: QR/Barcode Scanner Input -->
        <q-input
          v-if="inputMode === 'scan'"
          v-model="scannedCode"
          label="Scan or Enter Code"
          outlined
          dense
          autofocus
          @keyup.enter="handleScan"
        >
          <template #prepend>
            <q-icon name="qr_code_scanner" />
          </template>
          <template #append>
            <q-btn
              flat
              dense
              icon="search"
              @click="handleScan"
              :loading="loading"
            />
          </template>
          <template #hint>
            Scan QR code, barcode, or enter SKU
          </template>
        </q-input>

        <!-- DROPDOWN MODE: Item Selector -->
        <q-select
          v-if="inputMode === 'dropdown'"
          v-model="selectedItemId"
          :options="itemOptions"
          label="Select Item"
          outlined
          dense
          emit-value
          map-options
          use-input
          input-debounce="300"
          @update:model-value="handleDropdownSelect"
        >
          <template #prepend>
            <q-icon name="inventory_2" />
          </template>
          <template #option="scope">
            <q-item v-bind="scope.itemProps">
              <q-item-section>
                <q-item-label>{{ scope.opt.label }}</q-item-label>
                <q-item-label caption>
                  SKU: {{ scope.opt.sku || 'N/A' }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge
                  :color="scope.opt.stock > 0 ? 'green' : 'red'"
                  :label="`${scope.opt.stock} ${scope.opt.unit}`"
                />
              </q-item-section>
            </q-item>
          </template>
        </q-select>

        <!-- Item Display (after scan/select) -->
        <div v-if="selectedItem" class="q-mt-md bg-green-1 q-pa-sm rounded-borders">
          <div class="text-weight-medium">{{ selectedItem.name }}</div>
          <div class="text-caption">SKU: {{ selectedItem.sku }}</div>
          <q-badge
            :color="availableStock && availableStock > 0 ? 'green' : 'red'"
            :label="`Available: ${availableStock} ${selectedItem.unit || 'units'}`"
            class="q-mt-xs"
          />
        </div>

        <!-- Quantity Input -->
        <div v-if="selectedItem" class="q-mt-md">
          <q-input
            v-model.number="quantity"
            label="Quantity Taken"
            type="number"
            outlined
            dense
            min="0.01"
            step="0.01"
          >
            <template #prepend>
              <q-icon name="production_quantity_limits" />
            </template>
            <template #append>
              <div class="row q-gutter-xs">
                <q-btn
                  flat
                  dense
                  icon="remove"
                  size="sm"
                  @click="quantity = Math.max(0.01, quantity - 1)"
                />
                <q-btn
                  flat
                  dense
                  icon="add"
                  size="sm"
                  @click="quantity++"
                />
              </div>
            </template>
          </q-input>
          <div v-if="availableStock !== null && quantity > availableStock" class="text-negative text-caption q-mt-xs">
            ⚠️ Insufficient stock! Only {{ availableStock }} available.
          </div>
        </div>

        <!-- Project Selection -->
        <div v-if="selectedItem" class="q-mt-md">
          <q-select
            v-model="selectedProjectId"
            :options="projectOptions"
            label="Project *"
            outlined
            dense
            emit-value
            map-options
            use-input
            input-debounce="300"
          >
            <template #prepend>
              <q-icon name="construction" />
            </template>
          </q-select>
        </div>

        <!-- Notes (Optional, Expandable) -->
        <q-expansion-item
          v-if="selectedItem"
          label="Add Notes (Optional)"
          icon="notes"
          dense
          class="q-mt-sm"
        >
          <q-input
            v-model="notes"
            type="textarea"
            outlined
            dense
            rows="2"
            placeholder="Optional notes about this checkout..."
          />
        </q-expansion-item>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right">
        <q-btn flat label="Cancel" @click="closeDialog" />
        <q-btn
          color="primary"
          label="Check Out Material"
          :loading="loading"
          :disable="!selectedItem || !selectedProjectId || !quantity"
          @click="handleCheckout"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
