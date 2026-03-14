<template>
  <q-page class="dashboard-page">
    <!-- Header Section -->
    <div class="dashboard-header">
      <div class="header-content">
        <div>
          <h1 class="text-h4 text-weight-bold q-mb-sm">
            Welcome back, {{ userData?.name || 'User' }}
          </h1>
          <p class="text-subtitle1 text-grey-7">
            {{ formattedDate }}
          </p>
        </div>
        <q-btn
          color="primary"
          label="Go to Schedule"
          icon="event"
          to="/schedule"
          unelevated
          size="md"
        />
      </div>
    </div>

    <!-- Metrics Cards -->
    <div class="metrics-section">
      <q-card class="metric-card" flat bordered v-for="metric in metrics" :key="metric.label">
        <q-card-section>
          <div class="row items-center q-gutter-sm">
            <q-icon :name="metric.icon" :color="metric.color" size="32px" />
            <div class="col">
              <div class="text-caption text-grey-7">{{ metric.label }}</div>
              <div class="text-h5 text-weight-bold">{{ metric.value }}</div>
              <div class="text-caption" :class="`text-${metric.changeColor}`">
                {{ metric.change }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Quick Actions Grid -->
    <div class="section-title q-mt-xl q-mb-md">
      <h2 class="text-h6 text-weight-medium">Quick Actions</h2>
    </div>

    <div class="quick-actions-grid">
      <q-card
        v-for="action in quickActions"
        :key="action.title"
        class="action-card"
        flat
        bordered
        clickable
        @click="navigateTo(action.route)"
      >
        <q-card-section class="text-center">
          <q-icon :name="action.icon" :color="action.color" size="48px" class="q-mb-md" />
          <div class="text-h6 text-weight-medium q-mb-xs">{{ action.title }}</div>
          <div class="text-caption text-grey-7">{{ action.description }}</div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Today's Overview -->
    <div class="section-title q-mt-xl q-mb-md">
      <h2 class="text-h6 text-weight-medium">Today's Overview</h2>
    </div>

    <div class="overview-grid">
      <!-- Today's Schedules -->
      <q-card flat bordered class="overview-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-subtitle1 text-weight-medium">Today's Schedules</div>
            <q-btn flat dense color="primary" label="View All" to="/schedule" />
          </div>

          <q-separator class="q-mb-md" />

          <div v-if="loadingSchedules" class="text-center q-pa-md">
            <q-spinner color="primary" size="md" />
          </div>

          <div v-else-if="todaySchedules.length === 0" class="text-center text-grey-6 q-pa-md">
            No schedules for today
          </div>

          <q-list v-else separator>
            <q-item v-for="schedule in todaySchedules.slice(0, 5)" :key="schedule.id">
              <q-item-section avatar>
                <q-icon name="schedule" color="primary" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ schedule.project?.name }}</q-item-label>
                <q-item-label caption>{{ formatTime(schedule.start_time) }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge :color="getStatusColor(schedule.status)" :label="schedule.status" />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>

      <!-- Active Projects -->
      <q-card flat bordered class="overview-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-subtitle1 text-weight-medium">Active Projects</div>
            <q-btn flat dense color="primary" label="View All" to="/projects" />
          </div>

          <q-separator class="q-mb-md" />

          <div v-if="loadingProjects" class="text-center q-pa-md">
            <q-spinner color="primary" size="md" />
          </div>

          <div v-else-if="activeProjects.length === 0" class="text-center text-grey-6 q-pa-md">
            No active projects
          </div>

          <q-list v-else separator>
            <q-item v-for="project in activeProjects.slice(0, 5)" :key="project.id">
              <q-item-section avatar>
                <q-icon name="business" color="secondary" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ project.name }}</q-item-label>
                <q-item-label caption>{{ project.company?.name }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>

      <!-- Available Resources -->
      <q-card flat bordered class="overview-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-subtitle1 text-weight-medium">Resource Status</div>
            <q-btn flat dense color="primary" label="Details" to="/reports/available-resources" />
          </div>

          <q-separator class="q-mb-md" />

          <div class="resource-stats">
            <div class="stat-item">
              <q-icon name="people" color="positive" size="24px" />
              <div>
                <div class="text-caption text-grey-7">Available Employees</div>
                <div class="text-h6 text-weight-bold">{{ availableEmployeesCount }}</div>
              </div>
            </div>

            <q-separator />

            <div class="stat-item">
              <q-icon name="construction" color="warning" size="24px" />
              <div>
                <div class="text-caption text-grey-7">Available Equipment</div>
                <div class="text-h6 text-weight-bold">{{ availableEquipmentCount }}</div>
              </div>
            </div>

            <q-separator />

            <div class="stat-item">
              <q-icon name="inventory" color="info" size="24px" />
              <div>
                <div class="text-caption text-grey-7">Low Stock Items</div>
                <div class="text-h6 text-weight-bold text-negative">{{ lowStockCount }}</div>
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import { useScheduleStore } from '@/stores/schedule'
import { useProjectStore } from '@/stores/project'
import { useUserStore } from '@/stores/user'
import { useEquipmentStore } from '@/stores/equipment'
import { useDateFormatting } from '@/composables/useDateFormatting'
import type { Schedule } from '@/types'

const $q = useQuasar()
const router = useRouter()
const authStore = useAuthStore()
const scheduleStore = useScheduleStore()
const projectStore = useProjectStore()
const userStore = useUserStore()
const equipmentStore = useEquipmentStore()
const { formatDate, formatTime, getTodayString } = useDateFormatting()

const userData = computed(() => authStore.user)
const loadingSchedules = ref(true)
const loadingProjects = ref(true)
const todaySchedules = ref<Schedule[]>([])
const availableEmployeesCount = ref(0)
const availableEquipmentCount = ref(0)
const lowStockCount = ref(0)

const formattedDate = computed(() => formatDate(getTodayString(), 'long'))

const activeProjects = computed(() => projectStore.activeProjects)

const metrics = ref([
  {
    label: 'Today\'s Schedules',
    value: '0',
    change: '+0 from yesterday',
    changeColor: 'positive',
    icon: 'event',
    color: 'primary'
  },
  {
    label: 'Active Projects',
    value: '0',
    change: 'Projects in progress',
    changeColor: 'grey-7',
    icon: 'folder_open',
    color: 'secondary'
  },
  {
    label: 'Available Crew',
    value: '0',
    change: 'Ready to work',
    changeColor: 'positive',
    icon: 'groups',
    color: 'positive'
  },
  {
    label: 'Equipment Ready',
    value: '0',
    change: 'Not scheduled',
    changeColor: 'warning',
    icon: 'construction',
    color: 'warning'
  }
])

// Quick Actions Type
interface QuickAction {
  title: string
  description: string
  icon: string
  color: string
  route: string
}

// Default quick actions
const defaultQuickActions: QuickAction[] = [
  {
    title: 'Daily Schedule',
    description: 'Manage today\'s work schedule',
    icon: 'calendar_today',
    color: 'primary',
    route: '/schedule'
  },
  {
    title: 'Projects',
    description: 'View and manage projects',
    icon: 'business_center',
    color: 'secondary',
    route: '/projects'
  },
  {
    title: 'Companies',
    description: 'Manage customers and vendors',
    icon: 'business',
    color: 'blue-7',
    route: '/companies'
  },
  {
    title: 'Contacts',
    description: 'Manage company contacts',
    icon: 'contacts',
    color: 'teal',
    route: '/contacts'
  },
  {
    title: 'Employees',
    description: 'Manage crew members',
    icon: 'people',
    color: 'positive',
    route: '/users'
  },
  {
    title: 'Equipment',
    description: 'Track equipment status',
    icon: 'engineering',
    color: 'warning',
    route: '/equipment'
  }
]

// Quick actions - load from localStorage or use defaults
const quickActions = ref<QuickAction[]>([...defaultQuickActions])

function loadQuickActions() {
  const saved = localStorage.getItem('app-quick-actions')
  if (saved) {
    try {
      quickActions.value = JSON.parse(saved)
    } catch (error) {
      console.error('Failed to parse saved quick actions:', error)
      quickActions.value = [...defaultQuickActions]
    }
  }
}

function getStatusColor(status: string) {
  switch (status) {
    case 'completed': return 'positive'
    case 'in_progress': return 'warning'
    default: return 'grey'
  }
}

function navigateTo(route: string) {
  router.push(route)
}

async function loadDashboardData() {
  const today = getTodayString()

  try {
    // Load schedules
    loadingSchedules.value = true
    await scheduleStore.fetchSchedules({ date: today })
    todaySchedules.value = scheduleStore.schedulesForSelectedDate
    metrics.value[0].value = todaySchedules.value.length.toString()
  } catch (error: any) {
    console.error('Failed to load schedules:', error)
    $q.notify({
      type: 'negative',
      message: `Failed to load schedules: ${error.response?.data?.message || error.message}`
    })
  } finally {
    loadingSchedules.value = false
  }

  try {
    // Load projects
    loadingProjects.value = true
    await projectStore.fetchProjects()
    metrics.value[1].value = projectStore.activeProjects.length.toString()
  } catch (error: any) {
    console.error('Failed to load projects:', error)
    $q.notify({
      type: 'negative',
      message: `Failed to load projects: ${error.response?.data?.message || error.message}`
    })
  } finally {
    loadingProjects.value = false
  }

  try {
    // Load users
    await userStore.fetchUsers({ is_active: true })
    const available = await userStore.fetchAvailableUsers(today)
    availableEmployeesCount.value = available.length
    metrics.value[2].value = available.length.toString()
  } catch (error: any) {
    console.error('Failed to load users:', error)
    // Don't show notification for optional data
  }

  try {
    // Load equipment
    await equipmentStore.fetchEquipment({ status: 'active' })
    const availableEquip = await equipmentStore.fetchAvailableEquipment(today)
    availableEquipmentCount.value = availableEquip.length
    metrics.value[3].value = availableEquip.length.toString()
  } catch (error: any) {
    console.error('Failed to load equipment:', error)
    // Don't show notification for optional data
  }
}

onMounted(() => {
  loadQuickActions()
  loadDashboardData()
})
</script>

<style scoped>
.dashboard-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}

.dashboard-header {
  margin-bottom: 32px;
}

.dashboard-header .header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.metrics-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.metric-card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.section-title {
  border-left: 4px solid var(--q-primary);
  padding-left: 12px;
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.action-card {
  transition: all 0.2s;
}

.action-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 16px;
}

.overview-card {
  min-height: 300px;
}

.resource-stats {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.resource-stats .stat-item {
  display: flex;
  align-items: center;
  gap: 16px;
}

@media (max-width: 768px) {
  .dashboard-page {
    padding: 16px;
  }

  .metrics-section {
    grid-template-columns: 1fr;
  }

  .quick-actions-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .overview-grid {
    grid-template-columns: 1fr;
  }
}
</style>
