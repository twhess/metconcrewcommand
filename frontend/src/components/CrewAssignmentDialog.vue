<template>
  <q-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" persistent>
    <q-card style="min-width: 600px; max-width: 800px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">Assign Crew - {{ schedule.project?.name }}</div>
        <q-space />
        <q-btn icon="close" flat round dense @click="closeDialog" />
      </q-card-section>

      <q-card-section>
        <div class="text-caption text-grey-7 q-mb-md">
          Date: {{ formatDate(schedule.date) }}
        </div>

        <!-- Current Crew -->
        <div v-if="currentCrew.length > 0" class="q-mb-md">
          <div class="text-subtitle2 q-mb-sm">Current Crew ({{ currentCrew.length }})</div>
          <div class="row q-col-gutter-sm">
            <div
              v-for="member in currentCrew"
              :key="member.id"
              class="col-12 col-sm-6"
            >
              <q-chip
                :removable="!saving"
                @remove="removeMember(member.id)"
                :color="member.is_foreman ? 'primary' : 'grey-4'"
                :text-color="member.is_foreman ? 'white' : 'dark'"
              >
                <q-avatar v-if="member.is_foreman" icon="star" color="amber" text-color="white" />
                {{ member.user?.name }}
                <q-tooltip v-if="member.is_foreman">Foreman</q-tooltip>
              </q-chip>
            </div>
          </div>
        </div>
        <div v-else class="text-caption text-grey-6 q-mb-md">
          No crew members assigned yet
        </div>

        <q-separator class="q-my-md" />

        <!-- Available Employees -->
        <div class="text-subtitle2 q-mb-sm">Available Employees</div>

        <div v-if="loadingEmployees" class="text-center q-pa-md">
          <q-spinner size="md" color="primary" />
          <div class="text-caption q-mt-sm">Loading available employees...</div>
        </div>

        <div v-else-if="availableEmployees.length === 0" class="text-caption text-grey-6 q-pa-md text-center">
          No available employees for this date
        </div>

        <div v-else class="q-gutter-sm">
          <q-checkbox
            v-for="employee in availableEmployees"
            :key="employee.id"
            v-model="selectedEmployeeIds"
            :val="employee.id"
            :label="employee.name"
            class="full-width"
            :disable="saving"
          />
        </div>

        <!-- Foreman Selection -->
        <div v-if="selectedEmployeeIds.length > 0" class="q-mt-md">
          <q-separator class="q-my-md" />
          <div class="text-subtitle2 q-mb-sm">Designate Foreman</div>
          <q-option-group
            v-model="selectedForemanId"
            :options="foremanOptions"
            color="primary"
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
          label="Add Selected to Crew"
          @click="assignSelectedCrew"
          :loading="saving"
          :disable="selectedEmployeeIds.length === 0"
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
import type { Schedule, User, CrewAssignment } from '@/types'

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

const loadingEmployees = ref(false)
const saving = ref(false)
const availableEmployees = ref<User[]>([])
const selectedEmployeeIds = ref<number[]>([])
const selectedForemanId = ref<number | null>(null)
const assignmentErrors = ref<string[]>([])

const currentCrew = computed(() => props.schedule.crewAssignments || [])

const foremanOptions = computed(() => {
  // Include both selected employees and current crew members
  const allCrewIds = [...selectedEmployeeIds.value, ...currentCrew.value.map(c => c.user_id)]
  const uniqueIds = [...new Set(allCrewIds)]

  return uniqueIds.map(id => {
    const employee = availableEmployees.value.find(e => e.id === id)
    const crewMember = currentCrew.value.find(c => c.user_id === id)
    const name = employee?.name || crewMember?.user?.name || `Employee #${id}`

    return {
      label: name,
      value: id,
    }
  })
})

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

async function fetchAvailableEmployees() {
  loadingEmployees.value = true
  assignmentErrors.value = []

  try {
    const response = await apiClient.get<{ success: boolean; data: User[] }>(
      `/users/available/${props.schedule.date}`
    )
    availableEmployees.value = response.data.data
  } catch (error: any) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to load available employees',
    })
  } finally {
    loadingEmployees.value = false
  }
}

async function removeMember(userId: number) {
  // Note: This requires a backend endpoint DELETE /schedules/{id}/crew/{userId}
  // For now, we'll just refresh the schedule
  $q.notify({
    type: 'info',
    message: 'Crew member removal - endpoint pending',
  })
}

async function assignSelectedCrew() {
  if (selectedEmployeeIds.value.length === 0) {
    return
  }

  saving.value = true
  assignmentErrors.value = []

  try {
    const response = await scheduleStore.assignCrew(
      props.schedule.id,
      selectedEmployeeIds.value,
      selectedForemanId.value || undefined
    )

    // Check for partial success
    if (response.errors && response.errors.length > 0) {
      assignmentErrors.value = response.errors
      $q.notify({
        type: 'warning',
        message: `Assigned ${response.assigned?.length || 0} of ${selectedEmployeeIds.value.length} crew members`,
      })
    } else {
      $q.notify({
        type: 'positive',
        message: 'Crew assigned successfully',
      })
      emit('assigned')
      closeDialog()
    }

    // Refresh available employees
    await fetchAvailableEmployees()

    // Reset selections
    selectedEmployeeIds.value = []
    selectedForemanId.value = null
  } catch (error: any) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to assign crew',
    })
  } finally {
    saving.value = false
  }
}

function closeDialog() {
  selectedEmployeeIds.value = []
  selectedForemanId.value = null
  assignmentErrors.value = []
  emit('update:modelValue', false)
}

// Watch for dialog opening to load employees
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    fetchAvailableEmployees()

    // Pre-select current foreman if exists
    const currentForeman = currentCrew.value.find(c => c.is_foreman)
    if (currentForeman) {
      selectedForemanId.value = currentForeman.user_id
    }
  }
})

onMounted(() => {
  if (props.modelValue) {
    fetchAvailableEmployees()
  }
})
</script>

<style scoped>
.full-width {
  width: 100%;
}
</style>
