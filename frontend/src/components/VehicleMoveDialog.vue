<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 500px; max-width: 90vw">
      <q-card-section class="row items-center q-pb-none">
        <div>
          <div class="text-h6">Move Vehicle</div>
          <div v-if="vehicle" class="text-subtitle2 text-grey-7">{{ vehicle.name }}</div>
        </div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pa-lg">
        <q-form @submit="onSubmit" class="q-gutter-y-md">
          <q-select
            v-model="form.movement_type"
            outlined
            dense
            label="Movement Type *"
            :options="movementTypeOptions"
            emit-value
            map-options
            :rules="[val => !!val || 'Movement type is required']"
          />

          <q-select
            v-model="form.location_type"
            outlined
            dense
            label="Location Type *"
            :options="locationTypeOptions"
            emit-value
            map-options
            :rules="[val => !!val || 'Location type is required']"
          />

          <q-select
            v-if="form.location_type && form.location_type !== 'in_transit'"
            v-model="form.location_id"
            outlined
            dense
            label="Location *"
            :options="locationOptions"
            option-value="id"
            option-label="name"
            emit-value
            map-options
            :rules="[val => !!val || 'Location is required']"
          />

          <q-input
            v-model.number="form.odometer_reading"
            outlined
            dense
            type="number"
            min="0"
            label="Odometer Reading"
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
              label="Move"
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
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import type { Vehicle } from '@/types'

interface Props {
  modelValue: boolean
  vehicle: Vehicle | null
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'moved'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const $q = useQuasar()

const isOpen = ref(props.modelValue)
const saving = ref(false)
const locationOptions = ref<Array<{ id: number; name: string }>>([])

const form = ref({
  movement_type: null as string | null,
  location_type: null as string | null,
  location_id: null as number | null,
  odometer_reading: null as number | null,
  notes: ''
})

const movementTypeOptions = [
  { label: 'Pickup', value: 'pickup' },
  { label: 'Dropoff', value: 'dropoff' },
  { label: 'Transfer', value: 'transfer' },
  { label: 'Return to Yard', value: 'return_to_yard' }
]

const locationTypeOptions = [
  { label: 'Project', value: 'project' },
  { label: 'Yard', value: 'yard' },
  { label: 'Shop', value: 'shop' },
  { label: 'Vendor', value: 'vendor' },
  { label: 'In Transit', value: 'in_transit' }
]

watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val) {
    resetForm()
    loadLocations()
  }
})

watch(isOpen, (val) => {
  emit('update:modelValue', val)
})

watch(() => form.value.location_type, () => {
  form.value.location_id = null
  loadLocations()
})

function resetForm() {
  form.value = {
    movement_type: null,
    location_type: null,
    location_id: null,
    odometer_reading: null,
    notes: ''
  }
}

async function loadLocations() {
  if (!form.value.location_type || form.value.location_type === 'in_transit') {
    locationOptions.value = []
    return
  }
  try {
    const response = await apiClient.get<{ success: boolean; data: Array<{ id: number; name: string }> }>('/yards')
    locationOptions.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load locations:', error)
  }
}

async function onSubmit() {
  if (!props.vehicle) return

  saving.value = true
  try {
    await apiClient.post(`/vehicles/${props.vehicle.id}/move`, {
      movement_type: form.value.movement_type,
      location_type: form.value.location_type,
      location_id: form.value.location_id,
      odometer_reading: form.value.odometer_reading,
      notes: form.value.notes
    })

    $q.notify({
      type: 'positive',
      message: 'Vehicle moved successfully'
    })

    isOpen.value = false
    emit('moved')
  } catch (error: any) {
    console.error('Failed to move vehicle:', error)
    const message = error?.response?.data?.message || 'Failed to move vehicle'
    $q.notify({
      type: 'negative',
      message
    })
  } finally {
    saving.value = false
  }
}
</script>
