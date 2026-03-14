<template>
  <q-page class="contacts-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="text-h4 text-weight-bold">Contacts</h1>
        <q-btn
          v-if="can('contacts.create')"
          round
          color="primary"
          icon="add"
          @click="openAddDialog"
          size="md"
        >
          <q-tooltip>Add Contact</q-tooltip>
        </q-btn>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <q-input
        v-model="search"
        placeholder="Search contacts..."
        outlined
        dense
        class="search-input"
      >
        <template v-slot:prepend>
          <q-icon name="search" />
        </template>
      </q-input>

      <q-select
        v-model="companyFilter"
        :options="companyFilterOptions"
        label="Company"
        outlined
        dense
        emit-value
        map-options
        class="filter-select"
      />

      <q-select
        v-model="statusFilter"
        :options="statusOptions"
        label="Status"
        outlined
        dense
        emit-value
        map-options
        class="filter-select"
      />
    </div>

    <!-- Contacts Table -->
    <q-card flat bordered>
      <q-table
        :rows="filteredContacts"
        :columns="columns"
        row-key="id"
        :loading="contactStore.loading"
        :pagination="pagination"
        @row-click="(_evt, row) => viewContact(row)"
        class="contacts-table"
      >
        <template v-slot:body-cell-name="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ props.row.first_name }} {{ props.row.last_name }}</div>
            <div v-if="props.row.title" class="text-caption text-grey-7">{{ props.row.title }}</div>
          </q-td>
        </template>

        <template v-slot:body-cell-company="props">
          <q-td :props="props">
            {{ props.row.company?.name || '—' }}
          </q-td>
        </template>

        <template v-slot:body-cell-phone="props">
          <q-td :props="props">
            {{ getDisplayPhone(props.row) }}
          </q-td>
        </template>

        <template v-slot:body-cell-roles="props">
          <q-td :props="props">
            <q-badge
              v-for="cr in (props.row.contact_roles || []).slice(0, 3)"
              :key="cr.id"
              color="primary"
              outline
              class="q-mr-xs q-mb-xs"
              :label="cr.role?.name || 'Unknown'"
            />
            <q-badge
              v-if="(props.row.contact_roles || []).length > 3"
              color="grey"
              :label="`+${props.row.contact_roles.length - 3}`"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-is_active="props">
          <q-td :props="props">
            <q-badge
              :color="props.value ? 'positive' : 'grey'"
              :label="props.value ? 'Active' : 'Inactive'"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn
              flat
              round
              dense
              icon="visibility"
              color="primary"
              @click.stop="viewContact(props.row)"
            >
              <q-tooltip>View</q-tooltip>
            </q-btn>
            <q-btn
              v-if="can('contacts.update')"
              flat
              round
              dense
              icon="edit"
              color="secondary"
              @click.stop="editContact(props.row)"
            >
              <q-tooltip>Edit</q-tooltip>
            </q-btn>
            <q-btn
              v-if="can('contacts.delete')"
              flat
              round
              dense
              icon="delete"
              color="negative"
              @click.stop="confirmDelete(props.row)"
            >
              <q-tooltip>Delete</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- View/Detail Dialog -->
    <q-dialog v-model="viewDialogOpen">
      <q-card class="dialog-card">
        <q-card-section class="row items-center">
          <div class="text-h6">Contact Details</div>
          <q-space />
          <q-btn
            v-if="can('contacts.update')"
            icon="edit"
            flat
            round
            dense
            color="primary"
            @click="switchToEdit"
          >
            <q-tooltip>Edit</q-tooltip>
          </q-btn>
          <q-btn icon="close" flat round dense @click="viewDialogOpen = false" />
        </q-card-section>

        <q-card-section v-if="selectedContact" class="q-pt-none">
          <div class="q-gutter-md">
            <!-- Name & Title -->
            <div>
              <div class="text-h5">{{ selectedContact.first_name }} {{ selectedContact.last_name }}</div>
              <div v-if="selectedContact.title" class="text-subtitle1 text-grey-7">{{ selectedContact.title }}</div>
            </div>

            <!-- Company -->
            <div v-if="selectedContact.company">
              <div class="text-caption text-grey-6">Company</div>
              <div>{{ selectedContact.company.name }}</div>
            </div>

            <!-- Contact Info -->
            <div>
              <div class="text-subtitle2 q-mb-sm">Contact Information</div>
              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-6">Email</div>
                  <div>{{ selectedContact.email }}</div>
                </div>
                <div v-if="selectedContact.phone_mobile" class="col-12 col-sm-6">
                  <div class="text-caption text-grey-6">Mobile</div>
                  <div>{{ selectedContact.phone_mobile }}</div>
                </div>
                <div v-if="selectedContact.phone_work" class="col-12 col-sm-6">
                  <div class="text-caption text-grey-6">Work</div>
                  <div>{{ selectedContact.phone_work }}</div>
                </div>
                <div v-if="selectedContact.phone_other" class="col-12 col-sm-6">
                  <div class="text-caption text-grey-6">Other</div>
                  <div>{{ selectedContact.phone_other }}</div>
                </div>
                <div v-if="selectedContact.preferred_contact_method" class="col-12 col-sm-6">
                  <div class="text-caption text-grey-6">Preferred Method</div>
                  <div>{{ selectedContact.preferred_contact_method }}</div>
                </div>
              </div>
            </div>

            <!-- Roles -->
            <div v-if="selectedContact.contact_roles && selectedContact.contact_roles.length > 0" class="q-mt-md">
              <div class="text-subtitle2">Roles</div>
              <q-list bordered separator class="q-mt-sm">
                <q-item v-for="cr in selectedContact.contact_roles" :key="cr.id">
                  <q-item-section>
                    <q-item-label>{{ cr.role?.name || 'Unknown Role' }}</q-item-label>
                    <q-item-label v-if="cr.location" caption>
                      {{ cr.location.location_name }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side v-if="cr.is_primary_for_role">
                    <q-badge color="primary" label="Primary" />
                  </q-item-section>
                </q-item>
              </q-list>
            </div>

            <!-- Notes -->
            <div v-if="selectedContact.notes" class="q-mt-md">
              <div class="text-subtitle2">Notes</div>
              <div class="text-body2 q-mt-xs">{{ selectedContact.notes }}</div>
            </div>

            <!-- Status -->
            <div class="q-mt-md">
              <q-badge
                :color="selectedContact.is_active ? 'positive' : 'grey'"
                :label="selectedContact.is_active ? 'Active' : 'Inactive'"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Delete Confirmation Dialog -->
    <q-dialog v-model="deleteDialogOpen">
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-color="white" />
          <span class="q-ml-sm">
            Are you sure you want to delete
            <strong>{{ selectedContact?.first_name }} {{ selectedContact?.last_name }}</strong>?
          </span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="grey" v-close-popup />
          <q-btn
            flat
            label="Delete"
            color="negative"
            @click="deleteContact"
            :loading="deleting"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Add/Edit Contact Dialog -->
    <ContactFormDialog
      v-model="formDialogOpen"
      :contact="editingContact"
      @saved="onContactSaved"
    />
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { usePermissions } from '@/composables/usePermissions'
import { useContactStore } from '@/stores/contact'
import apiClient from '@/api/client'
import type { Contact, Company, ApiResponse } from '@/types'
import ContactFormDialog from '@/components/ContactFormDialog.vue'

const $q = useQuasar()
const { can } = usePermissions()
const contactStore = useContactStore()

// State
const search = ref('')
const companyFilter = ref<number | string>('all')
const statusFilter = ref<string>('all')
const viewDialogOpen = ref(false)
const deleteDialogOpen = ref(false)
const formDialogOpen = ref(false)
const selectedContact = ref<Contact | null>(null)
const editingContact = ref<Contact | null>(null)
const deleting = ref(false)
const companies = ref<Company[]>([])

// Table Configuration
const columns = [
  {
    name: 'name',
    label: 'Name',
    field: (row: Contact) => `${row.last_name}, ${row.first_name}`,
    align: 'left' as const,
    sortable: true
  },
  {
    name: 'company',
    label: 'Company',
    field: (row: Contact) => row.company?.name || '',
    align: 'left' as const,
    sortable: true
  },
  {
    name: 'email',
    label: 'Email',
    field: 'email',
    align: 'left' as const,
    sortable: true
  },
  {
    name: 'phone',
    label: 'Phone',
    field: 'phone_work',
    align: 'left' as const
  },
  {
    name: 'roles',
    label: 'Roles',
    field: 'contact_roles',
    align: 'left' as const
  },
  {
    name: 'is_active',
    label: 'Status',
    field: 'is_active',
    align: 'center' as const
  },
  {
    name: 'actions',
    label: 'Actions',
    field: 'actions',
    align: 'right' as const
  }
]

const pagination = ref({
  page: 1,
  rowsPerPage: 15
})

const statusOptions = [
  { label: 'All Status', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const companyFilterOptions = computed(() => {
  const options: Array<{ label: string; value: number | string }> = [
    { label: 'All Companies', value: 'all' }
  ]
  companies.value.forEach(c => {
    options.push({ label: c.name, value: c.id })
  })
  return options
})

// Computed
const filteredContacts = computed(() => {
  let filtered = contactStore.contacts

  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase()
    filtered = filtered.filter(contact =>
      contact.first_name.toLowerCase().includes(searchLower) ||
      contact.last_name.toLowerCase().includes(searchLower) ||
      contact.email?.toLowerCase().includes(searchLower) ||
      contact.title?.toLowerCase().includes(searchLower) ||
      contact.company?.name?.toLowerCase().includes(searchLower) ||
      contact.phone_work?.includes(search.value) ||
      contact.phone_mobile?.includes(search.value)
    )
  }

  // Company filter
  if (companyFilter.value !== 'all') {
    filtered = filtered.filter(contact => contact.company_id === companyFilter.value)
  }

  // Status filter
  if (statusFilter.value === 'active') {
    filtered = filtered.filter(contact => contact.is_active)
  } else if (statusFilter.value === 'inactive') {
    filtered = filtered.filter(contact => !contact.is_active)
  }

  return filtered
})

// Methods
function getDisplayPhone(contact: Contact): string {
  return contact.phone_work || contact.phone_mobile || contact.phone_other || '—'
}

function openAddDialog() {
  editingContact.value = null
  formDialogOpen.value = true
}

async function viewContact(contact: Contact) {
  try {
    const detail = await contactStore.fetchContact(contact.id)
    selectedContact.value = detail
    viewDialogOpen.value = true
  } catch {
    $q.notify({
      type: 'negative',
      message: 'Failed to load contact details'
    })
  }
}

function editContact(contact: Contact) {
  editingContact.value = contact
  formDialogOpen.value = true
}

function switchToEdit() {
  viewDialogOpen.value = false
  editingContact.value = selectedContact.value
  formDialogOpen.value = true
}

function confirmDelete(contact: Contact) {
  selectedContact.value = contact
  deleteDialogOpen.value = true
}

async function deleteContact() {
  if (!selectedContact.value) return

  deleting.value = true
  try {
    await contactStore.deleteContact(selectedContact.value.id)
    $q.notify({
      type: 'positive',
      message: 'Contact deleted successfully'
    })
    deleteDialogOpen.value = false
    selectedContact.value = null
  } catch {
    $q.notify({
      type: 'negative',
      message: 'Failed to delete contact'
    })
  } finally {
    deleting.value = false
  }
}

async function onContactSaved() {
  // Reload all contacts to get fresh data with relationships
  await contactStore.fetchContacts()
}

onMounted(async () => {
  await contactStore.fetchContacts()
  // Load companies for filter dropdown
  try {
    const response = await apiClient.get<ApiResponse<Company[]>>('/companies')
    companies.value = response.data.data
  } catch {
    // Non-critical
  }
})
</script>

<style scoped>
.contacts-page {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}

.page-header {
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.filters-section {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 250px;
}

.filter-select {
  width: 200px;
}

.contacts-table {
  cursor: pointer;
}

.dialog-card {
  min-width: 600px;
  max-width: 800px;
  width: 90vw;
}

@media (max-width: 768px) {
  .contacts-page {
    padding: 16px;
  }

  .filters-section {
    flex-direction: column;
  }

  .search-input,
  .filter-select {
    width: 100%;
  }

  .dialog-card {
    min-width: unset;
    width: 95vw;
    max-width: 95vw;
  }
}
</style>
