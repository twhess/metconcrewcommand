<template>
  <q-page class="users-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="text-h4 text-weight-bold">Employees</h1>
        <q-btn
          round
          color="primary"
          icon="add"
          @click="openAddDialog"
          size="md"
        >
          <q-tooltip>Add Employee</q-tooltip>
        </q-btn>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <q-input
        v-model="search"
        placeholder="Search employees..."
        outlined
        dense
        class="search-input"
      >
        <template v-slot:prepend>
          <q-icon name="search" />
        </template>
      </q-input>

      <q-select
        v-model="statusFilter"
        :options="statusOptions"
        label="Status"
        outlined
        dense
        class="filter-select"
      />

      <q-select
        v-model="availabilityFilter"
        :options="availabilityOptions"
        label="Availability"
        outlined
        dense
        class="filter-select"
      />
    </div>

    <!-- Users Table -->
    <q-card flat bordered>
      <q-table
        :rows="filteredUsers"
        :columns="columns"
        row-key="id"
        :loading="loading"
        :pagination="pagination"
        @row-click="(_evt, row) => viewUser(row)"
        class="users-table"
      >
        <template v-slot:body-cell-is_active="props">
          <q-td :props="props">
            <q-badge
              :color="props.value ? 'positive' : 'negative'"
              :label="props.value ? 'Active' : 'Inactive'"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-is_available="props">
          <q-td :props="props">
            <q-badge
              :color="props.value ? 'positive' : 'warning'"
              :label="props.value ? 'Available' : 'Unavailable'"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn
              flat
              round
              dense
              icon="edit"
              color="primary"
              @click.stop="editUser(props.row)"
            >
              <q-tooltip>Edit</q-tooltip>
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
            {{ dialogMode === 'add' ? 'Add Employee' : dialogMode === 'edit' ? 'Edit Employee' : 'Employee Details' }}
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
          <q-form @submit="saveUser" class="user-form">
            <!-- Username -->
            <q-input
              v-model="formData.username"
              label="Username *"
              outlined
              :readonly="dialogMode === 'view'"
              :rules="[val => !!val || 'Username is required']"
              class="q-mb-md"
            />

            <!-- Name Fields Row -->
            <div class="row q-col-gutter-md q-mb-md">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="formData.first_name"
                  label="First Name *"
                  outlined
                  :readonly="dialogMode === 'view'"
                  :rules="[val => !!val || 'First name is required']"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="formData.last_name"
                  label="Last Name *"
                  outlined
                  :readonly="dialogMode === 'view'"
                  :rules="[val => !!val || 'Last name is required']"
                />
              </div>
            </div>

            <!-- Preferred Name -->
            <q-input
              v-model="formData.preferred_name"
              label="Preferred Name (optional)"
              outlined
              :readonly="dialogMode === 'view'"
              hint="If provided, will be used instead of first name"
              class="q-mb-md"
            />

            <!-- Email -->
            <q-input
              v-model="formData.email"
              label="Email *"
              type="email"
              outlined
              :readonly="dialogMode === 'view'"
              :rules="[
                val => !!val || 'Email is required',
                val => /.+@.+\..+/.test(val) || 'Invalid email format'
              ]"
              class="q-mb-md"
            />

            <!-- Phone -->
            <q-input
              v-model="formData.phone"
              label="Phone"
              outlined
              :readonly="dialogMode === 'view'"
              mask="(###) ###-####"
              unmasked-value
              class="q-mb-md"
            />

            <!-- Password (only for add mode) -->
            <q-input
              v-if="dialogMode === 'add'"
              v-model="formData.password"
              label="Password *"
              type="password"
              outlined
              :rules="[val => !!val || 'Password is required']"
              class="q-mb-md"
            />

            <!-- Status and Availability Row -->
            <div class="row q-col-gutter-md q-mb-md">
              <div class="col-12 col-sm-6">
                <q-toggle
                  v-model="formData.is_active"
                  label="Active"
                  :disable="dialogMode === 'view'"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-toggle
                  v-model="formData.is_available"
                  label="Available"
                  :disable="dialogMode === 'view'"
                />
              </div>
            </div>
          </q-form>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancel" color="grey" @click="closeDialog" />
          <q-btn
            v-if="dialogMode !== 'view'"
            label="Save"
            color="primary"
            unelevated
            @click="saveUser"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useUserStore } from '@/stores/user'
import type { User } from '@/types'

const $q = useQuasar()
const userStore = useUserStore()

// State
const search = ref('')
const statusFilter = ref('all')
const availabilityFilter = ref('all')
const loading = ref(false)
const dialogOpen = ref(false)
const dialogMode = ref<'add' | 'edit' | 'view'>('add')
const selectedUser = ref<User | null>(null)

// Form Data
const formData = ref({
  username: '',
  first_name: '',
  last_name: '',
  preferred_name: '',
  email: '',
  phone: '',
  password: '',
  is_active: true,
  is_available: true
})

