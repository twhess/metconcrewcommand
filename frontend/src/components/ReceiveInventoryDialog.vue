<script setup lang="ts">
import { ref, computed } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import type { InventoryItem, InventoryLocation } from '@/types'
import { useQuasar } from 'quasar'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'received': []
}>()

const $q = useQuasar()
const inventoryStore = useInventoryStore()

// Form data
const locationId = ref<number | null>(null)
const items = ref<Array<{ inventory_item_id: number | null; quantity: number; notes: string }>>([
  { inventory_item_id: null, quantity: 1, notes: '' }
])
const generalNotes = ref('')
const saving = ref(false)

// Available items and locations
const availableItems = ref<InventoryItem[]>([])
const availableLocations = ref<InventoryLocation[]>([])

// Item options for select
const itemOptions = computed(() =>
  availableItems.value.map(item => ({
    label: `${item.name}${item.sku ? ` (${item.sku})` : ''}`,
    value: item.id,
    item: item
  }))
)

// Location options (shop/warehouse only for receiving)
const locationOptions = computed(() =>
  availableLocations.value
    .filter(loc => loc.is_active && ['warehouse', 'yard', 'shop'].includes(loc.type))
    .map(loc => ({
      label: `${loc.name} (${loc.type})`,
      value: loc.id
    }))
)

// Barcode scan input
const barcodeInput = ref('')

// Add new item row
function addItem(): void {
  items.value.push({
    inventory_item_id: null,
    quantity: 1,
    notes: ''
  })
}

// Remove item row
function removeItem(index: number): void {
  if (items.value.length > 1) {
    items.value.splice(index, 1)
  }
}

// Handle barcode scan
function handleBarcodeScan(): void {
  if (!barcodeInput.value) return

  const scannedBarcode = barcodeInput.value.trim()
  const foundItem = availableItems.value.find(
    item => item.barcode === scannedBarcode || item.sku === scannedBarcode
  )

  if (foundItem) {
    // Check if item already in list
    const existingIndex = items.value.findIndex(
      item => item.inventory_item_id === foundItem.id
    )

    if (existingIndex >= 0) {
      // Increment quantity
      items.value[existingIndex].quantity++
      $q.notify({
        type: 'positive',
        message: `Quantity updated for ${foundItem.name}`,
        position: 'top'
      })
    } else {
      // Add new item
      items.value.push({
        inventory_item_id: foundItem.id,
        quantity: 1,
        notes: ''
      })
      $q.notify({
        type: 'positive',
        message: `Added ${foundItem.name}`,
        position: 'top'
      })
    }
  } else {
    $q.notify({
      type: 'negative',
      message: 'Item not found',
      position: 'top'
    })
  }

  barcodeInput.value = ''
}

// Validate form
function isValid(): boolean {
  if (!locationId.value) {
    $q.notify({
      type: 'negative',
      message: 'Please select a location',
      position: 'top'
    })
    return false
  }

  if (items.value.length === 0) {
    $q.notify({
      type: 'negative',
      message: 'Please add at least one item',
      position: 'top'
    })
    return false
  }

  for (const item of items.value) {
    if (!item.inventory_item_id) {
      $q.notify({
        type: 'negative',
        message: 'Please select an item for all rows',
        position: 'top'
      })
      return false
    }
    if (item.quantity <= 0) {
      $q.notify({
        type: 'negative',
        message: 'Quantity must be greater than 0',
        position: 'top'
      })
      return false
    }
  }

  return true
}

// Save receiving
async function handleSave(): Promise<void> {
  if (!isValid()) return

  saving.value = true
  try {
    await inventoryStore.receiveBatch(
      locationId.value!,
      items.value.map(item => ({
        inventory_item_id: item.inventory_item_id!,
        quantity: item.quantity,
        notes: item.notes || undefined
      })),
      generalNotes.value || undefined
    )

    $q.notify({
      type: 'positive',
      message: `${items.value.length} item${items.value.length !== 1 ? 's' : ''} received successfully`,
      position: 'top'
    })

    emit('received')
    closeDialog()
  } catch (error) {
    console.error('Failed to receive inventory:', error)
    $q.notify({
      type: 'negative',
      message: 'Failed to receive inventory',
      position: 'top'
    })
  } finally {
    saving.value = false
  }
}

