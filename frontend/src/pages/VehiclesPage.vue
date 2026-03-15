<template>
  <q-page class="q-pa-md">
    <div class="row q-mb-md items-center">
      <div class="col">
        <h4 class="q-my-none">Vehicles</h4>
      </div>
      <div class="col-auto row q-gutter-sm">
        <q-btn-toggle
          v-model="viewMode"
          toggle-color="primary"
          :options="[
            { label: 'Cards', value: 'cards', icon: 'grid_view' },
            { label: 'Table', value: 'table', icon: 'view_list' }
          ]"
        />
        <q-btn
          color="primary"
          label="Add Vehicle"
          icon="add"
          @click="openAddDialog"
        />
      </div>
    </div>

    <!-- Filters -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-3">
            <q-input
              v-model="filters.search"
              outlined
              dense
              label="Search"
              placeholder="Name, VIN, plate..."
              @update:model-value="loadVehicles"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-2">
            <q-select
              v-model="filters.status"
              outlined
              dense
              label="Status"
              :options="statusOptions"
              emit-value
              map-options
              @update:model-value="loadVehicles"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.vehicle_type"
              outlined
              dense
              label="Vehicle Type"
              :options="vehicleTypeOptions"
              emit-value
              map-options
              clearable
              @update:model-value="loadVehicles"
            />
          </div>
          <div class="col-12 col-md-2">
            <q-select
              v-model="filters.fuel_type"
              outlined
              dense
              label="Fuel Type"
              :options="fuelTypeOptions"
              emit-value
              map-options
              clearable
              @update:model-value="loadVehicles"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Loading State -->
    <div v-if="loading" class="text-center q-pa-lg">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Empty State -->
    <div v-else-if="vehicles.length === 0" class="text-center q-pa-lg">
      <q-icon name="local_shipping" size="4em" color="grey-5" />
      <p class="text-grey-7">No vehicles found</p>
    </div>

    <!-- Card View -->
    <div v-else-if="viewMode === 'cards'" class="row q-col-gutter-md">
      <div
        v-for="item in vehicles"
        :key="item.id"
        class="col-12 col-sm-6 col-md-4 col-lg-3"
      >
        <q-card>
          <q-card-section>
            <div class="row items-center q-mb-sm">
              <div class="col">
                <div class="text-h6">{{ item.name }}</div>
                <div class="text-caption text-grey-7">{{ item.vehicle_number || '' }}</div>
              </div>
              <div class="col-auto">
                <q-badge
                  :color="getStatusColor(item.status)"
                  :label="formatLabel(item.status)"
                />
              </div>
            </div>

            <q-separator class="q-my-sm" />

            <div v-if="item.year || item.make || item.model" class="text-body2 q-mb-xs">
              <q-icon name="directions_car" size="sm" class="q-mr-xs" />
              <strong>Vehicle:</strong> {{ [item.year, item.make, item.model].filter(Boolean).join(' ') }}
            </div>

            <div v-if="item.vehicle_type" class="text-body2 q-mb-xs">
              <q-icon name="category" size="sm" class="q-mr-xs" />
              <strong>Type:</strong> {{ formatLabel(item.vehicle_type) }}
            </div>

            <div v-if="item.license_plate" class="text-body2 q-mb-xs">
              <q-icon name="badge" size="sm" class="q-mr-xs" />
              <strong>Plate:</strong> {{ item.license_plate }}
            </div>

            <div class="text-body2 q-mb-xs">
              <q-icon name="speed" size="sm" class="q-mr-xs" />
              <strong>Odometer:</strong> {{ item.current_odometer_miles?.toLocaleString() || '0' }} mi
            </div>

            <div v-if="item.assigned_to" class="text-body2 q-mb-xs">
              <q-icon name="person" size="sm" class="q-mr-xs" />
              <strong>Operator:</strong> {{ item.assigned_to.name }}
            </div>

            <div class="text-body2 q-mb-xs">
              <q-icon name="location_on" size="sm" class="q-mr-xs" />
              <strong>Location:</strong> {{ formatLocation(item) }}
            </div>

            <!-- Compliance alerts -->
            <div v-if="hasComplianceAlert(item)" class="q-mt-sm">
              <q-badge v-if="isOverdue(item.next_dot_inspection_due)" color="negative" class="q-mr-xs">
                DOT Overdue
              </q-badge>
              <q-badge v-if="isOverdue(item.insurance_expiration)" color="negative" class="q-mr-xs">
                Insurance Expired
              </q-badge>
              <q-badge v-if="isOverdue(item.registration_expiration)" color="negative" class="q-mr-xs">
                Reg Expired
              </q-badge>
              <q-badge v-if="isDueSoon(item.next_dot_inspection_due)" color="warning" class="q-mr-xs">
                DOT Due Soon
              </q-badge>
              <q-badge v-if="isDueSoon(item.insurance_expiration)" color="warning" class="q-mr-xs">
                Insurance Due
              </q-badge>
              <q-badge v-if="isDueSoon(item.registration_expiration)" color="warning" class="q-mr-xs">
                Reg Due Soon
              </q-badge>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-actions>
            <q-btn
              flat
              dense
              color="primary"
              label="Details"
              icon="info"
              @click="viewDetails(item)"
            />
            <q-btn
              flat
              dense
              color="secondary"
              label="Move"
              icon="local_shipping"
              @click="openMoveDialog(item)"
            />
            <q-space />
            <q-btn
              flat
              dense
              round
              color="grey-7"
              icon="more_vert"
            >
              <q-menu>
                <q-list style="min-width: 100px">
                  <q-item clickable v-close-popup @click="editVehicle(item)">
                    <q-item-section avatar>
                      <q-icon name="edit" />
                    </q-item-section>
                    <q-item-section>Edit</q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item clickable v-close-popup @click="deleteVehicle(item)">
                    <q-item-section avatar>
                      <q-icon name="delete" color="negative" />
                    </q-item-section>
                    <q-item-section class="text-negative">Delete</q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <!-- Table View -->
    <q-table
      v-else-if="viewMode === 'table'"
      :rows="vehicles"
      :columns="columns"
      row-key="id"
      flat
      bordered
      :pagination="{ rowsPerPage: 25 }"
    >
      <template v-slot:body-cell-name="props">
        <q-td :props="props">
          <div class="text-weight-medium">{{ props.row.name }}</div>
          <div class="text-caption text-grey-7">{{ props.row.vehicle_number }}</div>
        </q-td>
      </template>

      <template v-slot:body-cell-vehicle="props">
        <q-td :props="props">
          {{ [props.row.year, props.row.make, props.row.model].filter(Boolean).join(' ') || 'N/A' }}
        </q-td>
      </template>

      <template v-slot:body-cell-status="props">
        <q-td :props="props">
          <q-badge
            :color="getStatusColor(props.row.status)"
            :label="formatLabel(props.row.status)"
          />
        </q-td>
      </template>

      <template v-slot:body-cell-type="props">
        <q-td :props="props">
          {{ props.row.vehicle_type ? formatLabel(props.row.vehicle_type) : 'N/A' }}
        </q-td>
      </template>

      <template v-slot:body-cell-location="props">
        <q-td :props="props">
          {{ formatLocation(props.row) }}
        </q-td>
      </template>

      <template v-slot:body-cell-odometer="props">
        <q-td :props="props">
          {{ props.row.current_odometer_miles?.toLocaleString() || '0' }} mi
        </q-td>
      </template>

      <template v-slot:body-cell-operator="props">
        <q-td :props="props">
          {{ props.row.assigned_to?.name || 'Unassigned' }}
        </q-td>
      </template>

      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn
            flat dense round
            icon="visibility"
            color="primary"
            @click="viewDetails(props.row)"
          >
            <q-tooltip>View Details</q-tooltip>
          </q-btn>
          <q-btn
            flat dense round
            icon="edit"
            color="secondary"
            @click="editVehicle(props.row)"
          >
            <q-tooltip>Edit</q-tooltip>
          </q-btn>
          <q-btn
            flat dense round
            icon="local_shipping"
            color="info"
            @click="openMoveDialog(props.row)"
          >
            <q-tooltip>Move</q-tooltip>
          </q-btn>
          <q-btn
            flat dense round
            icon="delete"
            color="negative"
            @click="deleteVehicle(props.row)"
          >
            <q-tooltip>Delete</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>

    <!-- Dialogs -->
    <VehicleFormDialog
      v-model="showFormDialog"
      :vehicle="selectedVehicle"
      @saved="onVehicleSaved"
    />

    <VehicleDetailDialog
      v-model="showDetailDialog"
      :vehicle-id="selectedVehicleId"
      @edit="onEditFromDetail"
    />

    <VehicleMoveDialog
      v-model="showMoveDialog"
      :vehicle="selectedVehicle"
      @moved="onVehicleMoved"
    />
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import type { Vehicle } from '@/types'
import VehicleFormDialog from '@/components/VehicleFormDialog.vue'
import VehicleDetailDialog from '@/components/VehicleDetailDialog.vue'
import VehicleMoveDialog from '@/components/VehicleMoveDialog.vue'

