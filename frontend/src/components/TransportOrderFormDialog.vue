<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 600px; max-width: 800px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ isEdit ? 'Edit Transport Order' : 'New Transport Order' }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pa-lg">
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
            :disable="isEdit"
          />

          <!-- Pickup Location -->
          <div class="text-subtitle2 text-grey-8">Pickup Location</div>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.pickup_location_type"
                outlined dense
                label="Location Type *"
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
              />
            </div>
          </div>

          <!-- Dropoff Location -->
          <div class="text-subtitle2 text-grey-8">Dropoff Location</div>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.dropoff_location_type"
                outlined dense
                label="Location Type *"
                :options="locationTypeOptions"
                emit-value
                map-options
                :rules="[(val: string | null) => !!val || 'Required']"
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

          <!-- Scheduling -->
          <div class="text-subtitle2 text-grey-8">Scheduling</div>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model="form.scheduled_date"
                outlined dense
                label="Date *"
                type="date"
                :rules="[(val: string) => !!val || 'Date is required']"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="form.scheduled_time"
                outlined dense
                label="Time"
                type="time"
              />
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.priority"
                outlined dense
                label="Priority"
                :options="priorityOptions"
                emit-value
                map-options
              />
            </div>
          </div>

          <!-- Driver Assignment (optional) -->
          <div class="text-subtitle2 text-grey-8">Driver Assignment (optional)</div>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-select
                v-model="form.assigned_driver_id"
                outlined dense
                label="Driver"
                :options="driverOptions"
                option-value="id"
                option-label="name"
                emit-value
                map-options
                clearable
              />
            </div>
            <div class="col-6">
              <q-select
                v-model="form.assigned_vehicle_id"
                outlined dense
                label="Vehicle"
                :options="vehicleOptions"
                option-value="id"
                option-label="name"
                emit-value
                map-options
                clearable
              />
            </div>
          </div>

          <!-- Instructions -->
          <q-input
            v-model="form.special_instructions"
            outlined dense
            type="textarea"
            rows="2"
            label="Special Instructions"
          />

          <!-- Actions -->
          <div class="row q-mt-md q-gutter-sm justify-end">
            <q-btn flat label="Cancel" color="grey-7" v-close-popup />
            <q-btn type="submit" label="Save" color="primary" :loading="saving" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
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
  (e: 'saved'): void
}>()

const $q = useQuasar()
const store = useTransportOrderStore()

const isOpen = ref(props.modelValue)
const saving = ref(false)
const isEdit = ref(false)

const equipmentOptions = ref<Array<{ id: number; name: string; equipment_number?: string }>>([])
const driverOptions = ref<Array<{ id: number; name: string }>>([])
const vehicleOptions = ref<Array<{ id: number; name: string }>>([])
const yardOptions = ref<Array<{ id: number; name: string }>>([])
const projectOptions = ref<Array<{ id: number; name: string }>>([])

const locationTypeOptions = [
  { label: 'Project', value: 'project' },
  { label: 'Yard', value: 'yard' },
  { label: 'Shop', value: 'shop' },
]

const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Normal', value: 'normal' },
  { label: 'High', value: 'high' },
  { label: 'Urgent', value: 'urgent' },
]

const form = ref({
  equipment_id: null as number | null,
  pickup_location_type: 'yard' as string,
  pickup_location_id: null as number | null,
  dropoff_location_type: 'project' as string,
  dropoff_location_id: null as number | null,
  priority: 'normal',
  scheduled_date: new Date().toISOString().split('T')[0],
  scheduled_time: '',
  special_instructions: '',
  assigned_driver_id: null as number | null,
  assigned_vehicle_id: null as number | null,
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
    if (props.order) {
      isEdit.value = true
      populateForm(props.order)
    } else {
      isEdit.value = false
      resetForm()
    }
  }
})

watch(isOpen, (val) => emit('update:modelValue', val))

function resetForm() {
  form.value = {
    equipment_id: null,
    pickup_location_type: 'yard',
    pickup_location_id: null,
    dropoff_location_type: 'project',
    dropoff_location_id: null,
    priority: 'normal',
    scheduled_date: new Date().toISOString().split('T')[0],
    scheduled_time: '',
    special_instructions: '',
    assigned_driver_id: null,
    assigned_vehicle_id: null,
  }
}

function populateForm(order: TransportOrder) {
  form.value = {
    equipment_id: order.equipment_id,
    pickup_location_type: order.pickup_location_type,
    pickup_location_id: order.pickup_location_id,
    dropoff_location_type: order.dropoff_location_type,
    dropoff_location_id: order.dropoff_location_id,
    priority: order.priority,
    scheduled_date: order.scheduled_date,
    scheduled_time: order.scheduled_time || '',
    special_instructions: order.special_instructions || '',
    assigned_driver_id: order.assigned_driver_id || null,
    assigned_vehicle_id: order.assigned_vehicle_id || null,
  }
}

async function loadOptions() {
  try {
    const [equipRes, yardRes, projRes, userRes, vehRes] = await Promise.all([
      apiClient.get('/equipment', { params: { status: 'active' } }),
      apiClient.get('/yards'),
      apiClient.get('/projects'),
      apiClient.get('/users'),
      apiClient.get('/vehicles', { params: { status: 'active' } }),
    ])

    equipmentOptions.value = (equipRes.data.data || []).map((e: any) => ({
      id: e.id, name: e.name, equipment_number: e.equipment_number,
    }))
    yardOptions.value = (yardRes.data.data || []).map((y: any) => ({ id: y.id, name: y.name }))
    projectOptions.value = (projRes.data.data || []).map((p: any) => ({ id: p.id, name: p.name }))
    driverOptions.value = (userRes.data.data || []).map((u: any) => ({ id: u.id, name: u.name }))
    vehicleOptions.value = (vehRes.data.data || [])
      .filter((v: any) => ['flatbed', 'lowboy', 'service_truck', 'dump_truck'].includes(v.vehicle_type))
      .map((v: any) => ({ id: v.id, name: v.name }))
  } catch (e) {
    console.error('Failed to load form options:', e)
  }
}

async function onSubmit() {
  saving.value = true
  try {
    if (isEdit.value && props.order) {
      await store.updateOrder(props.order.id, form.value as any)
    } else {
      await store.createOrder(form.value as any)
    }
    $q.notify({ type: 'positive', message: isEdit.value ? 'Order updated' : 'Transport order created' })
    isOpen.value = false
    emit('saved')
  } catch (e: any) {
    $q.notify({
      type: 'negative',
      message: e?.response?.data?.message || 'Failed to save transport order',
    })
  } finally {
    saving.value = false
  }
}

onMounted(loadOptions)
</script>
