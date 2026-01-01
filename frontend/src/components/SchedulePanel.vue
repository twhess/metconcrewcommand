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
      />
    </div>

    <ScheduleFormDialog
      v-if="showAddSchedule"
      :date="selectedDate"
      @close="showAddSchedule = false"
      @saved="onScheduleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import { usePermissions } from '@/composables/usePermissions'
import { useDateFormatting } from '@/composables/useDateFormatting'
import ScheduleCard from './ScheduleCard.vue'
import ScheduleFormDialog from './ScheduleFormDialog.vue'
import type { Schedule } from '@/types'

const props = defineProps<{
  selectedDate: string
}>()

const scheduleStore = useScheduleStore()
const { can } = usePermissions()
const { formatDate } = useDateFormatting()

const showAddSchedule = ref(false)

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
  // TODO: Implement edit
  console.log('Edit schedule:', schedule)
}

async function deleteSchedule(scheduleId: number) {
  if (confirm('Are you sure you want to delete this schedule?')) {
    await scheduleStore.deleteSchedule(scheduleId)
  }
}

function onScheduleSaved() {
  showAddSchedule.value = false
  scheduleStore.fetchSchedules({ date: props.selectedDate })
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
