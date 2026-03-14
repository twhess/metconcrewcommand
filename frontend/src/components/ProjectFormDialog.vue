<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useProjectStore } from '@/stores/project'
import { useCompanyStore } from '@/stores/company'
import { useSpecificationTemplateStore } from '@/stores/specificationTemplate'
import type { Project } from '@/types'

const props = defineProps<{
  modelValue: boolean
  project?: Project | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'saved': [project: Project]
}>()

const projectStore = useProjectStore()
const companyStore = useCompanyStore()
const templateStore = useSpecificationTemplateStore()

const formData = ref({
  company_id: null as number | null,
  name: '',
  project_number: '',
  status: 'planning' as 'planning' | 'active' | 'on_hold' | 'completed' | 'cancelled',
  project_type: '',
  specification_template_id: null as number | null,
  address_line1: '',
  address_line2: '',
  city: '',
  state: '',
  zip: '',
  start_date: '',
  end_date: '',
  description: '',
  notes: '',
})

const saving = ref(false)
const currentStep = ref(1)

const isEditMode = computed(() => !!props.project)

const dialogTitle = computed(() =>
  isEditMode.value ? 'Edit Project' : 'New Project'
)

const statusOptions = [
  { label: 'Planning', value: 'planning' },
  { label: 'Active', value: 'active' },
  { label: 'On Hold', value: 'on_hold' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
]

const companyOptions = computed(() =>
  companyStore.companies.map(c => ({ label: c.name, value: c.id }))
)

const templateOptions = computed(() => {
  let templates = templateStore.activeTemplates

  // Filter by project type if selected
  if (formData.value.project_type) {
    templates = templates.filter(t => t.project_type === formData.value.project_type)
  }

  return [
    { label: 'None', value: null },
    ...templates.map(t => ({ label: t.name, value: t.id, description: t.description }))
  ]
})

const projectTypes = computed(() => {
  const types = new Set<string>()
  templateStore.templates.forEach(t => types.add(t.project_type))
  return Array.from(types).map(t => ({ label: t, value: t }))
})

const canProceedToStep2 = computed(() => {
  return formData.value.company_id && formData.value.name
})

// Watch for project changes (edit mode)
watch(() => props.project, (project) => {
  if (project) {
    formData.value = {
      company_id: project.company_id,
      name: project.name,
      project_number: project.project_number || '',
      status: project.status,
      project_type: project.project_type || '',
      specification_template_id: project.specification_template_id || null,
      address_line1: project.address_line1 || '',
      address_line2: project.address_line2 || '',
      city: project.city || '',
      state: project.state || '',
      zip: project.zip || '',
      start_date: project.start_date || '',
      end_date: project.end_date || '',
      description: project.description || '',
      notes: project.notes || '',
    }
  }
}, { immediate: true })

// Watch dialog open/close
watch(() => props.modelValue, (isOpen) => {
  if (isOpen && !props.project) {
    // Reset form for new project
    resetForm()
    currentStep.value = 1
  }
})

function resetForm(): void {
  formData.value = {
    company_id: null,
    name: '',
    project_number: '',
    status: 'planning',
    project_type: '',
    specification_template_id: null,
    address_line1: '',
    address_line2: '',
    city: '',
    state: '',
    zip: '',
    start_date: '',
    end_date: '',
    description: '',
    notes: '',
  }
}

function closeDialog(): void {
  emit('update:modelValue', false)
  setTimeout(() => {
    currentStep.value = 1
  }, 300)
}

async function handleSave(): Promise<void> {
  saving.value = true
  try {
    const data: any = { ...formData.value }

    // Remove empty strings and convert to null
    Object.keys(data).forEach(key => {
      if (data[key] === '') {
        data[key] = null
      }
    })

    let savedProject: Project
    if (isEditMode.value && props.project) {
      savedProject = await projectStore.updateProject(props.project.id, data)
    } else {
      savedProject = await projectStore.createProject(data)
    }

    emit('saved', savedProject)
    closeDialog()
  } catch (error) {
    console.error('Failed to save project:', error)
  } finally {
    saving.value = false
  }
}

function nextStep(): void {
  if (currentStep.value < 2) {
    currentStep.value++
  }
}

function prevStep(): void {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}
</script>

<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="min-width: 600px; max-width: 800px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ dialogTitle }}</div>
        <q-space />
        <q-btn icon="close" flat round dense @click="closeDialog" />
      </q-card-section>

      <q-stepper
        v-model="currentStep"
        vertical
        color="primary"
        animated
        class="q-pa-none"
      >
        <!-- Step 1: Basic Information -->
        <q-step
          :name="1"
          title="Basic Information"
          icon="info"
          :done="currentStep > 1"
        >
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-select
                  v-model="formData.company_id"
                  :options="companyOptions"
                  label="Company *"
                  outlined
                  dense
                  emit-value
                  map-options
                  :rules="[val => !!val || 'Company is required']"
                />
              </div>

              <div class="col-12 col-sm-8">
                <q-input
                  v-model="formData.name"
                  label="Project Name *"
                  outlined
                  dense
                  :rules="[val => !!val || 'Project name is required']"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model="formData.project_number"
                  label="Project Number"
                  outlined
                  dense
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="formData.status"
                  :options="statusOptions"
                  label="Status"
                  outlined
                  dense
                  emit-value
                  map-options
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="formData.project_type"
                  :options="projectTypes"
                  label="Project Type"
                  outlined
                  dense
                  emit-value
                  map-options
                  clearable
                  use-input
                  new-value-mode="add-unique"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model="formData.start_date"
                  label="Start Date"
                  outlined
                  dense
                  type="date"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model="formData.end_date"
                  label="End Date"
                  outlined
                  dense
                  type="date"
                />
              </div>

              <div class="col-12">
                <q-input
                  v-model="formData.description"
                  label="Description"
                  outlined
                  dense
                  type="textarea"
                  rows="3"
                />
              </div>
            </div>
          </q-card-section>

          <q-stepper-navigation>
            <q-btn
              color="primary"
              label="Next"
              :disable="!canProceedToStep2"
              @click="nextStep"
            />
            <q-btn
              flat
              label="Cancel"
              @click="closeDialog"
            />
          </q-stepper-navigation>
        </q-step>

        <!-- Step 2: Location & Additional Details -->
        <q-step
          :name="2"
          title="Location & Details"
          icon="location_on"
        >
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <div class="text-subtitle2 q-mb-sm">Template</div>
                <q-select
                  v-model="formData.specification_template_id"
                  :options="templateOptions"
                  label="Specification Template"
                  outlined
                  dense
                  emit-value
                  map-options
                  clearable
                >
                  <template #option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section>
                        <q-item-label>{{ scope.opt.label }}</q-item-label>
                        <q-item-label v-if="scope.opt.description" caption>
                          {{ scope.opt.description }}
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                </q-select>
                <div class="text-caption text-grey-7 q-mt-xs">
                  Applying a template will auto-create specification requirements for this project
                </div>
              </div>

              <div class="col-12">
                <div class="text-subtitle2 q-mb-sm">Address</div>
              </div>

              <div class="col-12">
                <q-input
                  v-model="formData.address_line1"
                  label="Address Line 1"
                  outlined
                  dense
                />
              </div>

              <div class="col-12">
                <q-input
                  v-model="formData.address_line2"
                  label="Address Line 2"
                  outlined
                  dense
                />
              </div>

              <div class="col-12 col-sm-5">
                <q-input
                  v-model="formData.city"
                  label="City"
                  outlined
                  dense
                />
              </div>

              <div class="col-12 col-sm-3">
                <q-input
                  v-model="formData.state"
                  label="State"
                  outlined
                  dense
                  maxlength="2"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model="formData.zip"
                  label="ZIP Code"
                  outlined
                  dense
                />
              </div>

              <div class="col-12">
                <q-input
                  v-model="formData.notes"
                  label="Notes"
                  outlined
                  dense
                  type="textarea"
                  rows="3"
                />
              </div>
            </div>
          </q-card-section>

          <q-stepper-navigation>
            <q-btn
              color="primary"
              :label="isEditMode ? 'Update' : 'Create'"
              :loading="saving"
              @click="handleSave"
            />
            <q-btn
              flat
              label="Back"
              @click="prevStep"
            />
          </q-stepper-navigation>
        </q-step>
      </q-stepper>
    </q-card>
  </q-dialog>
</template>
