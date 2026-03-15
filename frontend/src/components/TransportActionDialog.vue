<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 400px; max-width: 500px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ isPickup ? 'Confirm Pickup' : 'Confirm Drop Off' }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pa-lg">
        <!-- Order info -->
        <div v-if="order" class="q-mb-md">
          <div class="text-subtitle1 text-weight-bold">{{ order.equipment?.name }}</div>
          <div v-if="isPickup" class="text-body2 q-mt-xs">
            <q-icon name="place" color="negative" size="xs" class="q-mr-xs" />
            Picking up from: {{ formatLoc(order.pickup_location_type, order.pickup_location_id) }}
          </div>
          <div v-else class="text-body2 q-mt-xs">
            <q-icon name="flag" color="positive" size="xs" class="q-mr-xs" />
            Dropping off at: {{ formatLoc(order.dropoff_location_type, order.dropoff_location_id) }}
          </div>
        </div>

        <q-separator class="q-mb-md" />

        <q-form @submit="onSubmit" class="q-gutter-y-md">
          <!-- Hours reading (if equipment has hour meter) -->
          <q-input
            v-if="order?.equipment?.has_hour_meter"
            v-model.number="form.hours_reading"
            outlined dense
            type="number"
            step="0.1"
            min="0"
            label="Hour Meter Reading"
            :hint="'Current: ' + (order?.equipment?.current_hours ?? 'N/A')"
          />

          <!-- Notes -->
          <q-input
            v-model="form.notes"
            outlined dense
            type="textarea"
            rows="2"
            label="Notes (optional)"
            :placeholder="isPickup ? 'Condition, issues, etc.' : 'Delivery notes...'"
          />

          <!-- GPS capture -->
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
            :label="isPickup ? 'CONFIRM PICKUP' : 'CONFIRM DROP OFF'"
            :color="isPickup ? 'primary' : 'positive'"
            :icon="isPickup ? 'upload' : 'download'"
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
import { ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import type { TransportOrder } from '@/types'
import { useTransportOrderStore } from '@/stores/transportOrder'

interface Props {
  modelValue: boolean
  order?: TransportOrder | null
  action: 'pickup' | 'dropoff'
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'completed'): void
}>()

const $q = useQuasar()
const store = useTransportOrderStore()

const isOpen = ref(props.modelValue)
const saving = ref(false)
const capturingGps = ref(false)
const isPickup = ref(props.action === 'pickup')

const form = ref({
  hours_reading: null as number | null,
  notes: '',
  gps_latitude: null as number | null,
  gps_longitude: null as number | null,
})

watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val) {
    isPickup.value = props.action === 'pickup'
    form.value = { hours_reading: null, notes: '', gps_latitude: null, gps_longitude: null }
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

function formatLoc(type: string, id: number): string {
  const labels: Record<string, string> = { project: 'Project', yard: 'Yard', shop: 'Shop' }
  return `${labels[type] || type} #${id}`
}

async function onSubmit() {
  if (!props.order) return

  saving.value = true
  try {
    const payload = {
      hours_reading: form.value.hours_reading ?? undefined,
      notes: form.value.notes || undefined,
      gps_latitude: form.value.gps_latitude ?? undefined,
      gps_longitude: form.value.gps_longitude ?? undefined,
    }

    if (isPickup.value) {
      await store.executePickup(props.order.id, payload)
      $q.notify({ type: 'positive', message: 'Equipment picked up!' })
    } else {
      await store.executeDropoff(props.order.id, payload)
      $q.notify({ type: 'positive', message: 'Equipment dropped off!' })
    }

    isOpen.value = false
    emit('completed')
  } catch (e: any) {
    $q.notify({
      type: 'negative',
      message: e?.response?.data?.message || 'Action failed',
    })
  } finally {
    saving.value = false
  }
}
</script>
