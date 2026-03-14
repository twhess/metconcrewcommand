<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useProjectStore } from '@/stores/project'
import { useInventoryStore } from '@/stores/inventory'
import RecordUsageDialog from '@/components/RecordUsageDialog.vue'
import type { Project, ProjectDashboard, ProjectUsageSummary } from '@/types'

const props = defineProps<{
  modelValue: boolean
  project?: Project | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'edit': [project: Project]
}>()

const projectStore = useProjectStore()
const inventoryStore = useInventoryStore()

const currentTab = ref('overview')
const dashboard = ref<ProjectDashboard | null>(null)
const loading = ref(false)
const materialsUsage = ref<ProjectUsageSummary | null>(null)
const showUsageDialog = ref(false)

const projectData = computed(() => props.project)

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

// Category color mapping
function getCategoryColor(category: string): string {
  const colors: Record<string, string> = {
    safety: 'red',
    regulatory: 'blue',
    insurance: 'purple',
    tax: 'orange',
    prevailing_wage: 'teal',
    custom: 'grey',
  }
  return colors[category] || 'grey'
}

// Compliance status color
function getComplianceColor(status: string): string {
  const colors: Record<string, string> = {
    not_started: 'grey',
    in_progress: 'blue',
    compliant: 'green',
    non_compliant: 'red',
  }
  return colors[status] || 'grey'
}

// Phase status color
function getPhaseStatusColor(status: string): string {
  const colors: Record<string, string> = {
    pending: 'grey',
    in_progress: 'blue',
    completed: 'green',
    on_hold: 'orange',
  }
  return colors[status] || 'grey'
}

function closeDialog(): void {
  emit('update:modelValue', false)
  dashboard.value = null
  currentTab.value = 'overview'
}

function handleEdit(): void {
  if (props.project) {
    emit('edit', props.project)
  }
}

async function loadDashboard(): Promise<void> {
  if (!props.project) return

  loading.value = true
  try {
    dashboard.value = await projectStore.fetchProjectSummary(props.project.id)
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  } finally {
    loading.value = false
  }
}

async function loadMaterialsUsage(): Promise<void> {
  if (!props.project) return

  try {
    materialsUsage.value = await inventoryStore.fetchProjectUsage(props.project.id)
  } catch (error) {
    console.error('Failed to load materials usage:', error)
  }
}

function handleRecordUsage(): void {
  showUsageDialog.value = true
}

function handleUsageRecorded(): void {
  loadMaterialsUsage()
}

// Watch for dialog open
watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.project) {
    loadDashboard()
  }
})

watch(() => currentTab.value, (tab) => {
  if (tab === 'materials' && props.project) {
    loadMaterialsUsage()
  }
})