// Close dialog
function closeDialog(): void {
  emit('update:modelValue', false)
  setTimeout(resetForm, 300)
}

// Reset form
function resetForm(): void {
  locationId.value = null
  items.value = [{ inventory_item_id: null, quantity: 1, notes: '' }]
  generalNotes.value = ''
  barcodeInput.value = ''
}

// Load data when dialog opens
async function loadData(): Promise<void> {
  try {
    const [itemsData, locationsData] = await Promise.all([
      inventoryStore.fetchItems({ is_active: true }),
      inventoryStore.fetchLocations({ is_active: true })
    ])
    availableItems.value = itemsData
    availableLocations.value = locationsData
  } catch (error) {
    console.error('Failed to load data:', error)
  }
}

// Watch for dialog open
import { watch } from 'vue'
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    loadData()
  }
})
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="min-width: 700px; max-width: 900px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">Receive Inventory</div>
        <q-space />
        <q-btn icon="close" flat round dense @click="closeDialog" />
      </q-card-section>

      <q-card-section>
        <!-- Location Selection -->
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12">
            <q-select
              v-model="locationId"
              :options="locationOptions"
              label="Receiving Location *"
              outlined
              dense
              emit-value
              map-options
              :rules="[val => !!val || 'Location is required']"
            >
              <template #prepend>
                <q-icon name="place" />
              </template>
            </q-select>
          </div>
        </div>

        <!-- Barcode Scanner -->
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12">
            <q-input
              v-model="barcodeInput"
              label="Scan Barcode / SKU"
              outlined
              dense
              @keyup.enter="handleBarcodeScan"
            >
              <template #prepend>
                <q-icon name="qr_code_scanner" />
              </template>
              <template #append>
                <q-btn
                  flat
                  dense
                  icon="search"
                  color="primary"
                  @click="handleBarcodeScan"
                />
              </template>
            </q-input>
            <div class="text-caption text-grey-7 q-mt-xs">
              Scan or enter barcode/SKU and press Enter
            </div>
          </div>
        </div>

        <q-separator class="q-my-md" />

        <!-- Items to Receive -->
        <div class="text-subtitle2 q-mb-sm">Items to Receive</div>

        <div
          v-for="(item, index) in items"
          :key="index"
          class="row q-col-gutter-md q-mb-md"
        >
          <div class="col-12 col-sm-5">
            <q-select
              v-model="item.inventory_item_id"
              :options="itemOptions"
              label="Item *"
              outlined
              dense
              emit-value
              map-options
              use-input
              input-debounce="300"
              @filter="(val, update) => update()"
            />
          </div>

          <div class="col-6 col-sm-2">
            <q-input
              v-model.number="item.quantity"
              label="Quantity *"
              type="number"
              outlined
              dense
              min="0.01"
              step="0.01"
            />
          </div>

          <div class="col-12 col-sm-4">
            <q-input
              v-model="item.notes"
              label="Notes"
              outlined
              dense
            />
          </div>

          <div class="col-6 col-sm-1 flex items-center">
            <q-btn
              flat
              dense
              round
              icon="delete"
              color="negative"
              :disable="items.length === 1"
              @click="removeItem(index)"
            >
              <q-tooltip v-if="items.length > 1">Remove</q-tooltip>
            </q-btn>
          </div>
        </div>

        <!-- Add Item Button -->
        <div class="row">
          <div class="col-12">
            <q-btn
              flat
              dense
              icon="add"
              label="Add Another Item"
              color="primary"
              @click="addItem"
            />
          </div>
        </div>

        <!-- General Notes -->
        <div class="row q-col-gutter-md q-mt-md">
          <div class="col-12">
            <q-input
              v-model="generalNotes"
              label="General Notes"
              outlined
              dense
              type="textarea"
              rows="2"
            />
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right">
        <q-btn flat label="Cancel" @click="closeDialog" />
        <q-btn
          color="primary"
          label="Receive Items"
          :loading="saving"
          @click="handleSave"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
