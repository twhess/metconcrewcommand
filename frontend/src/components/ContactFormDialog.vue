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
                      :rules="[val => !!val || 'Role is required']"
                    />
                  </div>
                  <div class="col-6">
                    <q-select
                      v-model="assignment.company_location_id"
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
import type { Contact, CompanyLocation } from '@/types'

interface Props {
  modelValue: boolean
  companyId: number
  contact?: Contact | null
  locations?: CompanyLocation[]
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'saved'): void
}

interface RoleAssignment {
  role_id: number | null
  company_location_id: number | null
}

const props = withDefaults(defineProps<Props>(), {
  contact: null,
  locations: () => []
})

const emit = defineEmits<Emits>()

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

const formData = ref({
  company_id: props.companyId,
  first_name: '',
  last_name: '',
  title: '',
  email: '',
  phone_mobile: '',
  phone_work: '',
  phone_other: '',
  preferred_contact_method: null as string | null,
  notes: ''
})

const roleAssignments = ref<RoleAssignment[]>([])

const contactMethods = [
  { label: 'Email', value: 'email' },
  { label: 'Mobile Phone', value: 'phone_mobile' },
  { label: 'Work Phone', value: 'phone_work' },
  { label: 'Other Phone', value: 'phone_other' }
]

const availableRoles = computed(() => roleStore.roles)

const locationOptions = computed(() => props.locations || [])

onMounted(async () => {
  if (roleStore.roles.length === 0) {
    await roleStore.fetchRoles()
  }
})

watch(() => props.contact, (newContact) => {
  if (newContact) {
    formData.value = {
      company_id: props.companyId,
      first_name: newContact.first_name || '',
      last_name: newContact.last_name || '',
      title: newContact.title || '',
      email: newContact.email || '',
      phone_mobile: newContact.phone_mobile || '',
      phone_work: newContact.phone_work || '',
      phone_other: newContact.phone_other || '',
      preferred_contact_method: newContact.preferred_contact_method || null,
      notes: newContact.notes || ''
    }

    // Load existing role assignments
    if (newContact.contact_roles && newContact.contact_roles.length > 0) {
      roleAssignments.value = newContact.contact_roles.map(cr => ({
        role_id: cr.role_id,
        company_location_id: cr.company_location_id || null
      }))
    } else {
      roleAssignments.value = []
    }
  } else {
    resetForm()
  }
}, { immediate: true })

function resetForm() {
  formData.value = {
    company_id: props.companyId,
    first_name: '',
    last_name: '',
    title: '',
    email: '',
    phone_mobile: '',
    phone_work: '',
    phone_other: '',
    preferred_contact_method: null,
    notes: ''
  }
  roleAssignments.value = []
}

function addRole() {
  roleAssignments.value.push({
    role_id: null,
    company_location_id: null
  })
}

function removeRole(index: number) {
  roleAssignments.value.splice(index, 1)
}

async function onSubmit() {
  // Validate that all role assignments have a role selected
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
    const payload = {
      ...formData.value,
      roles: roleAssignments.value.filter(ra => ra.role_id !== null)
    }

    if (props.contact?.id) {
      await contactStore.updateContact(props.contact.id, payload)
      $q.notify({
        type: 'positive',
        message: 'Contact updated successfully'
      })
    } else {
      await contactStore.createContact(payload)
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
