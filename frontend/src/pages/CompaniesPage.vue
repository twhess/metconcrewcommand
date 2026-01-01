<template>
  <q-page class="companies-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="text-h4 text-weight-bold">Companies</h1>
        <q-btn
          round
          color="primary"
          icon="add"
          @click="openAddDialog"
          size="md"
        >
          <q-tooltip>Add Company</q-tooltip>
        </q-btn>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <q-input
        v-model="search"
        placeholder="Search companies..."
        outlined
        dense
        class="search-input"
      >
        <template v-slot:prepend>
          <q-icon name="search" />
        </template>
      </q-input>

      <q-select
        v-model="typeFilter"
        :options="typeOptions"
        label="Type"
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

    <!-- Companies Table -->
    <q-card flat bordered>
      <q-table
        :rows="filteredCompanies"
        :columns="columns"
        row-key="id"
        :loading="loading"
        :pagination="pagination"
        @row-click="(_evt, row) => viewCompany(row)"
        class="companies-table"
      >
        <template v-slot:body-cell-type="props">
          <q-td :props="props">
            <q-badge :color="getTypeColor(props.value)" :label="props.value" />
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
              @click.stop="viewCompany(props.row)"
            >
              <q-tooltip>View</q-tooltip>
            </q-btn>
            <q-btn
              v-if="can('companies.update')"
              flat
              round
              dense
              icon="edit"
              color="secondary"
              @click.stop="editCompany(props.row)"
            >
              <q-tooltip>Edit</q-tooltip>
            </q-btn>
            <q-btn
              v-if="can('companies.delete')"
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

    <!-- Add/Edit/View Dialog -->
    <q-dialog v-model="dialogOpen" persistent>
      <q-card class="dialog-card">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">
            {{ dialogMode === 'add' ? 'Add Company' : dialogMode === 'edit' ? 'Edit Company' : 'Company Details' }}
          </div>
          <q-space />
          <q-btn
            v-if="dialogMode === 'view'"
            icon="edit"
            flat
            round
            dense
            color="primary"
            @click="dialogMode = 'edit'"
          >
            <q-tooltip>Edit</q-tooltip>
          </q-btn>
          <q-btn icon="close" flat round dense @click="closeDialog" />
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveCompany" class="company-form">
            <!-- Company Name -->
            <q-input
              v-model="formData.name"
              label="Company Name *"
              outlined
              :readonly="dialogMode === 'view'"
              :rules="[val => !!val || 'Company name is required']"
              class="q-mb-md"
            />

            <!-- Type -->
            <q-select
              v-model="formData.type"
              :options="typeOptionsForm"
              label="Type *"
              outlined
              emit-value
              map-options
              :readonly="dialogMode === 'view'"
              :rules="[val => !!val || 'Type is required']"
              class="q-mb-md"
            />

            <!-- Contact Info Row -->
            <div class="row q-col-gutter-md q-mb-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="formData.main_phone"
                  label="Main Phone"
                  outlined
                  :readonly="dialogMode === 'view'"
                  mask="(###) ###-####"
                  unmasked-value
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="formData.main_email"
                  label="Main Email"
                  type="email"
                  outlined
                  :readonly="dialogMode === 'view'"
                />
              </div>
            </div>

            <!-- Website -->
            <q-input
              v-model="formData.website"
              label="Website"
              outlined
              :readonly="dialogMode === 'view'"
              class="q-mb-md"
              placeholder="https://example.com"
            />

            <!-- Notes -->
            <q-input
              v-model="formData.notes"
              label="Notes"
              type="textarea"
              outlined
              rows="3"
              :readonly="dialogMode === 'view'"
              class="q-mb-md"
            />

            <!-- Active Status -->
            <q-toggle
              v-model="formData.is_active"
              label="Active"
              :disable="dialogMode === 'view'"
            />

            <!-- Locations Section (View Mode Only) -->
            <div v-if="dialogMode === 'view' && selectedCompany" class="q-mt-lg">
              <div class="row items-center q-mb-md">
                <div class="text-subtitle1 text-weight-bold">Locations</div>
                <q-space />
                <q-btn
                  size="sm"
                  color="primary"
                  icon="add"
                  label="Add Location"
                  @click="openLocationDialog"
                  unelevated
                />
              </div>
              <q-list v-if="selectedCompany.locations && selectedCompany.locations.length > 0" bordered separator>
                <q-item v-for="location in selectedCompany.locations" :key="location.id">
                  <q-item-section>
                    <q-item-label>
                      {{ location.location_name }}
                      <q-badge v-if="location.is_primary" color="primary" label="Primary" class="q-ml-sm" />
                    </q-item-label>
                    <q-item-label caption>
                      {{ location.location_type || 'N/A' }} • {{ formatAddress(location) }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-btn flat round dense icon="edit" color="primary" size="sm" @click="openLocationDialog(location)">
                      <q-tooltip>Edit Location</q-tooltip>
                    </q-btn>
                  </q-item-section>
                </q-item>
              </q-list>
              <div v-else class="text-grey-6 text-center q-pa-md">
                No locations added yet
              </div>
            </div>

            <!-- Contacts Section (View Mode Only) -->
            <div v-if="dialogMode === 'view' && selectedCompany" class="q-mt-lg">
              <div class="row items-center q-mb-md">
                <div class="text-subtitle1 text-weight-bold">Contacts</div>
                <q-space />
                <q-btn
                  size="sm"
                  color="primary"
                  icon="add"
                  label="Add Contact"
                  @click="openContactDialog"
                  unelevated
                />
              </div>
              <q-list v-if="selectedCompany.contacts && selectedCompany.contacts.length > 0" bordered separator>
                <q-item v-for="contact in selectedCompany.contacts" :key="contact.id">
                  <q-item-section>
                    <q-item-label>{{ contact.first_name }} {{ contact.last_name }}</q-item-label>
                    <q-item-label caption>
                      {{ contact.title || 'No title' }} • {{ contact.email }}
                    </q-item-label>
                    <q-item-label caption v-if="contact.phone_mobile || contact.phone_work">
                      {{ contact.phone_mobile || contact.phone_work }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-btn flat round dense icon="edit" color="primary" size="sm" @click="openContactDialog(contact)">
                      <q-tooltip>Edit Contact</q-tooltip>
                    </q-btn>
                  </q-item-section>
                </q-item>
              </q-list>
              <div v-else class="text-grey-6 text-center q-pa-md">
                No contacts added yet
              </div>
            </div>

            <!-- Actions -->
            <div class="dialog-actions">
              <q-btn
                label="Cancel"
                color="grey"
                flat
                @click="closeDialog"
              />
              <q-btn
                v-if="dialogMode !== 'view'"
                label="Save"
                type="submit"
                color="primary"
                unelevated
                :loading="saving"
              />
              <q-btn
                v-if="dialogMode === 'view' && can('companies.update')"
                label="Edit"
                color="secondary"
                unelevated
                @click="dialogMode = 'edit'"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Delete Confirmation Dialog -->
    <q-dialog v-model="deleteDialogOpen">
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-color="white" />
          <span class="q-ml-sm">Are you sure you want to delete this company?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="grey" v-close-popup />
          <q-btn
            flat
            label="Delete"
            color="negative"
            @click="deleteCompany"
            :loading="deleting"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Location Form Dialog -->
    <LocationFormDialog
      v-model="locationDialogOpen"
      :company-id="selectedCompany?.id || 0"
      :location="selectedLocation"
      :existing-locations="selectedCompany?.locations || []"
      @saved="onLocationSaved"
    />

    <!-- Contact Form Dialog -->
    <ContactFormDialog
      v-model="contactDialogOpen"
      :company-id="selectedCompany?.id || 0"
      :contact="selectedContact"
      :locations="selectedCompany?.locations || []"
      @saved="onContactSaved"
    />
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { usePermissions } from '@/composables/usePermissions'
import apiClient from '@/api/client'
import type { Company, CompanyLocation, Contact, ApiResponse } from '@/types'
import LocationFormDialog from '@/components/LocationFormDialog.vue'
import ContactFormDialog from '@/components/ContactFormDialog.vue'

const $q = useQuasar()
const { can } = usePermissions()

// State
const companies = ref<Company[]>([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const search = ref('')
const typeFilter = ref<string>('all')
const statusFilter = ref<string>('all')
const dialogOpen = ref(false)
const deleteDialogOpen = ref(false)
const dialogMode = ref<'add' | 'edit' | 'view'>('add')
const selectedCompany = ref<Company | null>(null)

// Location & Contact Dialog State
const locationDialogOpen = ref(false)
const selectedLocation = ref<CompanyLocation | null>(null)
const contactDialogOpen = ref(false)
const selectedContact = ref<Contact | null>(null)

// Form Data
const formData = ref<Partial<Company>>({
  name: '',
  type: 'customer',
  main_phone: '',
  main_email: '',
  website: '',
  notes: '',
  is_active: true
})

// Table Configuration
const columns = [
  {
    name: 'name',
    label: 'Company Name',
    field: 'name',
    align: 'left' as const,
    sortable: true
  },
  {
    name: 'type',
    label: 'Type',
    field: 'type',
    align: 'left' as const,
    sortable: true
  },
  {
    name: 'main_phone',
    label: 'Main Phone',
    field: 'main_phone',
    align: 'left' as const
  },
  {
    name: 'main_email',
    label: 'Main Email',
    field: 'main_email',
    align: 'left' as const
  },
  {
    name: 'website',
    label: 'Website',
    field: 'website',
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
  rowsPerPage: 10
})

// Filter Options
const typeOptions = [
  { label: 'All Types', value: 'all' },
  { label: 'Customer', value: 'customer' },
  { label: 'Vendor', value: 'vendor' },
  { label: 'Contractor', value: 'contractor' },
  { label: 'Internal', value: 'internal' }
]

const typeOptionsForm = [
  { label: 'Customer', value: 'customer' },
  { label: 'Vendor', value: 'vendor' },
  { label: 'Contractor', value: 'contractor' },
  { label: 'Internal', value: 'internal' }
]

const statusOptions = [
  { label: 'All Status', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

// Computed
const filteredCompanies = computed(() => {
  let filtered = companies.value

  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase()
    filtered = filtered.filter(company =>
      company.name.toLowerCase().includes(searchLower) ||
      company.main_email?.toLowerCase().includes(searchLower) ||
      company.main_phone?.includes(search.value) ||
      company.website?.toLowerCase().includes(searchLower)
    )
  }

  // Type filter
  if (typeFilter.value !== 'all') {
    filtered = filtered.filter(company => company.type === typeFilter.value)
  }

  // Status filter
  if (statusFilter.value === 'active') {
    filtered = filtered.filter(company => company.is_active)
  } else if (statusFilter.value === 'inactive') {
    filtered = filtered.filter(company => !company.is_active)
  }

  return filtered
})

// Methods
function getTypeColor(type: string): string {
  const colors: Record<string, string> = {
    customer: 'primary',
    vendor: 'secondary',
    contractor: 'accent',
    internal: 'info'
  }
  return colors[type] || 'grey'
}

async function fetchCompanies() {
  loading.value = true
  try {
    const response = await apiClient.get<ApiResponse<Company[]>>('/companies')
    companies.value = response.data.data
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Failed to load companies'
    })
  } finally {
    loading.value = false
  }
}

function openAddDialog() {
  dialogMode.value = 'add'
  formData.value = {
    name: '',
    type: 'customer',
    main_phone: '',
    main_email: '',
    website: '',
    notes: '',
    is_active: true
  }
  dialogOpen.value = true
}

function viewCompany(company: Company) {
  dialogMode.value = 'view'
  selectedCompany.value = company
  formData.value = { ...company }
  dialogOpen.value = true
}

function editCompany(company: Company) {
  dialogMode.value = 'edit'
  selectedCompany.value = company
  formData.value = { ...company }
  dialogOpen.value = true
}

function closeDialog() {
  dialogOpen.value = false
  selectedCompany.value = null
}

async function saveCompany() {
  saving.value = true
  try {
    if (dialogMode.value === 'add') {
      const response = await apiClient.post<ApiResponse<Company>>('/companies', formData.value)
      companies.value.push(response.data.data)
      $q.notify({
        type: 'positive',
        message: 'Company created successfully'
      })
    } else {
      const response = await apiClient.put<ApiResponse<Company>>(
        `/companies/${selectedCompany.value?.id}`,
        formData.value
      )
      const index = companies.value.findIndex(c => c.id === selectedCompany.value?.id)
      if (index !== -1) {
        companies.value[index] = response.data.data
      }
      $q.notify({
        type: 'positive',
        message: 'Company updated successfully'
      })
    }
    closeDialog()
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: dialogMode.value === 'add' ? 'Failed to create company' : 'Failed to update company'
    })
  } finally {
    saving.value = false
  }
}

function confirmDelete(company: Company) {
  selectedCompany.value = company
  deleteDialogOpen.value = true
}

async function deleteCompany() {
  if (!selectedCompany.value) return

  deleting.value = true
  try {
    await apiClient.delete(`/companies/${selectedCompany.value.id}`)
    companies.value = companies.value.filter(c => c.id !== selectedCompany.value?.id)
    $q.notify({
      type: 'positive',
      message: 'Company deleted successfully'
    })
    deleteDialogOpen.value = false
    selectedCompany.value = null
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Failed to delete company'
    })
  } finally {
    deleting.value = false
  }
}