const $q = useQuasar()

// State
const vehicles = ref<Vehicle[]>([])
const loading = ref(false)
const showFormDialog = ref(false)
const showDetailDialog = ref(false)
const showMoveDialog = ref(false)
const selectedVehicle = ref<Vehicle | null>(null)
const selectedVehicleId = ref<number | null>(null)
const viewMode = ref<'cards' | 'table'>('cards')

// Filters
const filters = ref({
  search: '',
  status: 'all',
  vehicle_type: null as string | null,
  fuel_type: null as string | null
})

const statusOptions = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Maintenance', value: 'maintenance' },
  { label: 'Out of Service', value: 'out_of_service' },
  { label: 'In Transit', value: 'in_transit' }
]

const vehicleTypeOptions = [
  { label: 'Dump Truck', value: 'dump_truck' },
  { label: 'Concrete Mixer', value: 'concrete_mixer' },
  { label: 'Pickup', value: 'pickup' },
  { label: 'Flatbed', value: 'flatbed' },
  { label: 'Lowboy', value: 'lowboy' },
  { label: 'Skid Steer Trailer', value: 'skid_steer_trailer' },
  { label: 'Utility Van', value: 'utility_van' },
  { label: 'Service Truck', value: 'service_truck' }
]

const fuelTypeOptions = [
  { label: 'Diesel', value: 'diesel' },
  { label: 'Gasoline', value: 'gasoline' },
  { label: 'Electric', value: 'electric' },
  { label: 'Hybrid', value: 'hybrid' },
  { label: 'Propane', value: 'propane' }
]

