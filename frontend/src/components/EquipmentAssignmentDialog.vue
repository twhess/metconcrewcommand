<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" persistent>
    <q-card style="min-width: 600px; max-width: 800px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">Assign Equipment - {{ schedule.project?.name }}</div>
        <q-space />
        <q-btn icon="close" flat round dense @click="closeDialog" />
      </q-card-section>

      <q-card-section>
        <div class="text-caption text-grey-7 q-mb-md">
          Date: {{ formatDate(schedule.date) }}
        </div>

        <!-- Currently Assigned -->
        <div v-if="currentEquipment.length > 0" class="q-mb-md">
          <div class="text-subtitle2 q-mb-sm">Currently Assigned ({{ currentEquipment.length }})</div>
          <div class="row q-col-gutter-sm">
            <div
              v-for="assignment in currentEquipment"
              :key="assignment.id"
              class="col-12 col-sm-6"
            >
              <q-chip
                :removable="!saving"
                @remove="removeEquipment(assignment.equipment_id)"
                color="grey-4"
                text-color="dark"
              >
                {{ assignment.equipment?.name }}
              </q-chip>
            </div>
          </div>
        </div>
        <div v-else class="text-caption text-grey-6 q-mb-md">
          No equipment assigned yet
        </div>

        <q-separator class="q-my-md" />

        <!-- Filter and Search -->
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-6">
            <q-select
              v-model="filterCategory"
              :options="categoryOptions"
              label="Filter by Category"
              dense
              outlined
              clearable
            />
          </div>
          <div class="col-6">
            <q-input
              v-model="searchQuery"
              label="Search Equipment"
              dense
              outlined
              clearable
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
        </div>

        <!-- Available Equipment -->
        <div class="text-subtitle2 q-mb-sm">Available Equipment</div>

        <div v-if="loadingEquipment" class="text-center q-pa-md">
          <q-spinner size="md" color="primary" />
          <div class="text-caption q-mt-sm">Loading available equipment...</div>
        </div>

        <div v-else-if="filteredEquipment.length === 0" class="text-caption text-grey-6 q-pa-md text-center">
          No available equipment found
        </div>

        <div v-else class="equipment-grid q-gutter-sm" style="max-height: 300px; overflow-y: auto;">
          <q-checkbox
            v-for="equipment in filteredEquipment"
            :key="equipment.id"
            v-model="selectedEquipmentIds"
            :val="equipment.id"
            :label="`${equipment.name} ${equipment.equipment_number ? '(#' + equipment.equipment_number + ')' : ''}`"
            class="full-width"
            :disable="saving"
          />
        </div>

        <!-- Error Messages -->
        <div v-if="assignmentErrors.length > 0" class="q-mt-md">
          <q-banner class="bg-negative text-white">
            <template #avatar>
              <q-icon name="warning" />
            </template>
            <div class="text-subtitle2">Assignment Errors:</div>
            <ul class="q-pl-md q-mb-none">
              <li v-for="(error, index) in assignmentErrors" :key="index">
                {{ error }}
              </li>
            </ul>
          </q-banner>
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Cancel" @click="closeDialog" :disable="saving" />
        <q-btn
          color="primary"
          label="Assign Selected"
          @click="assignSelectedEquipment"
          :loading="saving"
          :disable="selectedEquipmentIds.length === 0"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import apiClient from '@/api/client'
import { useScheduleStore } from '@/stores/schedule'
import type { Schedule, Equipment, EquipmentAssignment } from '@/types'

interface Props {
  modelValue: boolean
  schedule: Schedule
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'assigned': []
}>()

const $q = useQuasar()
const scheduleStore = useScheduleStore()

const loadingEquipment = ref(false)
const saving = ref(false)
const availableEquipment = ref<Equipment[]>([])
const selectedEquipmentIds = ref<number[]>([])
const assignmentErrors = ref<string[]>([])
const filterCategory = ref<string | null>(null)
const searchQuery = ref<string>('')

const currentEquipment = computed(() => props.schedule.equipmentAssignments || [])

const categoryOptions = computed(() => {
  const categories = new Set(availableEquipment.value.map(e => e.category).filter(Boolean))
  return ['All', ...Array.from(categories)]
})

const filteredEquipment = computed(() => {
  let filtered = availableEquipment.value

  // Filter by category
  if (filterCategory.value && filterCategory.value !== 'All') {
    filtered = filtered.filter(e => e.category === filterCategory.value)
  }

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(e =>
      e.name?.toLowerCase().includes(query) ||
      e.equipment_number?.toLowerCase().includes(query)
    )
  }

  return filtered
})

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

async function fetchAvailableEquipment() {
  loadingEquipment.value = true
  assignmentErrors.value = []

  try {
    const response = await apiClient.get<{ success: boolean; data: Equipment[] }>(
      `/equipment/available/${props.schedule.date}`
    )
    availableEquipment.value = response.data.data
  } catch (error: any) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to load available equipment',
    })
  } finally {
    loadingEquipment.value = false
  }
}

async function removeEquipment(equipmentId: number) {
  // Note: This requires a backend endpoint DELETE /schedules/{id}/equipment/{equipmentId}
  // For now, we'll just refresh the schedule
  $q.notify({
    type: 'info',
    message: 'Equipment removal - endpoint pending',
  })
}

async function assignSelectedEquipment() {
  if (selectedEquipmentIds.value.length === 0) {
    return
  }

  saving.value = true
  assignmentErrors.value = []

  try {
    const response = await scheduleStore.assignEquipment(
      props.schedule.id,
      selectedEquipmentIds.value
    )

    // Check for partial success
    if (response.errors && response.errors.length > 0) {
      assignmentErrors.value = response.errors
      $q.notify({
        type: 'warning',
        message: `Assigned ${response.assigned?.length || 0} of ${selectedEquipmentIds.value.length} equipment items`,
      })
    } else {
      $q.notify({
        type: 'positive',
        message: 'Equipment assigned successfully',
      })
      emit('assigned')
      closeDialog()
    }

    // Refresh available equipment
    await fetchAvailableEquipment()

    // Reset selections
    selectedEquipmentIds.value = []
  } catch (error: any) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to assign equipment',
    })
  } finally {
    saving.value = false
  }
}

function closeDialog() {
  selectedEquipmentIds.value = []
  assignmentErrors.value = []
  filterCategory.value = null
  searchQuery.value = ''
  emit('update:modelValue', false)
}

// Watch for dialog opening to load equipment
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    fetchAvailableEquipment()
  }
})

onMounted(() => {
  if (props.modelValue) {
    fetchAvailableEquipment()
  }
})
</script>

<style scoped>
.full-width {
  width: 100%;
}

.equipment-grid {
  display: flex;
  flex-direction: column;
}
</style>
