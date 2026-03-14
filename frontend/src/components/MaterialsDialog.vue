<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" persistent>
    <q-card style="min-width: 700px; max-width: 900px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">Manage Materials - {{ schedule.project?.name }}</div>
        <q-space />
        <q-btn icon="close" flat round dense @click="closeDialog" />
      </q-card-section>

      <q-card-section>
        <div class="text-caption text-grey-7 q-mb-md">
          Date: {{ formatDate(schedule.date) }}
        </div>

        <!-- Material Type Tabs -->
        <q-tabs v-model="activeTab" dense class="text-grey" active-color="primary" indicator-color="primary" align="justify">
          <q-tab name="concrete" label="Concrete" />
          <q-tab name="gravel" label="Gravel" />
          <q-tab name="other" label="Other Materials" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="activeTab" animated>
          <!-- Concrete Tab -->
          <q-tab-panel name="concrete">
            <div class="material-form">
              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    v-model.number="concreteForm.quantity"
                    label="Quantity *"
                    type="number"
                    dense
                    outlined
                    :rules="[val => val > 0 || 'Quantity must be greater than 0']"
                  />
                </div>
                <div class="col-6">
                  <q-select
                    v-model="concreteForm.unit"
                    :options="['Cubic Yards', 'Cubic Meters', 'Tons']"
                    label="Unit *"
                    dense
                    outlined
                  />
                </div>
                <div class="col-6">
                  <q-input
                    v-model.number="concreteForm.yards_per_hour"
                    label="Yards per Hour (Pour Rate)"
                    type="number"
                    dense
                    outlined
                    suffix="CY/hr"
                    hint="Concrete pour rate in cubic yards per hour"
                  />
                </div>
                <div class="col-6">
                  <q-input
                    v-model="concreteForm.additives"
                    label="Additives"
                    dense
                    outlined
                    hint="e.g., Fiber, Accelerator, Air Entrainment"
                  />
                </div>
                <div class="col-6">
                  <q-input
                    v-model="concreteForm.dispatch_number"
                    label="Dispatch Number"
                    dense
                    outlined
                  />
                </div>
                <div class="col-6">
                  <q-input
                    v-model="concreteForm.dispatch_phone"
                    label="Dispatch Phone"
                    dense
                    outlined
                    type="tel"
                  />
                </div>
                <div class="col-12">
                  <q-input
                    v-model="concreteForm.special_instructions"
                    label="Special Instructions"
                    type="textarea"
                    rows="2"
                    dense
                    outlined
                    hint="Delivery time, location, etc."
                  />
                </div>
              </div>
              <div class="q-mt-md">
                <q-btn color="primary" label="Add Concrete" @click="addMaterial('concrete')" :disable="!isConcreteFormValid" />
              </div>
            </div>
          </q-tab-panel>

          <!-- Gravel Tab -->
          <q-tab-panel name="gravel">
            <div class="material-form">
              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    v-model.number="gravelForm.quantity"
                    label="Quantity *"
                    type="number"
                    dense
                    outlined
                    :rules="[val => val > 0 || 'Quantity must be greater than 0']"
                  />
                </div>
                <div class="col-6">
                  <q-select
                    v-model="gravelForm.unit"
                    :options="['Tons', 'Cubic Yards', 'Cubic Meters', 'Loads']"
                    label="Unit *"
                    dense
                    outlined
                  />
                </div>
                <div class="col-6">
                  <q-input
                    v-model="gravelForm.dispatch_number"
                    label="Dispatch Number"
                    dense
                    outlined
                  />
                </div>
                <div class="col-6">
                  <q-input
                    v-model="gravelForm.dispatch_phone"
                    label="Dispatch Phone"
                    dense
                    outlined
                    type="tel"
                  />
                </div>
                <div class="col-12">
                  <q-input
                    v-model="gravelForm.special_instructions"
                    label="Special Instructions"
                    type="textarea"
                    rows="2"
                    dense
                    outlined
                    hint="Delivery time, location, gravel type, etc."
                  />
                </div>
              </div>
              <div class="q-mt-md">
                <q-btn color="primary" label="Add Gravel" @click="addMaterial('gravel')" :disable="!isGravelFormValid" />
              </div>
            </div>
          </q-tab-panel>

          <!-- Other Materials Tab -->
          <q-tab-panel name="other">
            <div class="material-form">
              <div class="row q-col-gutter-md">
                <div class="col-6">
                  <q-input
                    v-model.number="otherForm.quantity"
                    label="Quantity *"
                    type="number"
                    dense
                    outlined
                    :rules="[val => val > 0 || 'Quantity must be greater than 0']"
                  />
                </div>
                <div class="col-6">
                  <q-input
                    v-model="otherForm.unit"
                    label="Unit *"
                    dense
                    outlined
                    hint="e.g., Bags, Gallons, Pallets"
                  />
                </div>
                <div class="col-6">
                  <q-input
                    v-model="otherForm.dispatch_number"
                    label="Dispatch/Order Number"
                    dense
                    outlined
                  />
                </div>
                <div class="col-6">
                  <q-input
                    v-model="otherForm.dispatch_phone"
                    label="Contact Phone"
                    dense
                    outlined
                    type="tel"
                  />
                </div>
                <div class="col-12">
                  <q-input
                    v-model="otherForm.special_instructions"
                    label="Special Instructions"
                    type="textarea"
                    rows="2"
                    dense
                    outlined
                    hint="Material description, delivery details, etc."
                  />
                </div>
              </div>
              <div class="q-mt-md">
                <q-btn color="primary" label="Add Material" @click="addMaterial('other')" :disable="!isOtherFormValid" />
              </div>
            </div>
          </q-tab-panel>
        </q-tab-panels>

        <q-separator class="q-my-md" />

        <!-- Scheduled Materials List -->
        <div class="text-subtitle2 q-mb-sm">Scheduled Deliveries</div>

        <div v-if="scheduledMaterials.length === 0" class="text-caption text-grey-6 q-pa-md text-center">
          No materials scheduled yet
        </div>

        <div v-else class="materials-list q-gutter-sm">
          <q-card
            v-for="material in scheduledMaterials"
            :key="material.id"
            flat
            bordered
          >
            <q-card-section class="q-pa-md">
              <div class="row items-center">
                <div class="col">
                  <div class="text-subtitle1 text-weight-medium">
                    {{ capitalize(material.type) }}
                    <q-chip size="sm" color="grey-4" text-color="dark">
                      {{ material.quantity }} {{ material.unit }}
                    </q-chip>
                    <q-chip v-if="material.yards_per_hour" size="sm" color="primary" text-color="white">
                      {{ material.yards_per_hour }} CY/hr
                    </q-chip>
                  </div>
                  <div v-if="material.dispatch_number || material.dispatch_phone" class="text-caption text-grey-7 q-mt-xs">
                    <span v-if="material.dispatch_number">Dispatch: {{ material.dispatch_number }}</span>
                    <a v-if="material.dispatch_phone" :href="`tel:${material.dispatch_phone}`" class="q-ml-sm">
                      {{ material.dispatch_phone }}
                    </a>
                  </div>
                  <div v-if="material.additives" class="text-caption text-grey-7 q-mt-xs">
                    Additives: {{ material.additives }}
                  </div>
                  <div v-if="material.special_instructions" class="text-caption text-grey-7 q-mt-xs">
                    {{ material.special_instructions }}
                  </div>
                </div>
                <div class="col-auto">
                  <q-btn
                    flat
                    round
                    dense
                    icon="delete"
                    color="negative"
                    @click="deleteMaterial(material.id)"
                    :disable="saving"
                  >
                    <q-tooltip>Delete Material</q-tooltip>
                  </q-btn>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Close" @click="closeDialog" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import type { Schedule, Material } from '@/types'

