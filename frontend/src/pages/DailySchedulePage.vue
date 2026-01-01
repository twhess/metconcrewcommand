<template>
  <div class="daily-schedule-page">
    <div class="page-header">
      <h1>Daily Schedule</h1>
      <div class="date-selector">
        <button @click="previousDay" class="btn btn-secondary">
          &larr; Previous
        </button>
        <input
          type="date"
          v-model="selectedDate"
          @change="onDateChange"
          class="form-control"
        />
        <button @click="nextDay" class="btn btn-secondary">
          Next &rarr;
        </button>
        <button @click="goToToday" class="btn btn-primary">
          Today
        </button>
      </div>
    </div>

    <div class="schedule-layout">
      <!-- Left Panel: Resources -->
      <div class="resources-panel">
        <div class="panel-tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="['tab-button', { active: activeTab === tab.key }]"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="tab-content">
          <ProjectList v-if="activeTab === 'projects'" :selected-date="selectedDate" />
          <EmployeeList v-if="activeTab === 'employees'" :selected-date="selectedDate" />
          <EquipmentList v-if="activeTab === 'equipment'" :selected-date="selectedDate" />
          <MaterialsList v-if="activeTab === 'materials'" />
        </div>
      </div>

      <!-- Right Panel: Schedule View -->
      <div class="schedule-panel">
        <SchedulePanel :selected-date="selectedDate" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import { useProjectStore } from '@/stores/project'
import { useUserStore } from '@/stores/user'
import { useEquipmentStore } from '@/stores/equipment'
import { useDateFormatting } from '@/composables/useDateFormatting'
import SchedulePanel from '@/components/SchedulePanel.vue'
import ProjectList from '@/components/ProjectList.vue'
import EmployeeList from '@/components/EmployeeList.vue'
import EquipmentList from '@/components/EquipmentList.vue'
import MaterialsList from '@/components/MaterialsList.vue'

const scheduleStore = useScheduleStore()
const projectStore = useProjectStore()
const userStore = useUserStore()
const equipmentStore = useEquipmentStore()
const { getTodayString, addDays } = useDateFormatting()

const selectedDate = ref(getTodayString())
const activeTab = ref('projects')

const tabs = [
  { key: 'projects', label: 'Projects' },
  { key: 'employees', label: 'Employees' },
  { key: 'equipment', label: 'Equipment' },
  { key: 'materials', label: 'Materials' },
]

async function loadData() {
  await Promise.all([
    scheduleStore.fetchSchedules({ date: selectedDate.value }),
    projectStore.fetchProjects(),
    userStore.fetchUsers({ is_active: true }),
    equipmentStore.fetchEquipment({ status: 'active' }),
  ])
}

function onDateChange() {
  scheduleStore.setSelectedDate(selectedDate.value)
  loadData()
}

function previousDay() {
  selectedDate.value = addDays(selectedDate.value, -1)
  onDateChange()
}

function nextDay() {
  selectedDate.value = addDays(selectedDate.value, 1)
  onDateChange()
}

function goToToday() {
  selectedDate.value = getTodayString()
  onDateChange()
}

onMounted(() => {
  scheduleStore.setSelectedDate(selectedDate.value)
  loadData()
})

watch(() => selectedDate.value, () => {
  scheduleStore.setSelectedDate(selectedDate.value)
})
</script>

<style scoped>
.daily-schedule-page {
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.page-header {
  padding: 1rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-header h1 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.date-selector {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.schedule-layout {
  flex: 1;
  display: grid;
  grid-template-columns: 320px 1fr;
  overflow: hidden;
}

.resources-panel {
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  background: #f9fafb;
}

.panel-tabs {
  display: flex;
  border-bottom: 1px solid #e5e7eb;
  background: white;
}

.tab-button {
  flex: 1;
  padding: 0.75rem 1rem;
  border: none;
  background: transparent;
  cursor: pointer;
  font-weight: 500;
  color: #6b7280;
  transition: all 0.2s;
}

.tab-button:hover {
  background: #f3f4f6;
  color: #111827;
}

.tab-button.active {
  color: #2563eb;
  border-bottom: 2px solid #2563eb;
}

.tab-content {
  flex: 1;
  overflow-y: auto;
}

.schedule-panel {
  overflow-y: auto;
  background: white;
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

.btn-secondary {
  background: white;
  color: #374151;
}

.btn-secondary:hover {
  background: #f3f4f6;
}

.form-control {
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .schedule-layout {
    grid-template-columns: 1fr;
  }

  .resources-panel {
    display: none;
  }
}
</style>
