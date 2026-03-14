<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useInventoryStore } from '@/stores/inventory'
import type { Vehicle, LocationInventory } from '@/types'

const props = defineProps<{
  vehicle: Vehicle
  showActions?: boolean
}>()

const emit = defineEmits<{
  'transfer': [vehicleId: number]
  'recordUsage': [vehicleId: number]
  'restock': [vehicleId: number]
}>()

const inventoryStore = useInventoryStore()

// Loading state
const loading = ref(false)
const inventory = ref<LocationInventory | null>(null)

// Get stock color based on quantity
function getStockColor(quantity: number): string {
  if (quantity === 0) return 'red'
  if (quantity < 10) return 'orange'
  return 'green'
}

// Load inventory for this truck
async function loadInventory(): Promise<void> {
  if (!props.vehicle.id) return

  loading.value = true
  try {
    // Find the inventory location for this vehicle
    const locations = await inventoryStore.fetchLocations({ is_active: true })
    const truckLocation = locations.find(loc => loc.vehicle_id === props.vehicle.id)

    if (truckLocation) {
      inventory.value = await inventoryStore.fetchLocationInventory(truckLocation.id)
    } else {
      inventory.value = null
    }
  } catch (error) {
    console.error('Failed to load truck inventory:', error)
  } finally {
    loading.value = false
  }
}

// Watch for vehicle changes
watch(() => props.vehicle.id, () => {
  loadInventory()
}, { immediate: true })

// Refresh inventory
function refresh(): void {
  loadInventory()
}

// Expose refresh method
defineExpose({
  refresh
})
</script>

<template>
  <q-card flat bordered>
    <q-card-section class="bg-blue-1">
      <div class="row items-center">
        <div class="col">
          <div class="text-h6">{{ vehicle.name || vehicle.make }}</div>
          <div class="text-caption text-grey-7">
            {{ vehicle.license_plate || 'No License Plate' }}
          </div>
        </div>
        <div class="col-auto">
          <q-icon name="local_shipping" size="md" color="blue" />
        </div>
      </div>
    </q-card-section>

    <q-separator />

    <!-- Loading State -->
    <q-card-section v-if="loading" class="text-center">
      <q-spinner color="primary" size="2em" />
    </q-card-section>

    <!-- Empty State -->
    <q-card-section v-else-if="!inventory || inventory.stocks.length === 0" class="text-center">
      <q-icon name="inventory_2" size="3em" color="grey-5" />
      <div class="text-grey-7 q-mt-sm">No inventory on this truck</div>
    </q-card-section>

    <!-- Inventory List -->
    <q-card-section v-else class="q-pa-none">
      <q-list separator>
        <q-item
          v-for="stock in inventory.stocks"
          :key="stock.item.id"
        >
          <q-item-section>
            <q-item-label>{{ stock.item.name }}</q-item-label>
            <q-item-label caption>
              {{ stock.item.category || 'Uncategorized' }}
            </q-item-label>
          </q-item-section>

          <q-item-section side>
            <q-badge
              :color="getStockColor(stock.quantity)"
              :label="`${stock.quantity} ${stock.item.unit || 'units'}`"
            />
          </q-item-section>
        </q-item>
      </q-list>

      <div class="q-pa-sm bg-grey-2 text-caption text-grey-7">
        Total Items: {{ inventory.stocks.length }}
      </div>
    </q-card-section>

    <!-- Actions -->
    <q-separator v-if="showActions && inventory && inventory.stocks.length > 0" />

    <q-card-actions v-if="showActions && inventory && inventory.stocks.length > 0">
      <q-btn
        flat
        dense
        color="primary"
        label="Transfer to Project"
        icon="send"
        @click="emit('transfer', vehicle.id)"
      />
      <q-btn
        flat
        dense
        color="primary"
        label="Record Usage"
        icon="remove_circle"
        @click="emit('recordUsage', vehicle.id)"
      />
      <q-space />
      <q-btn
        flat
        dense
        color="positive"
        label="Restock"
        icon="add_circle"
        @click="emit('restock', vehicle.id)"
      />
    </q-card-actions>
  </q-card>
</template>
