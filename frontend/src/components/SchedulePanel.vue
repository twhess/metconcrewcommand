<template>
  <div class="schedule-panel">
    <div class="panel-header">
      <h2>{{ formattedDate }}</h2>
      <button @click="showAddSchedule = true" class="btn btn-primary" v-if="can('schedules.create')">
        + Add Schedule
      </button>
    </div>

    <div v-if="loading" class="loading">
      Loading schedules...
    </div>

    <div v-else-if="error" class="error">
      {{ error }}
    </div>

    <div v-else-if="schedules.length === 0" class="empty-state">
      <p>No schedules for this date</p>
      <button @click="showAddSchedule = true" class="btn btn-primary" v-if="can('schedules.create')">
        Create First Schedule
      </button>
    </div>

    <div v-else class="schedules-list">
      <ScheduleCard
        v-for="schedule in sortedSchedules"
        :key="schedule.id"
        :schedule="schedule"
        @edit="editSchedule"
        @delete="deleteSchedule"
        @duplicate="duplicateSchedule"
        @assign-crew="openCrewDialog"
        @assign-equipment="openEquipmentDialog"
        @manage-materials="openMaterialsDialog"
      />
    </div>

    <!-- Schedule Form Dialog (Create/Edit) -->
    <ScheduleFormDialog
      v-if="showScheduleForm"
      :date="selectedDate"
      :schedule="scheduleToEdit"
      @close="closeScheduleForm"
      @saved="onScheduleSaved"
    />

    <!-- Crew Assignment Dialog -->
    <CrewAssignmentDialog
      v-if="showCrewDialog && selectedSchedule"
      v-model="showCrewDialog"
      :schedule="selectedSchedule"
      @assigned="onResourcesUpdated"
    />

    <!-- Equipment Assignment Dialog -->
    <EquipmentAssignmentDialog
      v-if="showEquipmentDialog && selectedSchedule"
      v-model="showEquipmentDialog"
      :schedule="selectedSchedule"
      @assigned="onResourcesUpdated"
    />

    <!-- Materials Management Dialog -->
    <MaterialsDialog
      v-if="showMaterialsDialog && selectedSchedule"
      v-model="showMaterialsDialog"
      :schedule="selectedSchedule"
      @updated="onResourcesUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useScheduleStore } from '@/stores/schedule'
import { usePermissions } from '@/composables/usePermissions'
import { useDateFormatting } from '@/composables/useDateFormatting'
import ScheduleCard from './ScheduleCard.vue'
import ScheduleFormDialog from './ScheduleFormDialog.vue'
import CrewAssignmentDialog from './CrewAssignmentDialog.vue'
import EquipmentAssignmentDialog from './EquipmentAssignmentDialog.vue'
import MaterialsDialog from './MaterialsDialog.vue'
import type { Schedule } from '@/types'

const props = defineProps<{
  selectedDate: string
}>()

const $q = useQuasar()
const scheduleStore = useScheduleStore()
const { can } = usePermissions()
const { formatDate, addDays } = useDateFormatting()

const showScheduleForm = ref(false)
const scheduleToEdit = ref<Schedule | undefined>(undefined)
const showCrewDialog = ref(false)
const showEquipmentDialog = ref(false)
const showMaterialsDialog = ref(false)
const selectedSchedule = ref<Schedule | null>(null)

const schedules = computed(() => scheduleStore.schedulesForSelectedDate)
const loading = computed(() => scheduleStore.loading)
const error = computed(() => scheduleStore.error)

const sortedSchedules = computed(() => {
  return [...schedules.value].sort((a, b) => {
    return a.start_time.localeCompare(b.start_time)
  })
})

const formattedDate = computed(() => formatDate(props.selectedDate, 'long'))

function editSchedule(schedule: Schedule) {
  scheduleToEdit.value = schedule
  showScheduleForm.value = true
}

function closeScheduleForm() {
  showScheduleForm.value = false
  scheduleToEdit.value = undefined
}

async function deleteSchedule(scheduleId: number) {
  $q.dialog({
    title: 'Confirm Delete',
    message: 'Are you sure you want to delete this schedule? This action cannot be undone.',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await scheduleStore.deleteSchedule(scheduleId)
      $q.notify({
        type: 'positive',
        message: 'Schedule deleted successfully',
      })
    } catch (error: any) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to delete schedule',
      })
    }
  })
}

async function duplicateSchedule(schedule: Schedule) {
  const nextDate = addDays(schedule.date, 1)
  const formattedNextDate = formatDate(nextDate, 'long')

  $q.dialog({
    title: 'Duplicate Schedule',
    message: `Duplicate this schedule to ${formattedNextDate}? This will copy crew, equipment, and materials.`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const result = await scheduleStore.duplicateSchedule(schedule.id)

      // Show results with any errors
      let message = 'Schedule duplicated successfully'
      if (result.crew_errors && result.crew_errors.length > 0) {
        message += `\n\nCrew conflicts: ${result.crew_errors.join(', ')}`
      }
      if (result.equipment_errors && result.equipment_errors.length > 0) {
        message += `\n\nEquipment conflicts: ${result.equipment_errors.join(', ')}`
      }

      $q.notify({
        type: result.crew_errors?.length || result.equipment_errors?.length ? 'warning' : 'positive',
        message,
        multiLine: true,
        timeout: 5000,
      })

      // Refresh schedules for both days
      await scheduleStore.fetchSchedules({ date: props.selectedDate })
    } catch (error: any) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to duplicate schedule',
      })
    }
  })
}

function openCrewDialog(schedule: Schedule) {
  selectedSchedule.value = schedule
  showCrewDialog.value = true
}

function openEquipmentDialog(schedule: Schedule) {
  selectedSchedule.value = schedule
  showEquipmentDialog.value = true
}

function openMaterialsDialog(schedule: Schedule) {
  selectedSchedule.value = schedule
  showMaterialsDialog.value = true
}

async function onScheduleSaved() {
  closeScheduleForm()
  await scheduleStore.fetchSchedules({ date: props.selectedDate })
}

async function onResourcesUpdated() {
  // Refresh the schedules to show updated assignments
  await scheduleStore.fetchSchedules({ date: props.selectedDate })
}
</script>

<style scoped>
.schedule-panel {
  padding: 1.5rem;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.panel-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.loading,
.error,
.empty-state {
  padding: 3rem;
  text-align: center;
  color: #6b7280;
}

.error {
  color: #dc2626;
}

.empty-state p {
  margin-bottom: 1rem;
}

.schedules-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}

.btn-primary:hover {
  background: #1d4ed8;
}
</style>
