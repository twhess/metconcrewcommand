<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 400px; max-width: 500px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">Ad-Hoc Pickup</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pa-lg">
        <div class="text-body2 text-grey-7 q-mb-md">
          Use this for emergency moves without a dispatch order. Dispatch will be notified.
        </div>

        <q-form @submit="onSubmit" class="q-gutter-y-md">
          <!-- Equipment -->
          <q-select
            v-model="form.equipment_id"
            outlined dense
            label="Equipment *"
            :options="equipmentOptions"
            option-value="id"
            :option-label="(item: any) => item.name + (item.equipment_number ? ' (' + item.equipment_number + ')' : '')"
            emit-value
            map-options
            :rules="[(val: number | null) => !!val || 'Equipment is required']"
          />

          <!-- Pickup Location -->
          <div class="text-subtitle2 text-grey-8">Pickup From</div>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.pickup_location_type"
                outlined dense
                label="Type *"
                :options="locationTypeOptions"
                emit-value
                map-options
              />
            </div>
            <div class="col-6">
              <q-select
                v-model="form.pickup_location_id"
                outlined dense
                label="Location *"
                :options="pickupLocationOptions"
                option-value="id"
                option-label="name"
                emit-value
                map-options
                :rules="[(val: number | null) => !!val || 'Required']"
              />
            </div>
          </div>

          <!-- Dropoff Location -->
          <div class="text-subtitle2 text-grey-8">Deliver To</div>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.dropoff_location_type"
                outlined dense
                label="Type *"
                :options="locationTypeOptions"
                emit-value
                map-options
              />
            </div>
            <div class="col-6">
              <q-select
                v-model="form.dropoff_location_id"
                outlined dense
                label="Location *"
                :options="dropoffLocationOptions"
                option-value="id"
                option-label="name"
                emit-value
                map-options
                :rules="[(val: number | null) => !!val || 'Required']"
              />
            </div>
          </div>

          <!-- Hours reading -->
          <q-input
            v-model.number="form.hours_reading"
            outlined dense
            type="number"
            step="0.1"
            min="0"
            label="Hour Meter Reading (optional)"
          />

          <!-- Notes -->
          <q-input
            v-model="form.notes"
            outlined dense
            type="textarea"
            rows="2"
            label="Notes (optional)"
            placeholder="Reason for ad-hoc move, condition, etc."
          />

          <!-- GPS -->
          <div class="row items-center q-gutter-sm">
            <q-btn
              flat dense
              color="primary"
              icon="my_location"
              label="Capture GPS"
              @click="captureGps"
              :loading="capturingGps"
            />
            <span v-if="form.gps_latitude" class="text-caption text-grey-7">
              {{ form.gps_latitude.toFixed(4) }}, {{ form.gps_longitude?.toFixed(4) }}
            </span>
          </div>

          <!-- Submit -->
          <q-btn
            type="submit"
            label="CONFIRM AD-HOC PICKUP"
            color="orange"
            icon="upload"
            size="lg"
            class="full-width q-mt-md"
            padding="md"
            :loading="saving"
          />
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import { useTransportOrderStore } from '@/stores/transportOrder'

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'completed'): void
}>()

const $q = useQuasar()
const store = useTransportOrderStore()

const isOpen = ref(props.modelValue)
const saving = ref(false)
const capturingGps = ref(false)

const equipmentOptions = ref<Array<{ id: number; name: string; equipment_number?: string }>>([])
const yardOptions = ref<Array<{ id: number; name: string }>>([])
const projectOptions = ref<Array<{ id: number; name: string }>>([])

const locationTypeOptions = [
  { label: 'Project', value: 'project' },
  { label: 'Yard', value: 'yard' },
  { label: 'Shop', value: 'shop' },
]

const form = ref({
  equipment_id: null as number | null,
  pickup_location_type: 'project' as string,
  pickup_location_id: null as number | null,
  dropoff_location_type: 'project' as string,
  dropoff_location_id: null as number | null,
  hours_reading: null as number | null,
  notes: '',
  gps_latitude: null as number | null,
  gps_longitude: null as number | null,
})

const pickupLocationOptions = computed(() =>
  form.value.pickup_location_type === 'project' ? projectOptions.value : yardOptions.value
)

const dropoffLocationOptions = computed(() =>
  form.value.dropoff_location_type === 'project' ? projectOptions.value : yardOptions.value
)

watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val) {
    form.value = {
      equipment_id: null,
      pickup_location_type: 'project',
      pickup_location_id: null,
      dropoff_location_type: 'project',
      dropoff_location_id: null,
      hours_reading: null,
      notes: '',
      gps_latitude: null,
      gps_longitude: null,
    }
  }
})

watch(isOpen, (val) => emit('update:modelValue', val))

function captureGps() {
  if (!navigator.geolocation) {
    $q.notify({ type: 'warning', message: 'GPS not available on this device' })
    return
  }
  capturingGps.value = true
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      form.value.gps_latitude = pos.coords.latitude
      form.value.gps_longitude = pos.coords.longitude
      capturingGps.value = false
    },
    () => {
      $q.notify({ type: 'warning', message: 'Could not get GPS location' })
      capturingGps.value = false
    },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

async function loadOptions() {
  try {
    const [equipRes, yardRes, projRes] = await Promise.all([
      apiClient.get('/equipment', { params: { status: 'active' } }),
      apiClient.get('/yards'),
      apiClient.get('/projects'),
    ])
    equipmentOptions.value = (equipRes.data.data || []).map((e: any) => ({
      id: e.id, name: e.name, equipment_number: e.equipment_number,
    }))
    yardOptions.value = (yardRes.data.data || []).map((y: any) => ({ id: y.id, name: y.name }))
    projectOptions.value = (projRes.data.data || []).map((p: any) => ({ id: p.id, name: p.name }))
  } catch (e) {
    console.error('Failed to load options:', e)
  }
}

async function onSubmit() {
  if (!form.value.equipment_id || !form.value.pickup_location_id || !form.value.dropoff_location_id) return

  saving.value = true
  try {
    await store.adhocPickup({
      equipment_id: form.value.equipment_id,
      pickup_location_type: form.value.pickup_location_type,
      pickup_location_id: form.value.pickup_location_id,
      dropoff_location_type: form.value.dropoff_location_type,
      dropoff_location_id: form.value.dropoff_location_id,
      hours_reading: form.value.hours_reading ?? undefined,
      notes: form.value.notes || undefined,
      gps_latitude: form.value.gps_latitude ?? undefined,
      gps_longitude: form.value.gps_longitude ?? undefined,
    })
    $q.notify({ type: 'positive', message: 'Ad-hoc pickup confirmed! Equipment is now in transit.' })
    isOpen.value = false
    emit('completed')
  } catch (e: any) {
    $q.notify({
      type: 'negative',
      message: e?.response?.data?.message || 'Failed to create ad-hoc pickup',
    })
  } finally {
    saving.value = false
  }
}

onMounted(loadOptions)
</script>