// Format date
function formatDate(date?: string): string {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" maximized>
    <q-card v-if="projectData">
      <q-card-section class="row items-center q-pb-none bg-primary text-white">
        <div class="col">
          <div class="text-h5">{{ projectData.name }}</div>
          <div v-if="projectData.project_number" class="text-subtitle2">
            {{ projectData.project_number }}
          </div>
        </div>
        <div class="col-auto q-gutter-sm">
          <q-btn
            flat
            round
            icon="edit"
            @click="handleEdit"
          >
            <q-tooltip>Edit Project</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            icon="close"
            @click="closeDialog"
          />
        </div>
      </q-card-section>

      <q-tabs
        v-model="currentTab"
        dense
        class="text-grey"
        active-color="primary"
        indicator-color="primary"
        align="justify"
      >
        <q-tab name="overview" label="Overview" icon="dashboard" />
        <q-tab name="specifications" label="Specifications" icon="rule" />
        <q-tab name="phases" label="Phases" icon="timeline" />
        <q-tab name="contacts" label="Contacts" icon="people" />
        <q-tab name="vendors" label="Vendors" icon="store" />
        <q-tab name="materials" label="Materials" icon="inventory_2" />
      </q-tabs>

      <q-separator />

      <q-tab-panels v-model="currentTab" animated style="min-height: 500px">
        <!-- Overview Tab -->
        <q-tab-panel name="overview">
          <div v-if="loading" class="row justify-center q-pa-lg">
            <q-spinner color="primary" size="3em" />
          </div>

          <div v-else-if="dashboard" class="row q-col-gutter-md">
            <!-- Project Info Card -->
            <div class="col-12 col-md-6">
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-h6 q-mb-md">Project Information</div>

                  <div class="row q-col-gutter-sm">
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Status</div>
                      <q-badge :color="getStatusColor(projectData.status)" :label="projectData.status" />
                    </div>

                    <div class="col-6">
                      <div class="text-caption text-grey-7">Type</div>
                      <div>{{ projectData.project_type || 'N/A' }}</div>
                    </div>

                    <div class="col-6">
                      <div class="text-caption text-grey-7">Company</div>
                      <div>{{ projectData.company?.name }}</div>
                    </div>

                    <div class="col-6">
                      <div class="text-caption text-grey-7">Template</div>
                      <div>{{ projectData.specification_template?.name || 'None' }}</div>
                    </div>

                    <div class="col-6">
                      <div class="text-caption text-grey-7">Start Date</div>
                      <div>{{ formatDate(projectData.start_date) }}</div>
                    </div>

                    <div class="col-6">
                      <div class="text-caption text-grey-7">End Date</div>
                      <div>{{ formatDate(projectData.end_date) }}</div>
                    </div>

                    <div v-if="projectData.completed_at" class="col-12">
                      <div class="text-caption text-grey-7">Completed At</div>
                      <div>{{ formatDate(projectData.completed_at) }}</div>
                    </div>

                    <div v-if="projectData.description" class="col-12 q-mt-sm">
                      <div class="text-caption text-grey-7">Description</div>
                      <div>{{ projectData.description }}</div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <!-- Progress Card -->
            <div class="col-12 col-md-6">
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-h6 q-mb-md">Progress Overview</div>

                  <div class="q-mb-md">
                    <div class="text-caption text-grey-7">Overall Completion</div>
                    <div class="text-h4">{{ dashboard.phases.summary.overall_completion }}%</div>
                    <q-linear-progress
                      :value="dashboard.phases.summary.overall_completion / 100"
                      :color="getCompletionColor(dashboard.phases.summary.overall_completion)"
                      size="12px"
                      class="q-mt-sm"
                    />
                  </div>

                  <div class="row q-col-gutter-sm">
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Total Hours</div>
                      <div>{{ dashboard.phases.summary.total_estimated_hours || 0 }} est / {{ dashboard.phases.summary.total_actual_hours || 0 }} actual</div>
                    </div>

                    <div class="col-6">
                      <div class="text-caption text-grey-7">Phases</div>
                      <div>{{ dashboard.phases.summary.completed }} / {{ dashboard.phases.summary.total }} complete</div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <!-- Specifications Summary -->
            <div class="col-12 col-md-4">
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-h6 q-mb-md">Specifications</div>

                  <div class="row q-col-gutter-sm">
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Total</div>
                      <div class="text-h5">{{ dashboard.specifications.summary.total }}</div>
                    </div>
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Required</div>
                      <div class="text-h5">{{ dashboard.specifications.summary.required }}</div>
                    </div>
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Compliant</div>
                      <div class="text-subtitle1 text-green">{{ dashboard.specifications.summary.compliant }}</div>
                    </div>
                    <div class="col-6">
                      <div class="text-caption text-grey-7">Non-Compliant</div>
                      <div class="text-subtitle1 text-red">{{ dashboard.specifications.summary.non_compliant }}</div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <!-- Contacts Summary -->
            <div class="col-12 col-md-4">
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-h6 q-mb-md">Contacts</div>

                  <div class="text-h5 q-mb-sm">{{ dashboard.contacts.total }}</div>
                  <div v-if="dashboard.contacts.total > 0" class="text-caption text-grey-7">
                    Assigned across {{ Object.keys(dashboard.contacts.by_role).length }} roles
                  </div>
                  <div v-else class="text-caption text-grey-7">
                    No contacts assigned
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <!-- Vendors Summary -->
            <div class="col-12 col-md-4">
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-h6 q-mb-md">Vendors</div>

                  <div class="text-h5 q-mb-sm">{{ dashboard.vendors.total }}</div>
                  <div v-if="dashboard.vendors.total > 0" class="text-caption text-grey-7">
                    {{ Object.keys(dashboard.vendors.by_type).length }} vendor types
                  </div>
                  <div v-else class="text-caption text-grey-7">
                    No vendors assigned
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <!-- Address Card -->
            <div v-if="projectData.address_line1 || projectData.city" class="col-12">
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-h6 q-mb-md">
                    <q-icon name="location_on" />
                    Location
                  </div>
                  <div>{{ projectData.address_line1 }}</div>
                  <div v-if="projectData.address_line2">{{ projectData.address_line2 }}</div>
                  <div v-if="projectData.city">
                    {{ projectData.city }}<template v-if="projectData.state">, {{ projectData.state }}</template>
                    <template v-if="projectData.zip"> {{ projectData.zip }}</template>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-tab-panel>

        <!-- Specifications Tab -->
        <q-tab-panel name="specifications">
          <div v-if="loading" class="row justify-center q-pa-lg">
            <q-spinner color="primary" size="3em" />
          </div>

          <div v-else-if="dashboard && dashboard.specifications.summary.total > 0">
            <div
              v-for="(specs, category) in dashboard.specifications.by_category"
              :key="category"
              class="q-mb-lg"
            >
              <div class="text-h6 q-mb-md">
                <q-badge :color="getCategoryColor(category)" :label="category" class="q-mr-sm" />
              </div>

              <q-list bordered separator>
                <q-item
                  v-for="spec in specs"
                  :key="spec.id"
                >
                  <q-item-section>
                    <q-item-label>
                      {{ spec.requirement_name }}
                      <q-badge v-if="spec.is_required" color="red" label="Required" class="q-ml-sm" />
                    </q-item-label>
                    <q-item-label v-if="spec.requirement_description" caption>
                      {{ spec.requirement_description }}
                    </q-item-label>
                    <q-item-label v-if="spec.compliance_notes" caption class="q-mt-xs">
                      <strong>Notes:</strong> {{ spec.compliance_notes }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-badge :color="getComplianceColor(spec.compliance_status)" :label="spec.compliance_status" />
                    <div v-if="spec.verified_at" class="text-caption text-grey-7 q-mt-xs">
                      Verified {{ formatDate(spec.verified_at) }}
                    </div>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>

          <div v-else class="text-center q-pa-lg text-grey-7">
            <q-icon name="inbox" size="4em" />
            <div class="text-h6 q-mt-md">No specifications</div>
          </div>
        </q-tab-panel>

        <!-- Phases Tab -->
        <q-tab-panel name="phases">
          <div v-if="loading" class="row justify-center q-pa-lg">
            <q-spinner color="primary" size="3em" />
          </div>

          <div v-else-if="dashboard && dashboard.phases.summary.total > 0">
            <q-list bordered separator>
              <q-item
                v-for="phase in dashboard.phases.list"
                :key="phase.id"
              >
                <q-item-section>
                  <q-item-label>
                    <span class="text-h6">Phase {{ phase.phase_number }}: {{ phase.name }}</span>
                    <q-badge :color="getPhaseStatusColor(phase.status)" :label="phase.status" class="q-ml-sm" />
                  </q-item-label>
                  <q-item-label v-if="phase.description" caption>
                    {{ phase.description }}
                  </q-item-label>

                  <div class="row q-col-gutter-sm q-mt-sm">
                    <div class="col-auto">
                      <div class="text-caption text-grey-7">Progress</div>
                      <div>{{ phase.completion_percentage }}%</div>
                    </div>
                    <div v-if="phase.estimated_hours" class="col-auto">
                      <div class="text-caption text-grey-7">Hours</div>
                      <div>{{ phase.estimated_hours }} est / {{ phase.actual_hours || 0 }} actual</div>
                    </div>
                    <div v-if="phase.crew_size_estimate" class="col-auto">
                      <div class="text-caption text-grey-7">Crew Size</div>
                      <div>{{ phase.crew_size_estimate }}</div>
                    </div>
                  </div>

                  <div v-if="phase.equipment_needs && phase.equipment_needs.length > 0" class="q-mt-sm">
                    <div class="text-caption text-grey-7">Equipment Needs:</div>
                    <q-chip
                      v-for="(equipment, idx) in phase.equipment_needs"
                      :key="idx"
                      dense
                      size="sm"
                    >
                      {{ equipment }}
                    </q-chip>
                  </div>

                  <q-linear-progress
                    :value="phase.completion_percentage / 100"
                    :color="getCompletionColor(phase.completion_percentage)"
                    class="q-mt-sm"
                  />
                </q-item-section>
              </q-item>
            </q-list>
          </div>

          <div v-else class="text-center q-pa-lg text-grey-7">
            <q-icon name="inbox" size="4em" />
            <div class="text-h6 q-mt-md">No phases defined</div>
          </div>
        </q-tab-panel>

        <!-- Contacts Tab -->
        <q-tab-panel name="contacts">
          <div v-if="loading" class="row justify-center q-pa-lg">
            <q-spinner color="primary" size="3em" />
          </div>

          <div v-else-if="dashboard && dashboard.contacts.total > 0">
            <div
              v-for="(roleData, role) in dashboard.contacts.by_role"
              :key="role"
              class="q-mb-lg"
            >
              <div class="text-h6 q-mb-md">{{ role }}</div>

              <q-list bordered separator>
                <q-item
                  v-for="contact in roleData.all"
                  :key="contact.id"
                >
                  <q-item-section avatar>
                    <q-avatar color="primary" text-color="white" icon="person" />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label>{{ contact.first_name }} {{ contact.last_name }}</q-item-label>
                    <q-item-label caption>{{ contact.email }}</q-item-label>
                    <q-item-label v-if="contact.phone_mobile" caption>{{ contact.phone_mobile }}</q-item-label>
                  </q-item-section>

                  <q-item-section v-if="contact.company" side>
                    <q-item-label caption>{{ contact.company.name }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>

          <div v-else class="text-center q-pa-lg text-grey-7">
            <q-icon name="inbox" size="4em" />
            <div class="text-h6 q-mt-md">No contacts assigned</div>
          </div>
        </q-tab-panel>

        <!-- Vendors Tab -->
        <q-tab-panel name="vendors">
          <div v-if="loading" class="row justify-center q-pa-lg">
            <q-spinner color="primary" size="3em" />
          </div>

          <div v-else-if="dashboard && dashboard.vendors.total > 0">
            <div
              v-for="(typeData, type) in dashboard.vendors.by_type"
              :key="type"
              class="q-mb-lg"
            >
              <div class="text-h6 q-mb-md">{{ type }}</div>

              <q-list bordered separator>
                <q-item
                  v-for="company in typeData.all"
                  :key="company.id"
                >
                  <q-item-section avatar>
                    <q-avatar color="secondary" text-color="white" icon="store" />
                  </q-item-section>

                  <q-item-section>
                    <q-item-label>{{ company.name }}</q-item-label>
                    <q-item-label v-if="company.main_phone" caption>{{ company.main_phone }}</q-item-label>
                    <q-item-label v-if="company.main_email" caption>{{ company.main_email }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>

          <div v-else class="text-center q-pa-lg text-grey-7">
            <q-icon name="inbox" size="4em" />
            <div class="text-h6 q-mt-md">No vendors assigned</div>
          </div>
        </q-tab-panel>

        <!-- Materials Tab -->
        <q-tab-panel name="materials">
          <div class="row items-center q-mb-md">
            <div class="col">
              <div class="text-h6">Material Usage</div>
            </div>
            <div class="col-auto">
              <q-btn
                color="primary"
                icon="add"
                label="Record Usage"
                @click="handleRecordUsage"
              />
            </div>
          </div>

          <div v-if="!materialsUsage || materialsUsage.usage_summary.length === 0" class="text-center q-pa-lg">
            <q-icon name="inventory_2" size="3em" color="grey-5" />
            <div class="text-h6 q-mt-md">No materials used yet</div>
            <div class="text-grey-7 q-mt-sm">
              Click "Record Usage" to track materials used on this project
            </div>
          </div>

          <div v-else>
            <!-- Summary Stats -->
            <div class="row q-col-gutter-md q-mb-md">
              <div class="col-12 col-sm-6">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="text-h5 text-primary">{{ materialsUsage.usage_summary.length }}</div>
                    <div class="text-grey-7">Different Items Used</div>
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-12 col-sm-6">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="text-h5 text-blue">{{ materialsUsage.total_transactions }}</div>
                    <div class="text-grey-7">Total Transactions</div>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <!-- Usage List -->
            <q-list bordered separator>
              <q-item
                v-for="(usage, index) in materialsUsage.usage_summary"
                :key="index"
              >
                <q-item-section>
                  <q-item-label class="text-weight-medium">{{ usage.item.name }}</q-item-label>
                  <q-item-label caption>
                    {{ usage.item.category || 'Uncategorized' }}
                    <span v-if="usage.item.sku"> • SKU: {{ usage.item.sku }}</span>
                  </q-item-label>
                  <q-item-label caption class="q-mt-xs">
                    {{ usage.transactions.length }} transaction{{ usage.transactions.length !== 1 ? 's' : '' }}
                  </q-item-label>
                </q-item-section>

                <q-item-section side>
                  <q-badge color="primary" :label="`${usage.total_used} ${usage.item.unit || 'units'}`" />
                </q-item-section>

                <q-item-section side>
                  <q-btn
                    flat
                    dense
                    round
                    icon="expand_more"
                    @click="usage.expanded = !usage.expanded"
                  >
                    <q-tooltip>View Transactions</q-tooltip>
                  </q-btn>
                </q-item-section>
              </q-item>
            </q-list>
          </div>
        </q-tab-panel>
      </q-tab-panels>
    </q-card>

    <!-- Record Usage Dialog -->
    <RecordUsageDialog
      v-model="showUsageDialog"
      :project="projectData"
      @recorded="handleUsageRecorded"
    />
  </q-dialog>
</template>
