<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useProjectStore } from '@/stores/project'
import { useCompanyStore } from '@/stores/company'
import { useSpecificationTemplateStore } from '@/stores/specificationTemplate'
import ProjectFormDialog from '@/components/ProjectFormDialog.vue'
import ProjectDetailDialog from '@/components/ProjectDetailDialog.vue'
import type { Project } from '@/types'

const projectStore = useProjectStore()
const companyStore = useCompanyStore()
const templateStore = useSpecificationTemplateStore()

// Filters
const statusFilter = ref<string>('all')
const typeFilter = ref<string>('all')
const companyFilter = ref<number | null>(null)
const searchQuery = ref('')

// View mode
const viewMode = ref<'cards' | 'table'>('cards')

// Dialogs
const showFormDialog = ref(false)
const showDetailDialog = ref(false)
const selectedProject = ref<Project | null>(null)

// Filtered projects
const filteredProjects = computed(() => {
  let projects = projectStore.projects

  if (statusFilter.value !== 'all') {
    projects = projects.filter(p => p.status === statusFilter.value)
  }

  if (typeFilter.value !== 'all') {
    projects = projects.filter(p => p.project_type === typeFilter.value)
  }

  if (companyFilter.value) {
    projects = projects.filter(p => p.company_id === companyFilter.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    projects = projects.filter(p =>
      p.name.toLowerCase().includes(query) ||
      p.project_number?.toLowerCase().includes(query) ||
      p.company?.name.toLowerCase().includes(query)
    )
  }

  return projects
})

// Unique project types from data
const projectTypes = computed(() => {
  const types = new Set<string>()
  projectStore.projects.forEach(p => {
    if (p.project_type) types.add(p.project_type)
  })
  return Array.from(types)
})

// Status options
const statusOptions = [
  { label: 'All Statuses', value: 'all' },
  { label: 'Planning', value: 'planning' },
  { label: 'Active', value: 'active' },
  { label: 'On Hold', value: 'on_hold' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
]

// Company options
const companyOptions = computed(() => [
  { label: 'All Companies', value: null },
  ...companyStore.companies.map(c => ({ label: c.name, value: c.id }))
])

// Type options
const typeOptions = computed(() => [
  { label: 'All Types', value: 'all' },
  ...projectTypes.value.map(t => ({ label: t, value: t }))
])

// Status color mapping
function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    planning: 'blue',
    active: 'green',
    on_hold: 'orange',
    completed: 'grey',
    cancelled: 'red',
  }
  return colors[status] || 'grey'
}

// Completion color
function getCompletionColor(percentage: number): string {
  if (percentage >= 75) return 'green'
  if (percentage >= 50) return 'light-green'
  if (percentage >= 25) return 'orange'
  return 'red'
}

// Actions
function openCreateDialog(): void {
  selectedProject.value = null
  showFormDialog.value = true
}

function openEditDialog(project: Project): void {
  selectedProject.value = project
  showFormDialog.value = true
}

function openDetailDialog(project: Project): void {
  selectedProject.value = project
  showDetailDialog.value = true
}

async function handleDelete(project: Project): Promise<void> {
  // TODO: Add confirmation dialog
  try {
    await projectStore.deleteProject(project.id)
  } catch (error) {
    console.error('Failed to delete project:', error)
  }
}

// Handle save from form dialog
function handleProjectSaved(): void {
  // Reload projects to get fresh data
  projectStore.fetchProjects()
  showFormDialog.value = false
}

// Handle edit from detail dialog
function handleEditFromDetail(project: Project): void {
  showDetailDialog.value = false
  setTimeout(() => {
    selectedProject.value = project
    showFormDialog.value = true
  }, 300)
}

// Load data
onMounted(async () => {
  await Promise.all([
    projectStore.fetchProjects(),
    companyStore.fetchCompanies(),
    templateStore.fetchTemplates()
  ])
})
</script>

