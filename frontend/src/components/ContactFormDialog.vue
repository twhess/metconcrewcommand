<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="min-width: 700px; max-width: 800px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ dialogTitle }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <!-- Company selector (shown when no companyId provided) -->
          <q-select
            v-if="!props.companyId"
            v-model="selectedCompanyId"
            :options="companyOptions"
            option-value="id"
            option-label="name"
            emit-value
            map-options
            label="Company *"
            outlined
            dense
            :rules="[(val: number | null) => !!val || 'Company is required']"
            @update:model-value="onCompanyChange"
          />

          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <q-input
                v-model="formData.first_name"
                label="First Name *"
                :rules="[val => !!val || 'First name is required']"
                outlined
                dense
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="formData.last_name"
                label="Last Name *"
                :rules="[val => !!val || 'Last name is required']"
                outlined
                dense
              />
            </div>
          </div>

          <q-input
            v-model="formData.title"
            label="Title"
            outlined
            dense
            placeholder="e.g., Project Manager, Owner"
          />

          <div class="text-subtitle2 q-mt-md">Contact Information</div>

          <q-input
            v-model="formData.email"
            label="Email *"
            type="email"
            :rules="[val => !!val || 'Email is required']"
            outlined
            dense
          />

          <div class="row q-col-gutter-sm">
            <div class="col-4">
              <q-input
                v-model="formData.phone_mobile"
                label="Mobile Phone"
                outlined
                dense
                mask="(###) ###-####"
                unmasked-value
              />
            </div>
            <div class="col-4">
              <q-input
                v-model="formData.phone_work"
                label="Work Phone"
                outlined
                dense
                mask="(###) ###-####"
                unmasked-value
              />
            </div>
            <div class="col-4">
              <q-input
                v-model="formData.phone_other"
                label="Other Phone"
                outlined
                dense
                mask="(###) ###-####"
                unmasked-value
              />
            </div>
          </div>

          <q-select
            v-model="formData.preferred_contact_method"
            :options="contactMethods"
            label="Preferred Contact Method"
            outlined
            dense
            emit-value
            map-options
          />

          <div class="text-subtitle2 q-mt-md">Roles & Locations</div>

          <div class="q-mb-md">
            <q-btn
              size="sm"
              color="primary"
              icon="add"
              label="Add Role"
              @click="addRole"
              outline
            />
          </div>

          <q-list v-if="roleAssignments.length > 0" bordered separator>
            <q-item v-for="(assignment, index) in roleAssignments" :key="index">
              <q-item-section>
                <div class="row q-col-gutter-sm">
                  <div class="col-6">
                    <q-select
                      v-model="assignment.role_id"
                      :options="availableRoles"
                      option-value="id"
                      option-label="name"
                      emit-value
                      map-options
                      label="Role *"
                      outlined
                      dense
                      :rules="[(val: number | null) => !!val || 'Role is required']"
                    />
                  </div>
                  <div class="col-6">
                    <q-select
                      v-model="assignment.location_id"
                      :options="locationOptions"
                      option-value="id"
                      option-label="location_name"
                      emit-value
                      map-options
                      label="Location (Optional)"
                      outlined
                      dense
                      clearable
                    />
                  </div>
                </div>
              </q-item-section>
              <q-item-section side>
                <q-btn
                  flat
                  round
                  dense
                  icon="delete"
                  color="negative"
                  size="sm"
                  @click="removeRole(index)"
                >
                  <q-tooltip>Remove Role</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
          </q-list>

          <div v-else class="text-grey-6 text-center q-pa-md">
            No roles assigned. Click "Add Role" to assign a role to this contact.
          </div>

          <q-input
            v-model="formData.notes"
            label="Notes"
            type="textarea"
            outlined
            rows="3"
          />

          <div class="row q-mt-md q-gutter-sm justify-end">
            <q-btn label="Cancel" color="grey" flat v-close-popup />
            <q-btn
              label="Save"
              type="submit"
              color="primary"
              :loading="loading"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useContactStore } from '@/stores/contact'
import { useRoleStore } from '@/stores/role'
import apiClient from '@/api/client'
import type { Contact, Company, CompanyLocation, ApiResponse } from '@/types'

interface Props {
  modelValue: boolean
  companyId?: number
  contact?: Contact | null
  locations?: CompanyLocation[]
}

interface RoleAssignment {
  role_id: number | null
  location_id: number | null
}

const props = withDefaults(defineProps<Props>(), {
  companyId: 0,
  contact: null,
  locations: () => []
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'saved'): void
}>()

const $q = useQuasar()
const contactStore = useContactStore()
const roleStore = useRoleStore()

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const dialogTitle = computed(() =>
  props.contact ? 'Edit Contact' : 'Add Contact'
)

