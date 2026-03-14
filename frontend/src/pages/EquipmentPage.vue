<template>
  <q-page class="q-pa-md">
    <div class="row q-mb-md items-center">
      <div class="col">
        <h4 class="q-my-none">Equipment</h4>
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
          label="Add Equipment"
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
              placeholder="Search equipment..."
              @update:model-value="loadEquipment"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.status"
              outlined
              dense
              label="Status"
              :options="statusOptions"
              emit-value
              map-options
              @update:model-value="loadEquipment"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.category"
              outlined
              dense
              label="Category"
              :options="categoryOptions"
              emit-value
              map-options
              clearable
              @update:model-value="loadEquipment"
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
    <div v-else-if="equipment.length === 0" class="text-center q-pa-lg">
      <q-icon name="construction" size="4em" color="grey-5" />
      <p class="text-grey-7">No equipment found</p>
    </div>

    <!-- Card View -->
    <div v-else-if="viewMode === 'cards'" class="row q-col-gutter-md">
      <div
        v-for="item in equipment"
        :key="item.id"
        class="col-12 col-sm-6 col-md-4 col-lg-3"
      >
        <q-card>
          <q-card-section>
            <div class="row items-center q-mb-sm">
              <div class="col">
                <div class="text-h6">{{ item.name }}</div>
                <div class="text-caption text-grey-7">{{ item.equipment_number }}</div>
              </div>
              <div class="col-auto">
                <q-badge
                  :color="getStatusColor(item.status)"
                  :label="item.status"
                />
              </div>
            </div>

            <q-separator class="q-my-sm" />

            <div class="text-body2 q-mb-xs">
              <q-icon name="category" size="sm" class="q-mr-xs" />
              <strong>Category:</strong> {{ item.category || 'N/A' }}
            </div>

            <div class="text-body2 q-mb-xs">
              <q-icon name="qr_code_2" size="sm" class="q-mr-xs" />
              <strong>QR Code:</strong> {{ item.qr_code }}
            </div>

            <div v-if="item.has_hour_meter" class="text-body2 q-mb-xs">
              <q-icon name="schedule" size="sm" class="q-mr-xs" />
              <strong>Hours:</strong> {{ item.current_hours }}
            </div>

            <div class="text-body2 q-mb-xs">
              <q-icon name="location_on" size="sm" class="q-mr-xs" />
              <strong>Location:</strong> {{ formatLocation(item) }}
            </div>

            <div v-if="item.description" class="text-caption text-grey-7 q-mt-sm">
              {{ item.description }}
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
              @click="moveEquipment(item)"
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
                  <q-item clickable v-close-popup @click="editEquipment(item)">
                    <q-item-section avatar>
                      <q-icon name="edit" />
                    </q-item-section>
                    <q-item-section>Edit</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="viewHistory(item)">
                    <q-item-section avatar>
                      <q-icon name="history" />
                    </q-item-section>
                    <q-item-section>History</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="viewMaintenance(item)">
                    <q-item-section avatar>
                      <q-icon name="build" />
                    </q-item-section>
                    <q-item-section>Maintenance</q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item clickable v-close-popup @click="deleteEquipment(item)">
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
      :rows="equipment"
      :columns="columns"
      row-key="id"
      flat
      bordered
      :pagination="{ rowsPerPage: 25 }"
    >
      <template v-slot:body-cell-name="props">
        <q-td :props="props">
          <div class="text-weight-medium">{{ props.row.name }}</div>
          <div class="text-caption text-grey-7">{{ props.row.equipment_number }}</div>
        </q-td>
      </template>

      <template v-slot:body-cell-status="props">
        <q-td :props="props">
          <q-badge
            :color="getStatusColor(props.row.status)"
            :label="props.row.status"
          />
        </q-td>
      </template>

      <template v-slot:body-cell-location="props">
        <q-td :props="props">
          {{ formatLocation(props.row) }}
        </q-td>
      </template>

      <template v-slot:body-cell-hours="props">
        <q-td :props="props">
          {{ props.row.has_hour_meter ? props.row.current_hours : 'N/A' }}
        </q-td>
      </template>

      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn
            flat
            dense
            round
            icon="visibility"
            color="primary"
            @click="viewDetails(props.row)"
          >
            <q-tooltip>View Details</q-tooltip>
          </q-btn>
          <q-btn
            flat
            dense
            round
            icon="edit"
            color="secondary"
            @click="editEquipment(props.row)"
          >
            <q-tooltip>Edit</q-tooltip>
          </q-btn>
          <q-btn
            flat
            dense
            round
            icon="local_shipping"
            color="info"
            @click="moveEquipment(props.row)"
          >
            <q-tooltip>Move</q-tooltip>
          </q-btn>
          <q-btn
            flat
            dense
            round
            icon="delete"
            color="negative"
            @click="deleteEquipment(props.row)"
          >
            <q-tooltip>Delete</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>

    <!-- Equipment Form Dialog -->
    <EquipmentFormDialog
      v-model="showFormDialog"
      :equipment="selectedEquipment"
      @saved="onEquipmentSaved"
    />

    <!-- Equipment Detail Dialog -->
    <EquipmentDetailDialog
      v-model="showDetailDialog"
      :equipment-id="selectedEquipmentId"
      @edit="onEditFromDetail"
    />
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import type { Equipment } from '@/types'
import EquipmentFormDialog from '@/components/EquipmentFormDialog.vue'
import EquipmentDetailDialog from '@/components/EquipmentDetailDialog.vue'

