<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 600px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ dialogTitle }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <q-input
            v-model="formData.location_name"
            label="Location Name *"
            :rules="[val => !!val || 'Location name is required']"
            outlined
            dense
          />

          <q-select
            v-model="formData.location_type"
            :options="locationTypes"
            label="Location Type"
            outlined
            dense
            emit-value
            map-options
          />

          <q-checkbox
            v-model="formData.is_primary"
            label="Primary Location"
            :disable="isOnlyLocation"
          >
            <q-tooltip v-if="isOnlyLocation">This is the only location</q-tooltip>
          </q-checkbox>

          <div class="text-subtitle2 q-mt-md">Address</div>

          <q-input
            v-model="formData.address_line1"
            label="Address Line 1"
            outlined
            dense
          />

          <q-input
            v-model="formData.address_line2"
            label="Address Line 2"
            outlined
            dense
          />

          <div class="row q-col-gutter-sm">
            <div class="col-5">
              <q-input
                v-model="formData.city"
                label="City"
                outlined
                dense
              />
            </div>
            <div class="col-3">
              <q-select
                v-model="formData.state"
                :options="stateOptions"
                label="State"
                outlined
                dense
                emit-value
                map-options
              />
            </div>
            <div class="col-4">
              <q-input
                v-model="formData.zip"
                label="ZIP Code"
                outlined
                dense
                mask="#####"
              />
            </div>
          </div>

          <q-input
            v-model="formData.phone"
            label="Phone"
            outlined
            dense
            mask="(###) ###-####"
            unmasked-value
          />

          <q-input
            v-model="formData.email"
            label="Email"
            type="email"
            outlined
            dense
          />

          <q-input
            v-model="formData.hours_of_operation"
            label="Hours of Operation"
            outlined
            dense
            placeholder="e.g., Mon-Fri 8am-5pm"
          />

          <q-input
            v-model="formData.notes"
            label="Notes"
            type="textarea"
            outlined
            rows="3"
          />

          <div class="row q-mt-md q-gutter-sm justify-end">
            <q-btn label="Cancel" color="grey" flat v-close-popup />
            <q-btn
              label="Save"
              type="submit"
              color="primary"
              :loading="loading"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useCompanyLocationStore } from '@/stores/companyLocation'
import type { CompanyLocation } from '@/types'

interface Props {
  modelValue: boolean
  companyId: number
  location?: CompanyLocation | null
  existingLocations?: CompanyLocation[]
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'saved'): void
}

const props = withDefaults(defineProps<Props>(), {
  location: null,
  existingLocations: () => []
})

const emit = defineEmits<Emits>()

const $q = useQuasar()
const locationStore = useCompanyLocationStore()

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const dialogTitle = computed(() =>
  props.location ? 'Edit Location' : 'Add Location'
)

const isOnlyLocation = computed(() =>
  !props.location && props.existingLocations.length === 0
)

const loading = ref(false)

const formData = ref({
  company_id: props.companyId,
  location_name: '',
  location_type: null as string | null,
  is_primary: false,
  address_line1: '',
  address_line2: '',
  city: '',
  state: '',
  zip: '',
  phone: '',
  email: '',
  hours_of_operation: '',
  notes: ''
})

const locationTypes = [
  { label: 'Headquarters', value: 'headquarters' },
  { label: 'Branch Office', value: 'branch' },
  { label: 'Warehouse', value: 'warehouse' },
  { label: 'Job Site', value: 'job_site' },
  { label: 'Other', value: 'other' }
]

const stateOptions = [
  { label: 'AL', value: 'AL' }, { label: 'AK', value: 'AK' }, { label: 'AZ', value: 'AZ' },
  { label: 'AR', value: 'AR' }, { label: 'CA', value: 'CA' }, { label: 'CO', value: 'CO' },
  { label: 'CT', value: 'CT' }, { label: 'DE', value: 'DE' }, { label: 'FL', value: 'FL' },
  { label: 'GA', value: 'GA' }, { label: 'HI', value: 'HI' }, { label: 'ID', value: 'ID' },
  { label: 'IL', value: 'IL' }, { label: 'IN', value: 'IN' }, { label: 'IA', value: 'IA' },
  { label: 'KS', value: 'KS' }, { label: 'KY', value: 'KY' }, { label: 'LA', value: 'LA' },
  { label: 'ME', value: 'ME' }, { label: 'MD', value: 'MD' }, { label: 'MA', value: 'MA' },
  { label: 'MI', value: 'MI' }, { label: 'MN', value: 'MN' }, { label: 'MS', value: 'MS' },
  { label: 'MO', value: 'MO' }, { label: 'MT', value: 'MT' }, { label: 'NE', value: 'NE' },
  { label: 'NV', value: 'NV' }, { label: 'NH', value: 'NH' }, { label: 'NJ', value: 'NJ' },
  { label: 'NM', value: 'NM' }, { label: 'NY', value: 'NY' }, { label: 'NC', value: 'NC' },
  { label: 'ND', value: 'ND' }, { label: 'OH', value: 'OH' }, { label: 'OK', value: 'OK' },
  { label: 'OR', value: 'OR' }, { label: 'PA', value: 'PA' }, { label: 'RI', value: 'RI' },
  { label: 'SC', value: 'SC' }, { label: 'SD', value: 'SD' }, { label: 'TN', value: 'TN' },
  { label: 'TX', value: 'TX' }, { label: 'UT', value: 'UT' }, { label: 'VT', value: 'VT' },
  { label: 'VA', value: 'VA' }, { label: 'WA', value: 'WA' }, { label: 'WV', value: 'WV' },
  { label: 'WI', value: 'WI' }, { label: 'WY', value: 'WY' }
]

watch(() => props.location, (newLocation) => {
  if (newLocation) {
    formData.value = {
      company_id: props.companyId,
      location_name: newLocation.location_name || '',
      location_type: newLocation.location_type || null,
      is_primary: newLocation.is_primary || false,
      address_line1: newLocation.address_line1 || '',
      address_line2: newLocation.address_line2 || '',
      city: newLocation.city || '',
      state: newLocation.state || '',
      zip: newLocation.zip || '',
      phone: newLocation.phone || '',
      email: newLocation.email || '',
      hours_of_operation: newLocation.hours_of_operation || '',
      notes: newLocation.notes || ''
    }
  } else {
    resetForm()
  }
}, { immediate: true })

// If this is the first location, automatically set it as primary
watch(() => props.existingLocations, (locations) => {
  if (!props.location && locations.length === 0) {
    formData.value.is_primary = true
  }
}, { immediate: true })

function resetForm() {
  formData.value = {
    company_id: props.companyId,
    location_name: '',
    location_type: null,
    is_primary: props.existingLocations.length === 0,
    address_line1: '',
    address_line2: '',
    city: '',
    state: '',
    zip: '',
    phone: '',
    email: '',
    hours_of_operation: '',
    notes: ''
  }
}

async function onSubmit() {
  loading.value = true
  try {
    if (props.location?.id) {
      await locationStore.updateLocation(props.location.id, formData.value)
      $q.notify({
        type: 'positive',
        message: 'Location updated successfully'
      })
    } else {
      await locationStore.createLocation(formData.value)
      $q.notify({
        type: 'positive',
        message: 'Location added successfully'
      })
    }
    emit('saved')
    isOpen.value = false
    resetForm()
  } catch (error: any) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to save location'
    })
  } finally {
    loading.value = false
  }
}
</script>