const loading = ref(false)
const companies = ref<Company[]>([])
const selectedCompanyId = ref<number | null>(null)
const companyLocations = ref<CompanyLocation[]>([])

const formData = ref({
  company_id: props.companyId || null as number | null,
  first_name: '',
  last_name: '',
  title: '',
  email: '',
  phone_mobile: '',
  phone_work: '',
  phone_other: '',
  preferred_contact_method: undefined as 'email' | 'mobile' | 'work' | 'other' | undefined,
  notes: ''
})

const roleAssignments = ref<RoleAssignment[]>([])

const contactMethods = [
  { label: 'Email', value: 'email' },
  { label: 'Mobile Phone', value: 'mobile' },
  { label: 'Work Phone', value: 'work' },
  { label: 'Other Phone', value: 'other' }
]

const companyOptions = computed(() => companies.value)
const availableRoles = computed(() => roleStore.roles)

const locationOptions = computed(() => {
  if (props.locations && props.locations.length > 0) {
    return props.locations
  }
  return companyLocations.value
})

onMounted(async () => {
  if (roleStore.roles.length === 0) {
    await roleStore.fetchRoles()
  }
  if (!props.companyId) {
    try {
      const response = await apiClient.get<ApiResponse<Company[]>>('/companies')
      companies.value = response.data.data
    } catch {
      // Company list load failure is non-critical
    }
  }
})

async function onCompanyChange(companyId: number) {
  formData.value.company_id = companyId
  if (companyId) {
    try {
      const response = await apiClient.get<ApiResponse<CompanyLocation[]>>('/company-locations', {
        params: { company_id: companyId }
      })
      companyLocations.value = response.data.data
    } catch {
      companyLocations.value = []
    }
  } else {
    companyLocations.value = []
  }
  roleAssignments.value.forEach(ra => {
    ra.location_id = null
  })
}

watch(() => props.contact, (newContact) => {
  if (newContact) {
    formData.value = {
      company_id: newContact.company_id || props.companyId || null,
      first_name: newContact.first_name || '',
      last_name: newContact.last_name || '',
      title: newContact.title || '',
      email: newContact.email || '',
      phone_mobile: newContact.phone_mobile || '',
      phone_work: newContact.phone_work || '',
      phone_other: newContact.phone_other || '',
      preferred_contact_method: newContact.preferred_contact_method || undefined,
      notes: newContact.notes || ''
    }

    selectedCompanyId.value = newContact.company_id || null

    if (newContact.company_id && !props.companyId) {
      onCompanyChange(newContact.company_id)
    }

    if (newContact.contact_roles && newContact.contact_roles.length > 0) {
      roleAssignments.value = newContact.contact_roles.map(cr => ({
        role_id: cr.role_id,
        location_id: cr.location_id || null
      }))
    } else {
      roleAssignments.value = []
    }
  } else {
    resetForm()
  }
}, { immediate: true })

watch(() => props.companyId, (newId) => {
  if (newId) {
    formData.value.company_id = newId
    selectedCompanyId.value = newId
  }
})

function resetForm() {
  formData.value = {
    company_id: props.companyId || null,
    first_name: '',
    last_name: '',
    title: '',
    email: '',
    phone_mobile: '',
    phone_work: '',
    phone_other: '',
    preferred_contact_method: undefined,
    notes: ''
  }
  selectedCompanyId.value = props.companyId || null
  roleAssignments.value = []
  companyLocations.value = []
}

function addRole() {
  roleAssignments.value.push({
    role_id: null,
    location_id: null
  })
}

function removeRole(index: number) {
  roleAssignments.value.splice(index, 1)
}

async function onSubmit() {
  const effectiveCompanyId = props.companyId || selectedCompanyId.value
  if (!effectiveCompanyId) {
    $q.notify({
      type: 'warning',
      message: 'Please select a company'
    })
    return
  }

  const invalidRoles = roleAssignments.value.filter(ra => !ra.role_id)
  if (invalidRoles.length > 0) {
    $q.notify({
      type: 'warning',
      message: 'Please select a role for all role assignments or remove empty ones'
    })
    return
  }

  loading.value = true
  try {
    const payload: Record<string, unknown> = {
      ...formData.value,
      company_id: effectiveCompanyId,
      roles: roleAssignments.value.filter(ra => ra.role_id !== null)
    }

    if (props.contact?.id) {
      await contactStore.updateContact(props.contact.id, payload as Partial<Contact>)
      $q.notify({
        type: 'positive',
        message: 'Contact updated successfully'
      })
    } else {
      await contactStore.createContact(payload as Partial<Contact>)
      $q.notify({
        type: 'positive',
        message: 'Contact added successfully'
      })
    }
    emit('saved')
    isOpen.value = false
    resetForm()
  } catch (error: any) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to save contact'
    })
  } finally {
    loading.value = false
  }
}
</script>
