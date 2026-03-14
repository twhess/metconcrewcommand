<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 600px; max-width: 800px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ isEditMode ? 'Edit Equipment' : 'Add Equipment' }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <!-- Basic Information -->
          <div class="text-subtitle2 text-grey-8">Basic Information</div>

          <q-input
            v-model="form.name"
            outlined
            dense
            label="Equipment Name *"
            :rules="[val => !!val || 'Name is required']"
          />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model="form.equipment_number"
                outlined
                dense
                label="Equipment Number"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="form.qr_code"
                outlined
                dense
                label="QR Code *"
                :rules="[val => !!val || 'QR Code is required']"
                :readonly="isEditMode"
              >
                <template v-slot:append v-if="!isEditMode">
                  <q-btn
                    flat
                    dense
                    icon="refresh"
                    @click="generateQrCode"
                    tooltip="Generate QR Code"
                  />
                </template>
              </q-input>
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.type"
                outlined
                dense
                label="Type *"
                :options="typeOptions"
                emit-value
                map-options
                :rules="[val => !!val || 'Type is required']"
              />
            </div>
            <div class="col-6">
              <q-select
                v-model="form.category"
                outlined
                dense
                label="Category"
                :options="categoryOptions"
                emit-value
                map-options
                clearable
              />
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.status"
                outlined
                dense
                label="Status"
                :options="statusOptions"
                emit-value
                map-options
              />
            </div>
            <div class="col-6 flex items-center">
              <q-checkbox
                v-model="form.has_hour_meter"
                label="Has Hour Meter"
              />
            </div>
          </div>

          <q-input
            v-if="form.has_hour_meter"
            v-model.number="form.current_hours"
            outlined
            dense
            type="number"
            step="0.1"
            min="0"
            label="Current Hours"
          />

          <!-- Location -->
          <div class="text-subtitle2 text-grey-8 q-mt-md">Current Location</div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.current_location_type"
                outlined
                dense
                label="Location Type"
                :options="locationTypeOptions"
                emit-value
                map-options
                clearable
              />
            </div>
            <div class="col-6" v-if="form.current_location_type && form.current_location_type !== 'in_transit'">
              <q-select
                v-model="form.current_location_id"
                outlined
                dense
                label="Location"
                :options="locationOptions"
                option-value="id"
                option-label="name"
                emit-value
                map-options
                clearable
              />
            </div>
          </div>

          <!-- Description -->
          <div class="text-subtitle2 text-grey-8 q-mt-md">Additional Information</div>

          <q-input
            v-model="form.description"
            outlined
            dense
            type="textarea"
            rows="2"
            label="Description"
          />

          <q-input
            v-model="form.notes"
            outlined
            dense
            type="textarea"
            rows="2"
            label="Notes"
          />

          <!-- Actions -->
          <div class="row q-mt-md q-gutter-sm justify-end">
            <q-btn
              flat
              label="Cancel"
              color="grey-7"
              v-close-popup
            />
            <q-btn
              type="submit"
              label="Save"
              color="primary"
              :loading="saving"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import type { Equipment } from '@/types'

interface Props {
  modelValue: boolean
  equipment?: Equipment | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'saved'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const $q = useQuasar()

const isOpen = ref(props.modelValue)
const saving = ref(false)
const isEditMode = ref(false)
const locationOptions = ref<Array<{ id: number; name: string }>>([])

const form = ref({
  name: '',
  equipment_number: '',
  qr_code: '',
  type: 'trackable' as 'trackable' | 'non_trackable',
  category: null as string | null,
  status: 'active' as string,
  current_location_type: null as string | null,
  current_location_id: null as number | null,
  has_hour_meter: false,
  current_hours: 0,
  description: '',
  notes: ''
})

const typeOptions = [
  { label: 'Trackable', value: 'trackable' },
  { label: 'Non-Trackable', value: 'non_trackable' }
]

const statusOptions = [
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

const locationTypeOptions = [
  { label: 'Yard', value: 'yard' },
  { label: 'Project', value: 'project' },
  { label: 'Shop', value: 'shop' },
  { label: 'In Transit', value: 'in_transit' }
]

watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val) {
    loadLocations()
    if (props.equipment) {
      isEditMode.value = true
      populateForm(props.equipment)
    } else {
      isEditMode.value = false
      resetForm()
    }
  }
})

watch(isOpen, (val) => {
  emit('update:modelValue', val)
})

function resetForm() {
  form.value = {
    name: '',
    equipment_number: '',
    qr_code: '',
    type: 'trackable',
    category: null,
    status: 'active',
    current_location_type: null,
    current_location_id: null,
    has_hour_meter: false,
    current_hours: 0,
    description: '',
    notes: ''
  }
}

function populateForm(equipment: Equipment) {
  form.value = {
    name: equipment.name,
    equipment_number: equipment.equipment_number || '',
    qr_code: equipment.qr_code,
    type: equipment.type,
    category: equipment.category || null,
    status: equipment.status,
    current_location_type: equipment.current_location_type || null,
    current_location_id: equipment.current_location_id || null,
    has_hour_meter: equipment.has_hour_meter,
    current_hours: equipment.current_hours,
    description: equipment.description || '',
    notes: equipment.notes || ''
  }
}

function generateQrCode() {
  form.value.qr_code = 'EQP-' + Math.random().toString(36).substring(2, 9).toUpperCase()
}

async function loadLocations() {
  try {
    const response = await apiClient.get<{ success: boolean; data: Array<{ id: number; name: string }> }>('/yards')
    locationOptions.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load locations:', error)
  }
}

async function onSubmit() {
  saving.value = true
  try {
    const payload = {
      ...form.value,
      current_hours: form.value.has_hour_meter ? form.value.current_hours : 0
    }

    if (isEditMode.value && props.equipment) {
      await apiClient.put(`/equipment/${props.equipment.id}`, payload)
      $q.notify({
        type: 'positive',
        message: 'Equipment updated successfully'
      })
    } else {
      await apiClient.post('/equipment', payload)
      $q.notify({
        type: 'positive',
        message: 'Equipment created successfully'
      })
    }

    isOpen.value = false
    emit('saved')
  } catch (error: any) {
    console.error('Failed to save equipment:', error)
    const message = error?.response?.data?.message || 'Failed to save equipment'
    $q.notify({
      type: 'negative',
      message
    })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadLocations()
})
</script>