interface Props {
  modelValue: boolean
  schedule: Schedule
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'updated': []
}>()

const $q = useQuasar()

const activeTab = ref<'concrete' | 'gravel' | 'other'>('concrete')
const saving = ref(false)

const concreteForm = reactive({
  quantity: null as number | null,
  unit: 'Cubic Yards',
  yards_per_hour: null as number | null,
  additives: '',
  dispatch_number: '',
  dispatch_phone: '',
  special_instructions: '',
})

const gravelForm = reactive({
  quantity: null as number | null,
  unit: 'Tons',
  dispatch_number: '',
  dispatch_phone: '',
  special_instructions: '',
})

const otherForm = reactive({
  quantity: null as number | null,
  unit: '',
  dispatch_number: '',
  dispatch_phone: '',
  special_instructions: '',
})

const scheduledMaterials = computed(() => props.schedule.materials || [])

const isConcreteFormValid = computed(() => {
  return concreteForm.quantity && concreteForm.quantity > 0 && concreteForm.unit
})

const isGravelFormValid = computed(() => {
  return gravelForm.quantity && gravelForm.quantity > 0 && gravelForm.unit
})

const isOtherFormValid = computed(() => {
  return otherForm.quantity && otherForm.quantity > 0 && otherForm.unit
})

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