const router = useRouter()
const $q = useQuasar()

// State
const equipment = ref<Equipment[]>([])
const loading = ref(false)
const showFormDialog = ref(false)
const showDetailDialog = ref(false)
const selectedEquipment = ref<Equipment | null>(null)
const selectedEquipmentId = ref<number | null>(null)
const viewMode = ref<'cards' | 'table'>('cards')

// Filters
const filters = ref({
  search: '',
  status: 'all',
  category: null as string | null
})

const statusOptions = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Maintenance', value: 'maintenance' },
  { label: 'In Transit', value: 'in_transit' }
]

const categoryOptions = [
  { label: 'Excavator', value: 'excavator' },
  { label: 'Skid Steer', value: 'skid_steer' },
  { label: 'Compactor', value: 'compactor' },
  { label: 'Generator', value: 'generator' },
  { label: 'Concrete Tools', value: 'concrete_tools' },
  { label: 'Air Compressor', value: 'air_compressor' },
  { label: 'Pump', value: 'pump' },
  { label: 'Small Tools', value: 'small_tools' }
]

const columns = [
  {
    name: 'name',
    label: 'Equipment',
    align: 'left',
    field: 'name',
    sortable: true
  },
  {
    name: 'status',
    label: 'Status',
    align: 'center',
    field: 'status',
    sortable: true
  },
  {
    name: 'category',
    label: 'Category',
    align: 'left',
    field: 'category',
    sortable: true
  },
  {
    name: 'qr_code',
    label: 'QR Code',
    align: 'left',
    field: 'qr_code',
    sortable: true
  },
  {
    name: 'location',
    label: 'Location',
    align: 'left',
    field: 'current_location_type',
    sortable: true
  },
  {
    name: 'hours',
    label: 'Hours',
    align: 'center',
    field: 'current_hours',
    sortable: true
  },
  {
    name: 'actions',
    label: 'Actions',
    align: 'center',
    field: 'id',
    sortable: false
  }
]

// Methods
async function loadEquipment() {
  loading.value = true
  try {
    const params: Record<string, string> = {}

    if (filters.value.search) {
      params.search = filters.value.search
    }
    if (filters.value.status && filters.value.status !== 'all') {
      params.status = filters.value.status
    }
    if (filters.value.category) {
      params.category = filters.value.category
    }

    const response = await apiClient.get<{ success: boolean; data: Equipment[] }>('/equipment', { params })
    equipment.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load equipment:', error)
    $q.notify({
      type: 'negative',
      message: 'Failed to load equipment'
    })
  } finally {
    loading.value = false
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

function formatLocation(item: Equipment): string {
  if (!item.current_location_type) {
    return 'Not set'
  }

  if (item.current_location_type === 'in_transit') {
    return 'In Transit'
  }

  // You could load and display actual location names here
  return item.current_location_type.replace('_', ' ').toUpperCase()
}

function openAddDialog() {
  selectedEquipment.value = null
  showFormDialog.value = true
}

function viewDetails(item: Equipment) {
  selectedEquipmentId.value = item.id
  showDetailDialog.value = true
}

function moveEquipment(item: Equipment) {
  router.push({ name: 'EquipmentMovement', query: { id: item.id } })
}

function editEquipment(item: Equipment) {
  selectedEquipment.value = item
  showFormDialog.value = true
}

function onEditFromDetail(item: Equipment) {
  selectedEquipment.value = item
  showFormDialog.value = true
}

function viewHistory(item: Equipment) {
  selectedEquipmentId.value = item.id
  showDetailDialog.value = true
  // The detail dialog will automatically show movement history
}

function viewMaintenance(item: Equipment) {
  selectedEquipmentId.value = item.id
  showDetailDialog.value = true
  // The detail dialog will automatically show maintenance tab
}

function onEquipmentSaved() {
  loadEquipment()
  selectedEquipment.value = null
}

function deleteEquipment(item: Equipment) {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete ${item.name}?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await apiClient.delete(`/equipment/${item.id}`)
      $q.notify({
        type: 'positive',
        message: 'Equipment deleted successfully'
      })
      loadEquipment()
    } catch (error) {
      console.error('Failed to delete equipment:', error)
      $q.notify({
        type: 'negative',
        message: 'Failed to delete equipment'
      })
    }
  })
}

// Lifecycle
onMounted(() => {
  loadEquipment()
})
</script>

<style scoped>
.q-page {
  max-width: 1400px;
  margin: 0 auto;
}
</style>
