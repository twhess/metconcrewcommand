<script setup lang="ts">
import { ref, onMounted } from 'vue'
import TruckInventoryCard from '@/components/TruckInventoryCard.vue'
import type { Vehicle } from '@/types'
import apiClient from '@/api/client'
import type { ApiResponse } from '@/types'

// Vehicles data
const vehicles = ref<Vehicle[]>([])
const loading = ref(false)
const selectedVehicle = ref<Vehicle | null>(null)

// Load vehicles
async function loadVehicles(): Promise<void> {
  loading.value = true
  try {
    const response = await apiClient.get<ApiResponse<Vehicle[]>>('/vehicles')
    // Filter to only trucks (you can adjust the filter based on your vehicle types)
    vehicles.value = response.data.data.filter(v => v.is_active)
  } catch (error) {
    console.error('Failed to load vehicles:', error)
  } finally {
    loading.value = false
  }
}

// Handle transfer action
function handleTransfer(vehicleId: number): void {
  console.log('Transfer from vehicle:', vehicleId)
  // TODO: Open transfer dialog
}

// Handle record usage action
function handleRecordUsage(vehicleId: number): void {
  console.log('Record usage from vehicle:', vehicleId)
  // TODO: Open record usage dialog
}

// Handle restock action
function handleRestock(vehicleId: number): void {
  console.log('Restock vehicle:', vehicleId)
  // TODO: Open restock dialog
}

// Load data
onMounted(() => {
  loadVehicles()
})
</script>

<template>
  <q-page padding>
    <!-- Header -->
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h4">Truck Inventory</div>
        <div class="text-subtitle2 text-grey-7">
          {{ vehicles.length }} vehicle{{ vehicles.length !== 1 ? 's' : '' }}
        </div>
      </div>
    </div>

    <!-- Info Banner -->
    <q-banner class="bg-blue-1 q-mb-md" rounded>
      <template #avatar>
        <q-icon name="info" color="blue" />
      </template>
      <div class="text-body2">
        View and manage inventory on trucks. Use this page to check stock levels on vehicles in the field.
      </div>
    </q-banner>

    <!-- Loading State -->
    <div v-if="loading" class="row justify-center q-mt-lg">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Vehicle Cards Grid -->
    <div v-else class="row q-col-gutter-md">
      <div
        v-for="vehicle in vehicles"
        :key="vehicle.id"
        class="col-12 col-sm-6 col-md-4"
      >
        <TruckInventoryCard
          :vehicle="vehicle"
          :show-actions="true"
          @transfer="handleTransfer"
          @record-usage="handleRecordUsage"
          @restock="handleRestock"
        />
      </div>

      <!-- Empty State -->
      <div v-if="vehicles.length === 0" class="col-12">
        <q-card flat bordered>
          <q-card-section class="text-center q-pa-lg">
            <q-icon name="local_shipping" size="4em" color="grey-5" />
            <div class="text-h6 q-mt-md">No vehicles found</div>
            <div class="text-grey-7 q-mt-sm">
              Add vehicles to the system to track their inventory
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
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