function capitalize(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function resetForm(type: 'concrete' | 'gravel' | 'other') {
  if (type === 'concrete') {
    concreteForm.quantity = null
    concreteForm.unit = 'Cubic Yards'
    concreteForm.yards_per_hour = null
    concreteForm.additives = ''
    concreteForm.dispatch_number = ''
    concreteForm.dispatch_phone = ''
    concreteForm.special_instructions = ''
  } else if (type === 'gravel') {
    gravelForm.quantity = null
    gravelForm.unit = 'Tons'
    gravelForm.dispatch_number = ''
    gravelForm.dispatch_phone = ''
    gravelForm.special_instructions = ''
  } else {
    otherForm.quantity = null
    otherForm.unit = ''
    otherForm.dispatch_number = ''
    otherForm.dispatch_phone = ''
    otherForm.special_instructions = ''
  }
}

async function addMaterial(type: 'concrete' | 'gravel' | 'other') {
  saving.value = true

  let materialData: any = { type }

  if (type === 'concrete') {
    materialData = {
      ...materialData,
      quantity: concreteForm.quantity,
      unit: concreteForm.unit,
      yards_per_hour: concreteForm.yards_per_hour || null,
      additives: concreteForm.additives || null,
      dispatch_number: concreteForm.dispatch_number || null,
      dispatch_phone: concreteForm.dispatch_phone || null,
      special_instructions: concreteForm.special_instructions || null,
    }
  } else if (type === 'gravel') {
    materialData = {
      ...materialData,
      quantity: gravelForm.quantity,
      unit: gravelForm.unit,
      dispatch_number: gravelForm.dispatch_number || null,
      dispatch_phone: gravelForm.dispatch_phone || null,
      special_instructions: gravelForm.special_instructions || null,
    }
  } else {
    materialData = {
      ...materialData,
      quantity: otherForm.quantity,
      unit: otherForm.unit,
      dispatch_number: otherForm.dispatch_number || null,
      dispatch_phone: otherForm.dispatch_phone || null,
      special_instructions: otherForm.special_instructions || null,
    }
  }

  try {
    await apiClient.post(`/schedules/${props.schedule.id}/materials`, {
      materials: [materialData]
    })

    $q.notify({
      type: 'positive',
      message: `${capitalize(type)} added successfully`,
    })

    emit('updated')
    resetForm(type)
  } catch (error: any) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to add material',
    })
  } finally {
    saving.value = false
  }
}

async function deleteMaterial(materialId: number) {
  $q.dialog({
    title: 'Confirm Delete',
    message: 'Are you sure you want to delete this material?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    saving.value = true
    try {
      await apiClient.delete(`/schedules/${props.schedule.id}/materials/${materialId}`)

      $q.notify({
        type: 'positive',
        message: 'Material deleted successfully',
      })

      emit('updated')
    } catch (error: any) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to delete material',
      })
    } finally {
      saving.value = false
    }
  })
}

function closeDialog() {
  emit('update:modelValue', false)
}
</script>

<style scoped>
.material-form {
  padding: 1rem 0;
}

.materials-list {
  max-height: 300px;
  overflow-y: auto;
}
</style>
