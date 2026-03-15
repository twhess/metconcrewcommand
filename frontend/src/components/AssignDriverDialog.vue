<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 400px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">Assign Driver</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pa-lg">
        <div v-if="order" class="q-mb-md">
          <div class="text-subtitle2">{{ order.equipment?.name }}</div>
          <div class="text-caption text-grey-7">{{ order.order_number }}</div>
        </div>

        <q-form @submit="onSubmit" class="q-gutter-y-md">
          <q-select
            v-model="driverId"
            outlined dense
            label="Driver *"
            :options="driverOptions"
            option-value="id"
            option-label="name"
            emit-value
            map-options
            :rules="[(val: number | null) => !!val || 'Driver is required']"
          />

          <q-select
            v-model="vehicleId"
            outlined dense
            label="Transport Vehicle *"
            :options="vehicleOptions"
            option-value="id"
            option-label="name"
            emit-value
            map-options
            :rules="[(val: number | null) => !!val || 'Vehicle is required']"
          />

          <div class="row q-mt-md q-gutter-sm justify-end">
            <q-btn flat label="Cancel" color="grey-7" v-close-popup />
            <q-btn type="submit" label="Assign" color="primary" :loading="saving" />
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
import type { TransportOrder } from '@/types'
import { useTransportOrderStore } from '@/stores/transportOrder'

interface Props {
  modelValue: boolean
  order?: TransportOrder | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'assigned'): void
}>()

const $q = useQuasar()
const store = useTransportOrderStore()

const isOpen = ref(props.modelValue)
const saving = ref(false)
const driverId = ref<number | null>(null)
const vehicleId = ref<number | null>(null)

const driverOptions = ref<Array<{ id: number; name: string }>>([])
const vehicleOptions = ref<Array<{ id: number; name: string }>>([])

watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val) {
    driverId.value = null
    vehicleId.value = null
  }
})

watch(isOpen, (val) => emit('update:modelValue', val))

async function loadOptions() {
  try {
    const [userRes, vehRes] = await Promise.all([
      apiClient.get('/users'),
      apiClient.get('/vehicles', { params: { status: 'active' } }),
    ])

    driverOptions.value = (userRes.data.data || []).map((u: any) => ({ id: u.id, name: u.name }))
    vehicleOptions.value = (vehRes.data.data || [])
      .filter((v: any) => ['flatbed', 'lowboy', 'service_truck', 'dump_truck'].includes(v.vehicle_type))
      .map((v: any) => ({ id: v.id, name: v.name }))
  } catch (e) {
    console.error('Failed to load options:', e)
  }
}

async function onSubmit() {
  if (!props.order || !driverId.value || !vehicleId.value) return

  saving.value = true
  try {
    await store.assignDriver(props.order.id, {
      assigned_driver_id: driverId.value,
      assigned_vehicle_id: vehicleId.value,
    })
    $q.notify({ type: 'positive', message: 'Driver assigned successfully' })
    isOpen.value = false
    emit('assigned')
  } catch (e: any) {
    $q.notify({
      type: 'negative',
      message: e?.response?.data?.message || 'Failed to assign driver',
    })
  } finally {
    saving.value = false
  }
}

onMounted(loadOptions)
</script>
