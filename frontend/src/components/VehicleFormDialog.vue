<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 600px; max-width: 800px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ isEditMode ? 'Edit Vehicle' : 'Add Vehicle' }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pa-lg">
        <q-form @submit="onSubmit" class="q-gutter-y-md">
          <!-- Basic Information -->
          <div class="text-subtitle2 text-grey-8">Basic Information</div>

          <q-input
            v-model="form.name"
            outlined
            dense
            label="Vehicle Name *"
            :rules="[val => !!val || 'Name is required']"
          />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model="form.vehicle_number"
                outlined
                dense
                label="Vehicle Number"
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
                v-model="form.vehicle_type"
                outlined
                dense
                label="Vehicle Type"
                :options="vehicleTypeOptions"
                emit-value
                map-options
                clearable
              />
            </div>
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
          </div>

          <!-- Identification & Registration -->
          <div class="text-subtitle2 text-grey-8 q-mt-md">Identification &amp; Registration</div>

          <div class="row q-col-gutter-md">
            <div class="col-3">
              <q-input
                v-model.number="form.year"
                outlined
                dense
                type="number"
                label="Year"
              />
            </div>
            <div class="col-3">
              <q-input
                v-model="form.make"
                outlined
                dense
                label="Make"
              />
            </div>
            <div class="col-3">
              <q-input
                v-model="form.model"
                outlined
                dense
                label="Model"
              />
            </div>
            <div class="col-3">
              <q-input
                v-model="form.color"
                outlined
                dense
                label="Color"
              />
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model="form.vin"
                outlined
                dense
                label="VIN"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="form.license_plate"
                outlined
                dense
                label="License Plate"
              />
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model="form.registration_state"
                outlined
                dense
                label="Registration State"
                maxlength="2"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="form.registration_expiration"
                outlined
                dense
                type="date"
                label="Registration Expiration"
              />
            </div>
          </div>

          <!-- Specifications -->
          <div class="text-subtitle2 text-grey-8 q-mt-md">Specifications</div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.fuel_type"
                outlined
                dense
                label="Fuel Type"
                :options="fuelTypeOptions"
                emit-value
                map-options
                clearable
              />
            </div>
            <div class="col-6">
              <q-input
                v-model.number="form.tank_capacity_gallons"
                outlined
                dense
                type="number"
                min="0"
                label="Tank Capacity (gallons)"
              />
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-4">
              <q-input
                v-model="form.weight_class"
                outlined
                dense
                label="Weight Class"
              />
            </div>
            <div class="col-4">
              <q-input
                v-model.number="form.gvwr_pounds"
                outlined
                dense
                type="number"
                min="0"
                label="GVWR (lbs)"
              />
            </div>
            <div class="col-4">
              <q-input
                v-model.number="form.towing_capacity_pounds"
                outlined
                dense
                type="number"
                min="0"
                label="Towing Capacity (lbs)"
              />
            </div>
          </div>

          <!-- Compliance -->
          <div class="text-subtitle2 text-grey-8 q-mt-md">Compliance</div>

          <div class="row q-col-gutter-md">
            <div class="col-6 flex items-center">
              <q-checkbox
                v-model="form.requires_cdl"
                label="Requires CDL"
              />
            </div>
            <div class="col-6 flex items-center">
              <q-checkbox
                v-model="form.requires_dot_inspection"
                label="Requires DOT Inspection"
              />
            </div>
          </div>

          <div class="row q-col-gutter-md" v-if="form.requires_dot_inspection">
            <div class="col-6">
              <q-input
                v-model="form.last_dot_inspection_date"
                outlined
                dense
                type="date"
                label="Last DOT Inspection Date"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="form.next_dot_inspection_due"
                outlined
                dense
                type="date"
                label="Next DOT Inspection Due"
              />
            </div>
          </div>

          <!-- Insurance -->
          <div class="text-subtitle2 text-grey-8 q-mt-md">Insurance</div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model="form.insurance_provider"
                outlined
                dense
                label="Insurance Provider"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="form.insurance_policy_number"
                outlined
                dense
                label="Insurance Policy Number"
              />
            </div>
          </div>

          <q-input
            v-model="form.insurance_expiration"
            outlined
            dense
            type="date"
            label="Insurance Expiration"
          />

          <!-- Current State -->
          <div class="text-subtitle2 text-grey-8 q-mt-md">Current State</div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model.number="form.current_odometer_miles"
                outlined
                dense
                type="number"
                min="0"
                label="Current Odometer (miles)"
              />
            </div>
            <div class="col-6">
              <q-select
                v-model="form.assigned_to_user_id"
                outlined
                dense
                label="Assigned To"
                :options="userOptions"
                option-value="id"
                option-label="name"
                emit-value
                map-options
                clearable
              />
            </div>
          </div>

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

          <!-- Additional -->
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
import type { Vehicle } from '@/types'