function formatAddress(location: any): string {
  const parts = []
  if (location.city) parts.push(location.city)
  if (location.state) parts.push(location.state)
  if (location.zip) parts.push(location.zip)
  return parts.length > 0 ? parts.join(', ') : 'No address'
}

function openLocationDialog(location: CompanyLocation | null = null) {
  selectedLocation.value = location
  locationDialogOpen.value = true
}

function openContactDialog(contact: Contact | null = null) {
  selectedContact.value = contact
  contactDialogOpen.value = true
}

async function onLocationSaved() {
  // Refresh the company data to show updated locations
  if (selectedCompany.value?.id) {
    try {
      const response = await apiClient.get<ApiResponse<Company>>(`/companies/${selectedCompany.value.id}`)
      selectedCompany.value = response.data.data
      formData.value = { ...response.data.data }

      // Update in the companies list
      const index = companies.value.findIndex(c => c.id === selectedCompany.value?.id)
      if (index !== -1) {
        companies.value[index] = response.data.data
      }
    } catch (error) {
      console.error('Failed to refresh company data:', error)
    }
  }
}

async function onContactSaved() {
  // Refresh the company data to show updated contacts
  if (selectedCompany.value?.id) {
    try {
      const response = await apiClient.get<ApiResponse<Company>>(`/companies/${selectedCompany.value.id}`)
      selectedCompany.value = response.data.data
      formData.value = { ...response.data.data }

      // Update in the companies list
      const index = companies.value.findIndex(c => c.id === selectedCompany.value?.id)
      if (index !== -1) {
        companies.value[index] = response.data.data
      }
    } catch (error) {
      console.error('Failed to refresh company data:', error)
    }
  }
}

onMounted(() => {
  fetchCompanies()
})
</script>

<style scoped>
.companies-page {
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
  width: 180px;
}

.companies-table {
  cursor: pointer;
}

.company-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.dialog-card {
  min-width: 600px;
  max-width: 800px;
  width: 90vw;
}

@media (max-width: 768px) {
  .dialog-card {
    min-width: unset;
    width: 95vw;
    max-width: 95vw;
  }
}

.dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 16px;
}

@media (max-width: 768px) {
  .companies-page {
    padding: 16px;
  }

  .filters-section {
    flex-direction: column;
  }

  .search-input,
  .filter-select {
    width: 100%;
  }
}
</style>
