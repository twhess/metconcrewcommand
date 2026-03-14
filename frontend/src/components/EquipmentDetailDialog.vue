<template>
  <q-dialog v-model="isOpen">
    <q-card style="min-width: 700px; max-width: 900px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">Equipment Details</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section v-if="loading" class="text-center q-pa-lg">
        <q-spinner color="primary" size="3em" />
      </q-card-section>

      <q-card-section v-else-if="equipment">
        <q-tabs
          v-model="tab"
          dense
          class="text-grey"
          active-color="primary"
          indicator-color="primary"
          align="justify"
        >
          <q-tab name="details" label="Details" />
          <q-tab name="location" label="Location" />
          <q-tab name="movements" label="Movement History" />
          <q-tab name="maintenance" label="Maintenance" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="tab" animated>
          <!-- Details Tab -->
          <q-tab-panel name="details">
            <div class="q-gutter-md">
              <div class="row">
                <div class="col-12">
                  <div class="text-h5">{{ equipment.name }}</div>
                  <div class="text-caption text-grey-7">{{ equipment.equipment_number }}</div>
                </div>
              </div>

              <q-separator />

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Status</div>
                  <q-badge :color="getStatusColor(equipment.status)" :label="equipment.status" />
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">Type</div>
                  <div>{{ equipment.type }}</div>
                </div>
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Category</div>
                  <div>{{ equipment.category || 'N/A' }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">QR Code</div>
                  <div class="row items-center">
                    <span class="q-mr-sm">{{ equipment.qr_code }}</span>
                    <q-btn
                      flat
                      dense
                      size="sm"
                      icon="qr_code_2"
                      @click="showQrCode"
                    >
                      <q-tooltip>View QR Code</q-tooltip>
                    </q-btn>
                  </div>
                </div>
              </div>

              <div class="row q-col-gutter-md" v-if="equipment.has_hour_meter">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Current Hours</div>
                  <div>{{ equipment.current_hours }}</div>
                </div>
                <div class="col-6" v-if="equipment.last_hours_reading_at">
                  <div class="text-caption text-grey-7">Last Reading</div>
                  <div>{{ formatDate(equipment.last_hours_reading_at) }}</div>
                </div>
              </div>

              <div v-if="equipment.description">
                <div class="text-caption text-grey-7">Description</div>
                <div>{{ equipment.description }}</div>
              </div>

              <div v-if="equipment.notes">
                <div class="text-caption text-grey-7">Notes</div>
                <div>{{ equipment.notes }}</div>
              </div>

              <div class="row q-col-gutter-md text-caption text-grey-7">
                <div class="col-6" v-if="equipment.created_at">
                  <div>Created: {{ formatDate(equipment.created_at) }}</div>
                </div>
                <div class="col-6" v-if="equipment.updated_at">
                  <div>Updated: {{ formatDate(equipment.updated_at) }}</div>
                </div>
              </div>
            </div>
          </q-tab-panel>

          <!-- Location Tab -->
          <q-tab-panel name="location">
            <div class="q-gutter-md">
              <div class="text-subtitle1">Current Location</div>

              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <div class="text-caption text-grey-7">Location Type</div>
                  <div>{{ equipment.current_location_type || 'Not set' }}</div>
                </div>
                <div class="col-6" v-if="equipment.current_location_id">
                  <div class="text-caption text-grey-7">Location ID</div>
                  <div>{{ equipment.current_location_id }}</div>
                </div>
              </div>

              <div class="row q-col-gutter-md" v-if="equipment.current_location_gps_lat && equipment.current_location_gps_lng">
                <div class="col-6">
                  <div class="text-caption text-grey-7">GPS Latitude</div>
                  <div>{{ equipment.current_location_gps_lat }}</div>
                </div>
                <div class="col-6">
                  <div class="text-caption text-grey-7">GPS Longitude</div>
                  <div>{{ equipment.current_location_gps_lng }}</div>
                </div>
              </div>
            </div>
          </q-tab-panel>

          <!-- Movement History Tab -->
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
                :title="movement.movement_type"
                :subtitle="formatDate(movement.moved_at)"
              >
                <div>
                  <div v-if="movement.from_location_type">
                    <strong>From:</strong> {{ movement.from_location_type }}
                  </div>
                  <div v-if="movement.to_location_type">
                    <strong>To:</strong> {{ movement.to_location_type }}
                  </div>
                  <div v-if="movement.hours_reading">
                    <strong>Hours:</strong> {{ movement.hours_reading }}
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
            <q-list v-else separator>
              <q-item v-for="record in maintenanceRecords" :key="record.id">
                <q-item-section>
                  <q-item-label>{{ record.maintenance_type }}</q-item-label>
                  <q-item-label caption>{{ formatDate(record.performed_at) }}</q-item-label>
                  <q-item-label caption v-if="record.description">{{ record.description }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge :color="getCategoryColor(record.category)">{{ record.category }}</q-badge>
                  <div class="text-caption" v-if="record.total_cost">
                    ${{ record.total_cost.toFixed(2) }}
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>
        </q-tab-panels>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Close" color="primary" v-close-popup />
        <q-btn
          flat
          label="Edit"
          color="primary"
          icon="edit"
          @click="onEdit"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import type { Equipment, EquipmentMovement, MaintenanceRecord } from '@/types'

interface Props {
  modelValue: boolean
  equipmentId: number | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'edit', equipment: Equipment): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const $q = useQuasar()

const isOpen = ref(props.modelValue)
const loading = ref(false)
const loadingMovements = ref(false)
const loadingMaintenance = ref(false)
const equipment = ref<Equipment | null>(null)
const movements = ref<EquipmentMovement[]>([])
const maintenanceRecords = ref<MaintenanceRecord[]>([])
const tab = ref('details')

watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val && props.equipmentId) {
    loadEquipment()
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
  }
})

async function loadEquipment() {
  loading.value = true
  try {
    const response = await apiClient.get<{ success: boolean; data: Equipment }>(`/equipment/${props.equipmentId}`)
    equipment.value = response.data.data
  } catch (error) {
    console.error('Failed to load equipment:', error)
    $q.notify({
      type: 'negative',
      message: 'Failed to load equipment details'
    })
    isOpen.value = false
  } finally {
    loading.value = false
  }
}

async function loadMovements() {
  loadingMovements.value = true
  try {
    const response = await apiClient.get<{ success: boolean; data: EquipmentMovement[] }>(`/equipment/${props.equipmentId}/movements`)
    movements.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load movements:', error)
  } finally {
    loadingMovements.value = false
  }
}

async function loadMaintenance() {
  loadingMaintenance.value = true
  try {
    const response = await apiClient.get<{ success: boolean; data: { records: MaintenanceRecord[] } }>('/maintenance/history', {
      params: {
        maintainable_type: 'App\\Models\\Equipment',
        maintainable_id: props.equipmentId
      }
    })
    maintenanceRecords.value = response.data.data.records || []
  } catch (error) {
    console.error('Failed to load maintenance:', error)
  } finally {
    loadingMaintenance.value = false
  }
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    active: 'positive',
    inactive: 'grey',
    maintenance: 'warning',
    in_transit: 'info'
  }
  return colors[status] || 'grey'
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

function formatDate(date: string | undefined): string {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString()
}

function showQrCode() {
  $q.notify({
    type: 'info',
    message: 'QR Code display - Coming soon'
  })
}

function onEdit() {
  if (equipment.value) {
    emit('edit', equipment.value)
    isOpen.value = false
  }
}
</script>