interface Props {
  modelValue: boolean
  vehicle?: Vehicle | null
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
const userOptions = ref<Array<{ id: number; name: string }>>([])

const form = ref({
  name: '',
  vehicle_number: '',
  qr_code: '',
  vehicle_type: null as string | null,
  status: 'active' as string,
  year: null as number | null,
  make: '',
  model: '',
  color: '',
  vin: '',
  license_plate: '',
  registration_state: '',
  registration_expiration: '',
  fuel_type: null as string | null,
  tank_capacity_gallons: null as number | null,
  weight_class: '',
  gvwr_pounds: null as number | null,
  towing_capacity_pounds: null as number | null,
  requires_cdl: false,
  requires_dot_inspection: false,
  last_dot_inspection_date: '',
  next_dot_inspection_due: '',
  insurance_provider: '',
  insurance_policy_number: '',
  insurance_expiration: '',
  current_odometer_miles: 0,
  assigned_to_user_id: null as number | null,
  current_location_type: null as string | null,
  current_location_id: null as number | null,
  description: '',
  notes: ''
})

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

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Maintenance', value: 'maintenance' },
  { label: 'Out of Service', value: 'out_of_service' },
  { label: 'In Transit', value: 'in_transit' }
]

const fuelTypeOptions = [
  { label: 'Diesel', value: 'diesel' },
  { label: 'Gasoline', value: 'gasoline' },
  { label: 'Electric', value: 'electric' },
  { label: 'Hybrid', value: 'hybrid' },
  { label: 'Propane', value: 'propane' }
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
    loadUsers()
    if (props.vehicle) {
      isEditMode.value = true
      populateForm(props.vehicle)
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
    vehicle_number: '',
    qr_code: '',
    vehicle_type: null,
    status: 'active',
    year: null,
    make: '',
    model: '',
    color: '',
    vin: '',
    license_plate: '',
    registration_state: '',
    registration_expiration: '',
    fuel_type: null,
    tank_capacity_gallons: null,
    weight_class: '',
    gvwr_pounds: null,
    towing_capacity_pounds: null,
    requires_cdl: false,
    requires_dot_inspection: false,
    last_dot_inspection_date: '',
    next_dot_inspection_due: '',
    insurance_provider: '',
    insurance_policy_number: '',
    insurance_expiration: '',
    current_odometer_miles: 0,
    assigned_to_user_id: null,
    current_location_type: null,
    current_location_id: null,
    description: '',
    notes: ''
  }
}

function populateForm(vehicle: Vehicle) {
  form.value = {
    name: vehicle.name,
    vehicle_number: vehicle.vehicle_number || '',
    qr_code: vehicle.qr_code,
    vehicle_type: vehicle.vehicle_type || null,
    status: vehicle.status,
    year: vehicle.year || null,
    make: vehicle.make || '',
    model: vehicle.model || '',
    color: vehicle.color || '',
    vin: vehicle.vin || '',
    license_plate: vehicle.license_plate || '',
    registration_state: vehicle.registration_state || '',
    registration_expiration: vehicle.registration_expiration || '',
    fuel_type: vehicle.fuel_type || null,
    tank_capacity_gallons: vehicle.tank_capacity_gallons || null,
    weight_class: vehicle.weight_class || '',
    gvwr_pounds: vehicle.gvwr_pounds || null,
    towing_capacity_pounds: vehicle.towing_capacity_pounds || null,
    requires_cdl: vehicle.requires_cdl,
    requires_dot_inspection: vehicle.requires_dot_inspection,
    last_dot_inspection_date: vehicle.last_dot_inspection_date || '',
    next_dot_inspection_due: vehicle.next_dot_inspection_due || '',
    insurance_provider: vehicle.insurance_provider || '',
    insurance_policy_number: vehicle.insurance_policy_number || '',
    insurance_expiration: vehicle.insurance_expiration || '',
    current_odometer_miles: vehicle.current_odometer_miles,
    assigned_to_user_id: vehicle.assigned_to_user_id || null,
    current_location_type: vehicle.current_location_type || null,
    current_location_id: vehicle.current_location_id || null,
    description: vehicle.description || '',
    notes: vehicle.notes || ''
  }
}

function generateQrCode() {
  form.value.qr_code = 'VEH-' + Math.random().toString(36).substring(2, 9).toUpperCase()
}

async function loadLocations() {
  try {
    const response = await apiClient.get<{ success: boolean; data: Array<{ id: number; name: string }> }>('/yards')
    locationOptions.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load locations:', error)
  }
}

async function loadUsers() {
  try {
    const response = await apiClient.get<{ success: boolean; data: Array<{ id: number; name: string }> }>('/users')
    userOptions.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load users:', error)
  }
}

async function onSubmit() {
  saving.value = true
  try {
    const payload = { ...form.value }

    if (isEditMode.value && props.vehicle) {
      await apiClient.put(`/vehicles/${props.vehicle.id}`, payload)
      $q.notify({
        type: 'positive',
        message: 'Vehicle updated successfully'
      })
    } else {
      await apiClient.post('/vehicles', payload)
      $q.notify({
        type: 'positive',
        message: 'Vehicle created successfully'
      })
    }

    isOpen.value = false
    emit('saved')
  } catch (error: any) {
    console.error('Failed to save vehicle:', error)
    const message = error?.response?.data?.message || 'Failed to save vehicle'
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
  loadUsers()
})
</script>