const columns = [
  { name: 'name', label: 'Vehicle', align: 'left' as const, field: 'name', sortable: true },
  { name: 'vehicle', label: 'Year/Make/Model', align: 'left' as const, field: 'make', sortable: true },
  { name: 'status', label: 'Status', align: 'center' as const, field: 'status', sortable: true },
  { name: 'type', label: 'Type', align: 'left' as const, field: 'vehicle_type', sortable: true },
  { name: 'license_plate', label: 'Plate', align: 'left' as const, field: 'license_plate', sortable: true },
  { name: 'odometer', label: 'Odometer', align: 'right' as const, field: 'current_odometer_miles', sortable: true },
  { name: 'operator', label: 'Operator', align: 'left' as const, field: 'assigned_to_user_id', sortable: true },
  { name: 'location', label: 'Location', align: 'left' as const, field: 'current_location_type', sortable: true },
  { name: 'actions', label: 'Actions', align: 'center' as const, field: 'id', sortable: false }
]

// Methods
async function loadVehicles(): Promise<void> {
  loading.value = true
  try {
    const params: Record<string, string> = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status && filters.value.status !== 'all') params.status = filters.value.status
    if (filters.value.vehicle_type) params.vehicle_type = filters.value.vehicle_type
    if (filters.value.fuel_type) params.fuel_type = filters.value.fuel_type

    const response = await apiClient.get<{ success: boolean; data: Vehicle[] }>('/vehicles', { params })
    vehicles.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load vehicles:', error)
    $q.notify({ type: 'negative', message: 'Failed to load vehicles' })
  } finally {
    loading.value = false
  }
}

function formatLabel(value: string): string {
  return value.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    active: 'positive',
    inactive: 'grey',
    maintenance: 'warning',
    out_of_service: 'negative',
    in_transit: 'info'
  }
  return colors[status] || 'grey'
}

function formatLocation(item: Vehicle): string {
  if (!item.current_location_type) return 'Not set'
  if (item.current_location_type === 'in_transit') return 'In Transit'
  return item.current_location_type.replace(/_/g, ' ').toUpperCase()
}

function isOverdue(dateStr?: string): boolean {
  if (!dateStr) return false
  return new Date(dateStr) < new Date()
}

function isDueSoon(dateStr?: string): boolean {
  if (!dateStr) return false
  const date = new Date(dateStr)
  const now = new Date()
  if (date < now) return false
  const diffDays = (date.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
  return diffDays <= 30
}

function hasComplianceAlert(item: Vehicle): boolean {
  return isOverdue(item.next_dot_inspection_due) ||
    isOverdue(item.insurance_expiration) ||
    isOverdue(item.registration_expiration) ||
    isDueSoon(item.next_dot_inspection_due) ||
    isDueSoon(item.insurance_expiration) ||
    isDueSoon(item.registration_expiration)
}

function openAddDialog(): void {
  selectedVehicle.value = null
  showFormDialog.value = true
}

function viewDetails(item: Vehicle): void {
  selectedVehicleId.value = item.id
  showDetailDialog.value = true
}

function editVehicle(item: Vehicle): void {
  selectedVehicle.value = item
  showFormDialog.value = true
}

function onEditFromDetail(item: Vehicle): void {
  selectedVehicle.value = item
  showFormDialog.value = true
}

function openMoveDialog(item: Vehicle): void {
  selectedVehicle.value = item
  showMoveDialog.value = true
}

function onVehicleSaved(): void {
  loadVehicles()
  selectedVehicle.value = null
}

function onVehicleMoved(): void {
  loadVehicles()
  selectedVehicle.value = null
}

function deleteVehicle(item: Vehicle): void {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete ${item.name}?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await apiClient.delete(`/vehicles/${item.id}`)
      $q.notify({ type: 'positive', message: 'Vehicle deleted successfully' })
      loadVehicles()
    } catch (error) {
      console.error('Failed to delete vehicle:', error)
      $q.notify({ type: 'negative', message: 'Failed to delete vehicle' })
    }
  })
}

// Lifecycle
onMounted(() => {
  loadVehicles()
})
</script>

<style scoped>
.q-page {
  max-width: 1400px;
  margin: 0 auto;
}
</style>
