<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 600px; max-width: 90vw">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ isEditMode ? 'Edit Mileage Entry' : 'Add Mileage Entry' }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pa-lg">
        <q-form @submit="onSubmit" class="q-gutter-y-md">
          <q-input
            v-model="form.trip_date"
            outlined
            dense
            type="date"
            label="Trip Date *"
            :rules="[val => !!val || 'Trip date is required']"
          />

          <q-select
            v-model="form.driver_user_id"
            outlined
            dense
            label="Driver *"
            :options="userOptions"
            option-value="id"
            option-label="name"
            emit-value
            map-options
            :rules="[val => !!val || 'Driver is required']"
          />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model.number="form.start_odometer"
                outlined
                dense
                type="number"
                label="Start Odometer *"
                :rules="[val => val !== null && val !== '' || 'Start odometer is required']"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model.number="form.end_odometer"
                outlined
                dense
                type="number"
                label="End Odometer *"
                :rules="[
                  val => val !== null && val !== '' || 'End odometer is required',
                  val => val >= form.start_odometer || 'Must be >= start odometer'
                ]"
              />
            </div>
          </div>

          <q-input
            :model-value="distanceMiles"
            outlined
            dense
            readonly
            label="Distance (miles)"
          />

          <q-select
            v-model="form.trip_type"
            outlined
            dense
            label="Trip Type *"
            :options="tripTypeOptions"
            emit-value
            map-options
            :rules="[val => !!val || 'Trip type is required']"
          />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model="form.from_location"
                outlined
                dense
                label="From Location"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="form.to_location"
                outlined
                dense
                label="To Location"
              />
            </div>
          </div>

          <q-select
            v-model="form.project_id"
            outlined
            dense
            label="Project"
            :options="projectOptions"
            option-value="id"
            option-label="name"
            emit-value
            map-options
            clearable
          />

          <q-input
            v-model="form.purpose"
            outlined
            dense
            type="textarea"
            rows="2"
            label="Purpose"
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
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import type { MileageLog } from '@/types'

interface Props {
  modelValue: boolean
  vehicleId: number
  mileageLog?: MileageLog | null
  currentOdometer?: number
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
const userOptions = ref<Array<{ id: number; name: string }>>([])
const projectOptions = ref<Array<{ id: number; name: string }>>([])

const form = ref({
  driver_user_id: null as number | null,
  trip_date: '',
  start_odometer: 0,
  end_odometer: 0,
  trip_type: null as string | null,
  from_location: '',
  to_location: '',
  project_id: null as number | null,
  purpose: '',
  notes: ''
})

const tripTypeOptions = [
  { label: 'Business', value: 'business' },
  { label: 'Personal', value: 'personal' },
  { label: 'Commute', value: 'commute' },
  { label: 'Delivery', value: 'delivery' },
  { label: 'Service Call', value: 'service_call' }
]

const distanceMiles = computed(() => {
  const diff = (form.value.end_odometer || 0) - (form.value.start_odometer || 0)
  return diff >= 0 ? diff : 0
})

watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val) {
    loadUsers()
    loadProjects()
    if (props.mileageLog) {
      isEditMode.value = true
      populateForm(props.mileageLog)
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
    driver_user_id: null,
    trip_date: '',
    start_odometer: props.currentOdometer ?? 0,
    end_odometer: 0,
    trip_type: null,
    from_location: '',
    to_location: '',
    project_id: null,
    purpose: '',
    notes: ''
  }
}

function populateForm(log: MileageLog) {
  form.value = {
    driver_user_id: log.driver_user_id,
    trip_date: log.trip_date,
    start_odometer: log.start_odometer,
    end_odometer: log.end_odometer,
    trip_type: log.trip_type,
    from_location: log.from_location || '',
    to_location: log.to_location || '',
    project_id: log.project_id || null,
    purpose: log.purpose || '',
    notes: log.notes || ''
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

async function loadProjects() {
  try {
    const response = await apiClient.get<{ success: boolean; data: Array<{ id: number; name: string }> }>('/projects')
    projectOptions.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load projects:', error)
  }
}

async function onSubmit() {
  saving.value = true
  try {
    const payload = {
      driver_user_id: form.value.driver_user_id,
      trip_date: form.value.trip_date,
      start_odometer: form.value.start_odometer,
      end_odometer: form.value.end_odometer,
      trip_type: form.value.trip_type,
      from_location: form.value.from_location || null,
      to_location: form.value.to_location || null,
      project_id: form.value.project_id || null,
      purpose: form.value.purpose || null,
      notes: form.value.notes || null
    }

    if (isEditMode.value && props.mileageLog) {
      await apiClient.put(`/mileage-logs/${props.mileageLog.id}`, payload)
      $q.notify({
        type: 'positive',
        message: 'Mileage entry updated successfully'
      })
    } else {
      await apiClient.post(`/vehicles/${props.vehicleId}/mileage`, payload)
      $q.notify({
        type: 'positive',
        message: 'Mileage entry created successfully'
      })
    }

    isOpen.value = false
    emit('saved')
  } catch (error: any) {
    console.error('Failed to save mileage entry:', error)
    const message = error?.response?.data?.message || 'Failed to save mileage entry'
    $q.notify({
      type: 'negative',
      message
    })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadUsers()
  loadProjects()
})
</script>