<template>
  <q-page padding>
    <!-- Header -->
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h4">Projects</div>
        <div class="text-subtitle2 text-grey-7">
          {{ filteredProjects.length }} project{{ filteredProjects.length !== 1 ? 's' : '' }}
        </div>
      </div>
      <div class="col-auto">
        <q-btn
          color="primary"
          icon="add"
          label="New Project"
          @click="openCreateDialog"
        />
      </div>
    </div>

    <!-- Filters -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-sm-6 col-md-3">
            <q-input
              v-model="searchQuery"
              outlined
              dense
              placeholder="Search projects..."
              clearable
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="statusFilter"
              :options="statusOptions"
              outlined
              dense
              label="Status"
              option-label="label"
              option-value="value"
              emit-value
              map-options
            />
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="typeFilter"
              :options="typeOptions"
              outlined
              dense
              label="Type"
              option-label="label"
              option-value="value"
              emit-value
              map-options
            />
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="companyFilter"
              :options="companyOptions"
              outlined
              dense
              label="Company"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              clearable
            />
          </div>
        </div>

        <div class="row q-mt-sm">
          <div class="col">
            <q-btn-toggle
              v-model="viewMode"
              :options="[
                { label: 'Cards', value: 'cards', icon: 'view_module' },
                { label: 'Table', value: 'table', icon: 'view_list' }
              ]"
              outline
              dense
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Loading State -->
    <div v-if="projectStore.loading" class="row justify-center q-mt-lg">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Cards View -->
    <div v-else-if="viewMode === 'cards'" class="row q-col-gutter-md">
      <div
        v-for="project in filteredProjects"
        :key="project.id"
        class="col-12 col-sm-6 col-md-4"
      >
        <q-card
          flat
          bordered
          class="cursor-pointer hover-shadow"
          @click="openDetailDialog(project)"
        >
          <q-card-section>
            <div class="row items-start q-mb-sm">
              <div class="col">
                <div class="text-h6">{{ project.name }}</div>
                <div v-if="project.project_number" class="text-caption text-grey-7">
                  {{ project.project_number }}
                </div>
              </div>
              <div class="col-auto">
                <q-badge
                  :color="getStatusColor(project.status)"
                  :label="project.status"
                />
              </div>
            </div>

            <div class="text-body2 q-mb-sm">
              <q-icon name="business" size="xs" class="q-mr-xs" />
              {{ project.company?.name || 'No company' }}
            </div>

            <div v-if="project.project_type" class="text-body2 q-mb-sm">
              <q-icon name="category" size="xs" class="q-mr-xs" />
              {{ project.project_type }}
            </div>

            <div v-if="project.completion_percentage !== undefined" class="q-mt-md">
              <div class="row items-center">
                <div class="col text-caption text-grey-7">
                  Progress: {{ project.completion_percentage }}%
                </div>
              </div>
              <q-linear-progress
                :value="project.completion_percentage / 100"
                :color="getCompletionColor(project.completion_percentage)"
                class="q-mt-xs"
              />
            </div>
          </q-card-section>

          <q-separator />

          <q-card-actions>
            <q-btn
              flat
              dense
              color="primary"
              label="View"
              @click.stop="openDetailDialog(project)"
            />
            <q-btn
              flat
              dense
              color="primary"
              label="Edit"
              @click.stop="openEditDialog(project)"
            />
            <q-space />
            <q-btn
              flat
              dense
              color="negative"
              icon="delete"
              @click.stop="handleDelete(project)"
            />
          </q-card-actions>
        </q-card>
      </div>

      <!-- Empty State -->
      <div v-if="filteredProjects.length === 0" class="col-12">
        <q-card flat bordered>
          <q-card-section class="text-center q-pa-lg">
            <q-icon name="inbox" size="4em" color="grey-5" />
            <div class="text-h6 q-mt-md">No projects found</div>
            <div class="text-grey-7 q-mt-sm">
              {{ searchQuery || statusFilter !== 'all' || typeFilter !== 'all'
                ? 'Try adjusting your filters'
                : 'Create your first project to get started'
              }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Table View -->
    <q-table
      v-else
      :rows="filteredProjects"
      :columns="[
        { name: 'name', label: 'Name', field: 'name', align: 'left', sortable: true },
        { name: 'project_number', label: 'Number', field: 'project_number', align: 'left' },
        { name: 'company', label: 'Company', field: row => row.company?.name, align: 'left', sortable: true },
        { name: 'type', label: 'Type', field: 'project_type', align: 'left', sortable: true },
        { name: 'status', label: 'Status', field: 'status', align: 'center', sortable: true },
        { name: 'completion', label: 'Progress', field: 'completion_percentage', align: 'center', sortable: true },
        { name: 'actions', label: 'Actions', align: 'right' }
      ]"
      row-key="id"
      flat
      bordered
      :rows-per-page-options="[10, 25, 50]"
    >
      <template #body-cell-status="props">
        <q-td :props="props">
          <q-badge :color="getStatusColor(props.row.status)" :label="props.row.status" />
        </q-td>
      </template>

      <template #body-cell-completion="props">
        <q-td :props="props">
          <div v-if="props.row.completion_percentage !== undefined" style="width: 100px">
            <q-linear-progress
              :value="props.row.completion_percentage / 100"
              :color="getCompletionColor(props.row.completion_percentage)"
            />
            <div class="text-caption text-center q-mt-xs">
              {{ props.row.completion_percentage }}%
            </div>
          </div>
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn flat dense round icon="visibility" color="primary" @click="openDetailDialog(props.row)">
            <q-tooltip>View Details</q-tooltip>
          </q-btn>
          <q-btn flat dense round icon="edit" color="primary" @click="openEditDialog(props.row)">
            <q-tooltip>Edit</q-tooltip>
          </q-btn>
          <q-btn flat dense round icon="delete" color="negative" @click="handleDelete(props.row)">
            <q-tooltip>Delete</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </q-table>

    <!-- Dialogs -->
    <ProjectFormDialog
      v-model="showFormDialog"
      :project="selectedProject"
      @saved="handleProjectSaved"
    />

    <ProjectDetailDialog
      v-model="showDetailDialog"
      :project="selectedProject"
      @edit="handleEditFromDetail"
    />
  </q-page>
</template>

<style scoped>
.hover-shadow {
  transition: box-shadow 0.3s ease;
}

.hover-shadow:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
