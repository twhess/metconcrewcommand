<template>
  <div class="employee-list">
    <div class="list-header">
      <input
        type="text"
        v-model="searchQuery"
        placeholder="Search employees..."
        class="search-input"
      />
    </div>

    <div v-if="loading" class="loading">Loading employees...</div>

    <div v-else class="employees">
      <div class="section">
        <div class="section-title">Available ({{ availableEmployees.length }})</div>
        <div v-if="availableEmployees.length === 0" class="empty-section">
          No available employees
        </div>
        <div
          v-for="employee in availableEmployees"
          :key="employee.id"
          class="employee-item available"
        >
          <div class="employee-name">{{ employee.name }}</div>
          <div class="employee-meta">
            {{ employee.email }}
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section-title">Scheduled ({{ scheduledEmployees.length }})</div>
        <div
          v-for="employee in scheduledEmployees"
          :key="employee.id"
          class="employee-item scheduled"
        >
          <div class="employee-name">{{ employee.name }}</div>
          <div class="employee-meta">On schedule</div>
        </div>
      </div>

      <div class="section" v-if="onVacationEmployees.length > 0">
        <div class="section-title">On Vacation ({{ onVacationEmployees.length }})</div>
        <div
          v-for="employee in onVacationEmployees"
          :key="employee.id"
          class="employee-item vacation"
        >
          <div class="employee-name">{{ employee.name }}</div>
          <div class="employee-meta">Vacation</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/stores/user'
import { useScheduleStore } from '@/stores/schedule'
import type { User } from '@/types'

const props = defineProps<{
  selectedDate: string
}>()

const userStore = useUserStore()
const scheduleStore = useScheduleStore()

const searchQuery = ref('')
const loading = ref(false)
const availableUsers = ref<User[]>([])

const filteredUsers = computed(() => {
  const query = searchQuery.value.toLowerCase()
  return userStore.activeUsers.filter(u =>
    u.name.toLowerCase().includes(query) ||
    u.email.toLowerCase().includes(query)
  )
})

const scheduledUserIds = computed(() => {
  const ids = new Set<number>()
  scheduleStore.schedulesForSelectedDate.forEach(schedule => {
    schedule.crew_assignments?.forEach(assignment => {
      ids.add(assignment.user_id)
    })
  })
  return ids
})

const availableEmployees = computed(() =>
  filteredUsers.value.filter(u => !scheduledUserIds.value.has(u.id))
)

const scheduledEmployees = computed(() =>
  filteredUsers.value.filter(u => scheduledUserIds.value.has(u.id))
)

const onVacationEmployees = computed(() =>
  filteredUsers.value.filter(u =>
    u.vacations?.some(v =>
      v.approved &&
      v.start_date <= props.selectedDate &&
      v.end_date >= props.selectedDate
    )
  )
)

onMounted(async () => {
  loading.value = true
  try {
    availableUsers.value = await userStore.fetchAvailableUsers(props.selectedDate)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.employee-list {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.list-header {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.search-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.loading {
  padding: 2rem 1rem;
  text-align: center;
  color: #6b7280;
  font-size: 0.875rem;
}

.employees {
  flex: 1;
  overflow-y: auto;
}

.section {
  border-bottom: 1px solid #e5e7eb;
  padding: 0.75rem 0;
}

.section-title {
  padding: 0.5rem 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #6b7280;
  background: #f9fafb;
}

.empty-section {
  padding: 1rem;
  text-align: center;
  color: #9ca3af;
  font-size: 0.875rem;
}

.employee-item {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: background 0.2s;
}

.employee-item:hover {
  background: #f9fafb;
}

.employee-item.available {
  border-left: 3px solid #10b981;
}

.employee-item.scheduled {
  border-left: 3px solid #f59e0b;
  opacity: 0.6;
  cursor: not-allowed;
}

.employee-item.vacation {
  border-left: 3px solid #6b7280;
  opacity: 0.5;
  cursor: not-allowed;
}

.employee-name {
  font-weight: 500;
  color: #111827;
  margin-bottom: 0.25rem;
  font-size: 0.875rem;
}

.employee-meta {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
