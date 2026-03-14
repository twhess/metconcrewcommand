<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import type { InventoryItem, InventoryLocation, Project } from '@/types'
import { useQuasar } from 'quasar'

const props = defineProps<{
  modelValue: boolean
  project?: Project | null
  projectId?: number
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'recorded': []
}>()

const $q = useQuasar()
const inventoryStore = useInventoryStore()

// Form data
const selectedItemId = ref<number | null>(null)
const fromLocationId = ref<number | null>(null)
const quantity = ref<number>(1)
const notes = ref('')
const saving = ref(false)

// Available items and locations
const availableItems = ref<InventoryItem[]>([])
const availableLocations = ref<InventoryLocation[]>([])
const usageHistory = ref<any[]>([])

// Computed project ID (from prop or projectId)
const currentProjectId = computed(() => props.project?.id || props.projectId)

// Item options for select
const itemOptions = computed(() =>
  availableItems.value.map(item => ({
    label: `${item.name}${item.sku ? ` (${item.sku})` : ''}`,
    value: item.id,
    item: item
  }))
)

// Location options (trucks and shops)
const locationOptions = computed(() =>
  availableLocations.value
    .filter(loc => loc.is_active)
    .map(loc => ({
      label: `${loc.name} (${loc.type})`,
      value: loc.id,
      type: loc.type
    }))
)

// Available stock at selected location
const availableStock = computed(() => {
  if (!selectedItemId.value || !fromLocationId.value) return null

  const item = availableItems.value.find(i => i.id === selectedItemId.value)
  if (!item?.stock) return null

  const stock = item.stock.find(s => s.inventory_location_id === fromLocationId.value)
  return stock?.quantity || 0
})

// Validate form
function isValid(): boolean {
  if (!currentProjectId.value) {
    $q.notify({
      type: 'negative',
      message: 'Project is required',
      position: 'top'
    })
    return false
  }

  if (!selectedItemId.value) {
    $q.notify({
      type: 'negative',
      message: 'Please select an item',
      position: 'top'
    })
    return false
  }

  if (!fromLocationId.value) {
    $q.notify({
      type: 'negative',
      message: 'Please select a location',
      position: 'top'
    })
    return false
  }

  if (quantity.value <= 0) {
    $q.notify({
      type: 'negative',
      message: 'Quantity must be greater than 0',
      position: 'top'
    })
    return false
  }

  if (availableStock.value !== null && quantity.value > availableStock.value) {
    $q.notify({
      type: 'negative',
      message: `Insufficient stock. Available: ${availableStock.value}`,
      position: 'top'
    })
    return false
  }

  return true
}

// Save usage
async function handleSave(): Promise<void> {
  if (!isValid()) return

  saving.value = true
  try {
    await inventoryStore.recordUsage(
      selectedItemId.value!,
      fromLocationId.value!,
      currentProjectId.value!,
      quantity.value,
      notes.value || undefined
    )

    $q.notify({
      type: 'positive',
      message: 'Usage recorded successfully',
      position: 'top'
    })

    emit('recorded')
    await loadUsageHistory() // Refresh history
    resetForm()
  } catch (error) {
    console.error('Failed to record usage:', error)
    $q.notify({
      type: 'negative',
      message: 'Failed to record usage',
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
  selectedItemId.value = null
  fromLocationId.value = null
  quantity.value = 1
  notes.value = ''
}

// Load usage history
async function loadUsageHistory(): Promise<void> {
  if (!currentProjectId.value) return

  try {
    const data = await inventoryStore.fetchProjectUsage(currentProjectId.value)
    usageHistory.value = data.usage_summary || []
  } catch (error) {
    console.error('Failed to load usage history:', error)
  }
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

    if (currentProjectId.value) {
      await loadUsageHistory()
    }
  } catch (error) {
    console.error('Failed to load data:', error)
  }
}

// Watch for dialog open
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
        <div class="text-h6">Record Material Usage</div>
        <q-space />
        <q-btn icon="close" flat round dense @click="closeDialog" />
      </q-card-section>

      <q-card-section>
        <!-- Project Info -->
        <div v-if="project" class="bg-blue-1 q-pa-sm rounded-borders q-mb-md">
          <div class="text-caption text-grey-7">Project</div>
          <div class="text-weight-medium">{{ project.name }}</div>
          <div v-if="project.project_number" class="text-caption">{{ project.project_number }}</div>
        </div>

        <!-- Usage Form -->
        <div class="row q-col-gutter-md">
          <div class="col-12 col-sm-6">
            <q-select
              v-model="selectedItemId"
              :options="itemOptions"
              label="Item *"
              outlined
              dense
              emit-value
              map-options
              use-input
              input-debounce="300"
              @filter="(val, update) => update()"
            >
              <template #prepend>
                <q-icon name="inventory_2" />
              </template>
            </q-select>
          </div>

          <div class="col-12 col-sm-6">
            <q-select
              v-model="fromLocationId"
              :options="locationOptions"
              label="Source Location *"
              outlined
              dense
              emit-value
              map-options
            >
              <template #prepend>
                <q-icon name="place" />
              </template>
              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.label }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge
                      :color="scope.opt.type === 'truck' ? 'blue' : 'green'"
                      :label="scope.opt.type"
                    />
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>

          <div class="col-12 col-sm-6">
            <q-input
              v-model.number="quantity"
              label="Quantity Used *"
              type="number"
              outlined
              dense
              min="0.01"
              step="0.01"
            >
              <template #prepend>
                <q-icon name="production_quantity_limits" />
              </template>
            </q-input>
            <div v-if="availableStock !== null" class="text-caption q-mt-xs">
              <span :class="quantity > availableStock ? 'text-negative' : 'text-positive'">
                Available: {{ availableStock }}
              </span>
            </div>
          </div>

          <div class="col-12 col-sm-6">
            <q-input
              v-model="notes"
              label="Notes"
              outlined
              dense
            />
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-actions align="right">
        <q-btn flat label="Cancel" @click="closeDialog" />
        <q-btn
          color="primary"
          label="Record Usage"
          :loading="saving"
          @click="handleSave"
        />
      </q-card-actions>

      <!-- Usage History Section -->
      <q-separator v-if="usageHistory.length > 0" />

      <q-card-section v-if="usageHistory.length > 0">
        <div class="text-subtitle2 q-mb-sm">Usage History for this Project</div>

        <q-list bordered separator>
          <q-item
            v-for="(usage, index) in usageHistory"
            :key="index"
          >
            <q-item-section>
              <q-item-label>{{ usage.item.name }}</q-item-label>
              <q-item-label caption>
                Total Used: {{ usage.total_used }} {{ usage.item.unit || 'units' }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge color="blue" :label="`${usage.transactions.length} transaction${usage.transactions.length !== 1 ? 's' : ''}`" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