// Helper function to get display name
function getDisplayName(user: User) {
  const firstName = user.preferred_name || user.first_name || user.name?.split(' ')[0] || ''
  const lastName = user.last_name || user.name?.split(' ').slice(1).join(' ') || ''
  return `${firstName} ${lastName}`.trim()
}

// Filter Options
const statusOptions = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const availabilityOptions = [
  { label: 'All', value: 'all' },
  { label: 'Available', value: 'available' },
  { label: 'Unavailable', value: 'unavailable' }
]

// Computed
const filteredUsers = computed(() => {
  let filtered = userStore.users

  // Search filter
  if (search.value) {
    const searchLower = search.value.toLowerCase()
    filtered = filtered.filter(user =>
      (user.username && user.username.toLowerCase().includes(searchLower)) ||
      (user.first_name && user.first_name.toLowerCase().includes(searchLower)) ||
      (user.last_name && user.last_name.toLowerCase().includes(searchLower)) ||
      (user.preferred_name && user.preferred_name.toLowerCase().includes(searchLower)) ||
      (user.name && user.name.toLowerCase().includes(searchLower)) ||
      user.email.toLowerCase().includes(searchLower) ||
      (user.phone && user.phone.toLowerCase().includes(searchLower))
    )
  }

  // Status filter
  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(user =>
      statusFilter.value === 'active' ? user.is_active : !user.is_active
    )
  }

  // Availability filter
  if (availabilityFilter.value !== 'all') {
    filtered = filtered.filter(user =>
      availabilityFilter.value === 'available' ? user.is_available : !user.is_available
    )
  }

  return filtered
})

// Table Configuration
const columns = [
  {
    name: 'username',
    label: 'Username',
    field: 'username',
    align: 'left' as const,
    sortable: true
  },
  {
    name: 'name',
    label: 'Name',
    field: (row: User) => getDisplayName(row),
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
    field: 'phone',
    align: 'left' as const
  },
  {
    name: 'is_active',
    label: 'Status',
    field: 'is_active',
    align: 'center' as const
  },
  {
    name: 'is_available',
    label: 'Availability',
    field: 'is_available',
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

// Methods
function openAddDialog() {
  dialogMode.value = 'add'
  formData.value = {
    username: '',
    first_name: '',
    last_name: '',
    preferred_name: '',
    email: '',
    phone: '',
    password: '',
    is_active: true,
    is_available: true
  }
  dialogOpen.value = true
}

function viewUser(user: User) {
  selectedUser.value = user
  dialogMode.value = 'view'
  formData.value = {
    username: user.username || '',
    first_name: user.first_name || user.name?.split(' ')[0] || '',
    last_name: user.last_name || user.name?.split(' ').slice(1).join(' ') || '',
    preferred_name: user.preferred_name || '',
    email: user.email,
    phone: user.phone || '',
    password: '',
    is_active: user.is_active,
    is_available: user.is_available
  }
  dialogOpen.value = true
}

function editUser(user: User) {
  selectedUser.value = user
  dialogMode.value = 'edit'
  formData.value = {
    username: user.username || '',
    first_name: user.first_name || user.name?.split(' ')[0] || '',
    last_name: user.last_name || user.name?.split(' ').slice(1).join(' ') || '',
    preferred_name: user.preferred_name || '',
    email: user.email,
    phone: user.phone || '',
    password: '',
    is_active: user.is_active,
    is_available: user.is_available
  }
  dialogOpen.value = true
}

function closeDialog() {
  dialogOpen.value = false
  selectedUser.value = null
}

async function saveUser() {
  try {
    if (dialogMode.value === 'add') {
      await userStore.createUser(formData.value)
      $q.notify({
        type: 'positive',
        message: 'Employee created successfully'
      })
    } else if (dialogMode.value === 'edit' && selectedUser.value) {
      // Remove password field if empty when editing
      const updateData = { ...formData.value }
      if (!updateData.password) {
        delete updateData.password
      }
      await userStore.updateUser(selectedUser.value.id, updateData)
      $q.notify({
        type: 'positive',
        message: 'Employee updated successfully'
      })
    }
    closeDialog()
    await loadUsers()
  } catch (error: any) {
    console.error('Save error:', error)
    const errorMessage = error.response?.data?.message || error.message || 'Failed to save employee'
    $q.notify({
      type: 'negative',
      message: errorMessage
    })
  }
}

async function loadUsers() {
  loading.value = true
  try {
    await userStore.fetchUsers()
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Failed to load employees'
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadUsers()
})
</script>

<style scoped>
.users-page {
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
  min-width: 150px;
}

.users-table {
  cursor: pointer;
}

.users-table :deep(tbody tr:hover) {
  background: rgba(0, 0, 0, 0.04);
}

.user-form {
  display: flex;
  flex-direction: column;
}

.dialog-card {
  min-width: 600px;
  max-width: 800px;
  width: 90vw;
}

@media (max-width: 768px) {
  .users-page {
    padding: 16px;
  }

  .dialog-card {
    min-width: unset;
    width: 95vw;
    max-width: 95vw;
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
