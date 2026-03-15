<template>
  <q-dialog v-model="isOpen" maximized>
    <q-card>
      <!-- Header -->
      <q-card-section class="row items-center q-pb-none">
        <div class="col">
          <div class="row items-center q-gutter-sm">
            <div class="text-h6">{{ vehicle?.name || 'Vehicle Details' }}</div>
            <div class="text-caption text-grey-7" v-if="vehicle?.vehicle_number">
              #{{ vehicle.vehicle_number }}
            </div>
            <q-badge
              v-if="vehicle"
              :color="getStatusColor(vehicle.status)"
              :label="formatLabel(vehicle.status)"
            />
          </div>
        </div>
        <q-btn
          flat
          label="Edit"
          color="primary"
          icon="edit"
          :disable="!vehicle"
          class="q-mr-sm"
          @click="onEdit"
        />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <!-- Loading State -->
      <q-card-section v-if="loading" class="text-center q-pa-lg">
        <q-spinner color="primary" size="3em" />
      </q-card-section>

      <!-- Content -->
      <q-card-section v-else-if="vehicle">
        <q-tabs
          v-model="tab"
          dense
          class="text-grey"
          active-color="primary"
          indicator-color="primary"
          align="justify"
        >
          <q-tab name="details" label="Details" />
          <q-tab name="compliance" label="Compliance" />
          <q-tab name="movements" label="Movements" />
          <q-tab name="maintenance" label="Maintenance" />
          <q-tab name="mileage" label="Mileage" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="tab" animated>
          <!-- Details Tab -->
          <q-tab-panel name="details">
            <div class="q-gutter-md">
              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Year / Make / Model</div>
                  <div>{{ [vehicle.year, vehicle.make, vehicle.model].filter(Boolean).join(' ') || 'N/A' }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">Color</div>
                  <div>{{ vehicle.color || 'N/A' }}</div>
                </div>
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Vehicle Type</div>
                  <div>{{ formatLabel(vehicle.vehicle_type) }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">Fuel Type</div>
                  <div>{{ formatLabel(vehicle.fuel_type) }}</div>
                </div>
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">VIN</div>
                  <div>{{ vehicle.vin || 'N/A' }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">License Plate</div>
                  <div>{{ vehicle.license_plate || 'N/A' }}</div>
                </div>
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Registration State</div>
                  <div>{{ vehicle.registration_state || 'N/A' }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">Registration Expiration</div>
                  <div>{{ formatDateShort(vehicle.registration_expiration) }}</div>
                </div>
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Weight Class</div>
                  <div>{{ vehicle.weight_class || 'N/A' }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">GVWR</div>
                  <div>{{ vehicle.gvwr_pounds ? `${vehicle.gvwr_pounds.toLocaleString()} lbs` : 'N/A' }}</div>
                </div>
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Towing Capacity</div>
                  <div>{{ vehicle.towing_capacity_pounds ? `${vehicle.towing_capacity_pounds.toLocaleString()} lbs` : 'N/A' }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">Tank Capacity</div>
                  <div>{{ vehicle.tank_capacity_gallons ? `${vehicle.tank_capacity_gallons} gal` : 'N/A' }}</div>
                </div>
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Current Odometer</div>
                  <div>{{ vehicle.current_odometer_miles != null ? `${Number(vehicle.current_odometer_miles).toLocaleString()} mi` : 'N/A' }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">Assigned Operator</div>
                  <div>{{ vehicle.assigned_to?.name || 'Unassigned' }}</div>
                </div>
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Current Location</div>
                  <div>{{ vehicle.current_location_type || 'Unknown' }}</div>
                </div>
              </div>

              <div v-if="vehicle.description">
                <div class="text-caption text-grey-7">Description</div>
                <div>{{ vehicle.description }}</div>
              </div>

              <div v-if="vehicle.notes">
                <div class="text-caption text-grey-7">Notes</div>
                <div>{{ vehicle.notes }}</div>
              </div>

              <div class="row q-col-gutter-md text-caption text-grey-7">
                <div class="col-6" v-if="vehicle.created_at">
                  <div>Created: {{ formatDate(vehicle.created_at) }}</div>
                </div>
                <div class="col-6" v-if="vehicle.updated_at">
                  <div>Updated: {{ formatDate(vehicle.updated_at) }}</div>
                </div>
              </div>
            </div>
          </q-tab-panel>

          <!-- Compliance Tab -->
          <q-tab-panel name="compliance">
            <div class="q-gutter-md">
              <!-- CDL Required -->
              <q-card flat bordered>
                <q-card-section>
                  <div class="row items-center">
                    <div class="col text-subtitle1">CDL Required</div>
                    <q-badge
                      :color="vehicle.requires_cdl ? 'warning' : 'positive'"
                      :label="vehicle.requires_cdl ? 'Yes' : 'No'"
                    />
                  </div>
                </q-card-section>
              </q-card>

              <!-- DOT Inspection -->
              <q-card flat bordered>
                <q-card-section>
                  <div class="row items-center q-mb-sm">
                    <div class="col text-subtitle1">DOT Inspection Required</div>
                    <q-badge
                      :color="vehicle.requires_dot_inspection ? 'warning' : 'positive'"
                      :label="vehicle.requires_dot_inspection ? 'Yes' : 'No'"
                    />
                  </div>
                  <div v-if="vehicle.requires_dot_inspection" class="row q-col-gutter-md q-mt-xs">
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Last DOT Inspection</div>
                      <div>{{ formatDateShort(vehicle.last_dot_inspection_date) }}</div>
                    </div>
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Next DOT Due</div>
                      <div :class="getComplianceClass(vehicle.next_dot_inspection_due)">
                        {{ formatDateShort(vehicle.next_dot_inspection_due) }}
                        <q-badge
                          v-if="vehicle.next_dot_inspection_due"
                          :color="getDueStatus(vehicle.next_dot_inspection_due).color"
                          :label="getDueStatus(vehicle.next_dot_inspection_due).label"
                          class="q-ml-sm"
                        />
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>

              <!-- Insurance -->
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-subtitle1 q-mb-sm">Insurance</div>
                  <div class="row q-col-gutter-md">
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Provider</div>
                      <div>{{ vehicle.insurance_provider || 'N/A' }}</div>
                    </div>
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Policy Number</div>
                      <div>{{ vehicle.insurance_policy_number || 'N/A' }}</div>
                    </div>
                  </div>
                  <div class="row q-col-gutter-md q-mt-xs">
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Expiration</div>
                      <div :class="getComplianceClass(vehicle.insurance_expiration)">
                        {{ formatDateShort(vehicle.insurance_expiration) }}
                        <q-badge
                          v-if="vehicle.insurance_expiration"
                          :color="getDueStatus(vehicle.insurance_expiration).color"
                          :label="getDueStatus(vehicle.insurance_expiration).label"
                          class="q-ml-sm"
                        />
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>

              <!-- Registration -->
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-subtitle1 q-mb-sm">Registration</div>
                  <div class="row q-col-gutter-md">
                    <div class="col-6">
                      <div class="text-caption text-grey-7">State</div>
                      <div>{{ vehicle.registration_state || 'N/A' }}</div>
                    </div>
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Expiration</div>
                      <div :class="getComplianceClass(vehicle.registration_expiration)">
                        {{ formatDateShort(vehicle.registration_expiration) }}
                        <q-badge
                          v-if="vehicle.registration_expiration"
                          :color="getDueStatus(vehicle.registration_expiration).color"
                          :label="getDueStatus(vehicle.registration_expiration).label"
                          class="q-ml-sm"
                        />
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </q-tab-panel>

          <!-- Movements Tab -->
          <q-tab-panel name="movements">
            <div v-if="loadingMovements" class="text-center q-pa-lg">
              <q-spinner color="primary" size="2em" />
            </div>
            <div v-else-if="movements.length === 0" class="text-center text-grey-7 q-pa-lg">
              No movement history
            </div>
            <q-timeline v-else color="primary">
              <q-timeline-entry
                v-for="movement in movements"
                :key="movement.id"
                :subtitle="formatDate(movement.moved_at)"
              >
                <template #title>
                  <q-badge
                    :color="getMovementTypeColor(movement.movement_type)"
                    :label="formatLabel(movement.movement_type)"
                  />
                </template>
                <div>
                  <div v-if="movement.to_location_type">
                    <strong>To:</strong> {{ movement.to_location_type }}
                  </div>
                  <div v-if="movement.odometer_reading">
                    <strong>Odometer:</strong> {{ Number(movement.odometer_reading).toLocaleString() }} mi
                  </div>
                  <div v-if="movement.moved_by_user">
                    <strong>Moved by:</strong> {{ movement.moved_by_user.name }}
                  </div>
                  <div v-if="movement.notes" class="text-caption text-grey-7 q-mt-sm">
                    {{ movement.notes }}
                  </div>
                </div>
              </q-timeline-entry>
            </q-timeline>
          </q-tab-panel>

          <!-- Maintenance Tab -->
          <q-tab-panel name="maintenance">
            <div v-if="loadingMaintenance" class="text-center q-pa-lg">
              <q-spinner color="primary" size="2em" />
            </div>
            <div v-else-if="maintenanceRecords.length === 0" class="text-center text-grey-7 q-pa-lg">
              No maintenance records
            </div>
            <q-table
              v-else
              :rows="maintenanceRecords"
              :columns="maintenanceColumns"
              row-key="id"
              flat
              bordered
              dense
              :pagination="{ rowsPerPage: 10 }"
            >
              <template #body-cell-category="slotProps">
                <q-td :props="slotProps">
                  <q-badge :color="getCategoryColor(slotProps.row.category)">
                    {{ slotProps.row.category }}
                  </q-badge>
                </q-td>
              </template>
              <template #body-cell-total_cost="slotProps">
                <q-td :props="slotProps">
                  {{ slotProps.row.total_cost != null ? `$${Number(slotProps.row.total_cost).toFixed(2)}` : '-' }}
                </q-td>
              </template>
            </q-table>
          </q-tab-panel>

          <!-- Mileage Tab -->
          <q-tab-panel name="mileage">
            <div v-if="loadingMileage" class="text-center q-pa-lg">
              <q-spinner color="primary" size="2em" />
            </div>
            <template v-else>
              <!-- Summary Cards -->
              <div class="row q-col-gutter-md q-mb-md">
                <div class="col-12 col-sm-4">
                  <q-card flat bordered class="text-center q-pa-md">
                    <div class="text-caption text-grey-7">Total Miles</div>
                    <div class="text-h5">{{ mileageSummary.totalMiles.toLocaleString() }}</div>
                  </q-card>
                </div>
                <div class="col-12 col-sm-4">
                  <q-card flat bordered class="text-center q-pa-md">
                    <div class="text-caption text-grey-7">Total Trips</div>
                    <div class="text-h5">{{ mileageSummary.totalTrips }}</div>
                  </q-card>
                </div>
                <div class="col-12 col-sm-4">
                  <q-card flat bordered class="text-center q-pa-md">
                    <div class="text-caption text-grey-7">Avg Distance</div>
                    <div class="text-h5">{{ mileageSummary.avgDistance.toFixed(1) }} mi</div>
                  </q-card>
                </div>
              </div>

              <div class="row items-center q-mb-sm">
                <q-space />
                <q-btn
                  color="primary"
                  label="Add Entry"
                  icon="add"
                  size="sm"
                  unelevated
                  @click="showMileageDialog = true"
                />
              </div>

              <div v-if="mileageLogs.length === 0" class="text-center text-grey-7 q-pa-lg">
                No mileage records
              </div>
              <q-table
                v-else
                :rows="mileageLogs"
                :columns="mileageColumns"
                row-key="id"
                flat
                bordered
                dense
                :pagination="{ rowsPerPage: 10 }"
              >
                <template #body-cell-trip_type="slotProps">
                  <q-td :props="slotProps">
                    <q-badge :color="getTripTypeColor(slotProps.row.trip_type)">
                      {{ formatLabel(slotProps.row.trip_type) }}
                    </q-badge>
                  </q-td>
                </template>
              </q-table>
            </template>
          </q-tab-panel>
        </q-tab-panels>
      </q-card-section>

      <!-- Mileage Log Dialog -->
      <MileageLogDialog
        v-model="showMileageDialog"
        :vehicle-id="vehicleId || 0"
        :current-odometer="vehicle?.current_odometer_miles ? Number(vehicle.current_odometer_miles) : 0"
        @saved="onMileageSaved"
      />
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useQuasar } from 'quasar'
import type { QTableColumn } from 'quasar'
import apiClient from '@/api/client'
import MileageLogDialog from '@/components/MileageLogDialog.vue'
import type { Vehicle, VehicleMovement, MaintenanceRecord, MileageLog } from '@/types'

interface Props {
  modelValue: boolean
  vehicleId: number | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'edit', vehicle: Vehicle): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const $q = useQuasar()

const isOpen = ref(props.modelValue)
const loading = ref(false)
const loadingMovements = ref(false)
const loadingMaintenance = ref(false)
const loadingMileage = ref(false)
const vehicle = ref<Vehicle | null>(null)
const movements = ref<VehicleMovement[]>([])
const maintenanceRecords = ref<MaintenanceRecord[]>([])
const mileageLogs = ref<MileageLog[]>([])
const tab = ref('details')
const showMileageDialog = ref(false)

const mileageSummary = computed(() => {
  const totalMiles = mileageLogs.value.reduce((sum, log) => sum + Number(log.distance_miles), 0)
  const totalTrips = mileageLogs.value.length
  const avgDistance = totalTrips > 0 ? totalMiles / totalTrips : 0
  return { totalMiles, totalTrips, avgDistance }
})

const maintenanceColumns: QTableColumn[] = [
  { name: 'performed_at', label: 'Date', field: 'performed_at', align: 'left', sortable: true, format: (val: string) => formatDateShort(val) },
  { name: 'maintenance_type', label: 'Type', field: 'maintenance_type', align: 'left', sortable: true },
  { name: 'category', label: 'Category', field: 'category', align: 'left', sortable: true },
  { name: 'performed_by', label: 'Performed By', field: (row: MaintenanceRecord) => row.performed_by_user?.name || row.vendor_company?.name || '-', align: 'left' },
  { name: 'total_cost', label: 'Cost', field: 'total_cost', align: 'right', sortable: true },
  { name: 'work_order_number', label: 'Work Order #', field: 'work_order_number', align: 'left' }
]

const mileageColumns: QTableColumn[] = [
  { name: 'trip_date', label: 'Date', field: 'trip_date', align: 'left', sortable: true, format: (val: string) => formatDateShort(val) },
  { name: 'driver', label: 'Driver', field: (row: MileageLog) => row.driver?.name || '-', align: 'left' },
  { name: 'start_odometer', label: 'Start Odo', field: 'start_odometer', align: 'right', format: (val: number) => val.toLocaleString() },
  { name: 'end_odometer', label: 'End Odo', field: 'end_odometer', align: 'right', format: (val: number) => val.toLocaleString() },
  { name: 'distance_miles', label: 'Distance', field: 'distance_miles', align: 'right', sortable: true, format: (val: number) => `${Number(val).toLocaleString()} mi` },
  { name: 'trip_type', label: 'Trip Type', field: 'trip_type', align: 'left', sortable: true },
  { name: 'from_to', label: 'From / To', field: (row: MileageLog) => [row.from_location, row.to_location].filter(Boolean).join(' \u2192 ') || '-', align: 'left' },
  { name: 'purpose', label: 'Purpose', field: 'purpose', align: 'left' }
]

watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val && props.vehicleId) {
    tab.value = 'details'
    movements.value = []
    maintenanceRecords.value = []
    mileageLogs.value = []
    loadVehicle()
  }
})

watch(isOpen, (val) => {
  emit('update:modelValue', val)
})

watch(tab, (newTab) => {
  if (newTab === 'movements' && movements.value.length === 0) {
    loadMovements()
  } else if (newTab === 'maintenance' && maintenanceRecords.value.length === 0) {
    loadMaintenance()
  } else if (newTab === 'mileage' && mileageLogs.value.length === 0) {
    loadMileage()
  }
})

async function loadVehicle(): Promise<void> {
  loading.value = true
  try {
    const response = await apiClient.get<{ success: boolean; data: Vehicle }>(`/vehicles/${props.vehicleId}`)
    vehicle.value = response.data.data
  } catch (error) {
    console.error('Failed to load vehicle:', error)
    $q.notify({ type: 'negative', message: 'Failed to load vehicle details' })
    isOpen.value = false
  } finally {
    loading.value = false
  }
}

async function loadMovements(): Promise<void> {
  loadingMovements.value = true
  try {
    const response = await apiClient.get<{ success: boolean; data: VehicleMovement[] }>(`/vehicles/${props.vehicleId}/history`)
    movements.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load movements:', error)
  } finally {
    loadingMovements.value = false
  }
}

async function loadMaintenance(): Promise<void> {
  loadingMaintenance.value = true
  try {
    const response = await apiClient.get<{ success: boolean; data: { records: MaintenanceRecord[] } }>('/maintenance-records', {
      params: {
        maintainable_type: 'App\\Models\\Vehicle',
        maintainable_id: props.vehicleId
      }
    })
    maintenanceRecords.value = response.data.data.records || []
  } catch (error) {
    console.error('Failed to load maintenance records:', error)
  } finally {
    loadingMaintenance.value = false
  }
}

async function loadMileage(): Promise<void> {
  loadingMileage.value = true
  try {
    const response = await apiClient.get<{ success: boolean; data: MileageLog[] }>(`/vehicles/${props.vehicleId}/mileage`)
    mileageLogs.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load mileage logs:', error)
  } finally {
    loadingMileage.value = false
  }
}

function onMileageSaved(): void {
  loadMileage()
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

function formatLabel(value?: string): string {
  if (!value) return 'N/A'
  return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function formatDate(date: string | undefined): string {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString()
}

function formatDateShort(date: string | undefined): string {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}

function getDueStatus(dateStr: string): { color: string; label: string } {
  const now = new Date()
  const due = new Date(dateStr)
  const diffMs = due.getTime() - now.getTime()
  const diffDays = diffMs / (1000 * 60 * 60 * 24)

  if (diffDays < 0) {
    return { color: 'negative', label: 'OVERDUE' }
  }
  if (diffDays <= 30) {
    return { color: 'warning', label: 'Due Soon' }
  }
  return { color: 'positive', label: 'OK' }
}

function getComplianceClass(dateStr: string | undefined): string {
  if (!dateStr) return ''
  const status = getDueStatus(dateStr)
  if (status.color === 'negative') return 'text-negative text-weight-bold'
  if (status.color === 'warning') return 'text-warning text-weight-bold'
  return ''
}

function getMovementTypeColor(type: string): string {
  const colors: Record<string, string> = {
    pickup: 'info',
    dropoff: 'positive',
    transfer: 'warning',
    return_to_yard: 'purple'
  }
  return colors[type] || 'grey'
}

function getCategoryColor(category: string): string {
  const colors: Record<string, string> = {
    preventive: 'positive',
    corrective: 'warning',
    inspection: 'info',
    warranty: 'purple'
  }
  return colors[category] || 'grey'
}

function getTripTypeColor(type: string): string {
  const colors: Record<string, string> = {
    business: 'primary',
    personal: 'grey',
    commute: 'teal',
    delivery: 'orange',
    service_call: 'purple'
  }
  return colors[type] || 'grey'
}

function onEdit(): void {
  if (vehicle.value) {
    emit('edit', vehicle.value)
    isOpen.value = false
  }
}
</script>
