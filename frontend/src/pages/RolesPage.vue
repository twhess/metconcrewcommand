<template>
  <q-page padding>
    <div class="q-pa-md">
      <div class="row q-mb-md justify-between items-center">
        <div class="col">
          <h4 class="q-ma-none">Roles & Permissions</h4>
          <p class="text-grey-7">Manage user roles and assign permissions</p>
        </div>
        <div class="col-auto">
          <q-btn
            v-if="can('roles.create')"
            color="primary"
            icon="add"
            label="Add Role"
            @click="openCreateDialog"
          />
        </div>
      </div>

      <!-- Roles Table -->
      <q-table
        :rows="roleStore.roles"
        :columns="columns"
        row-key="id"
        :loading="roleStore.loading"
        flat
        bordered
      >
        <!-- Active Status -->
        <template v-slot:body-cell-is_active="props">
          <q-td :props="props">
            <q-chip
              :color="props.row.is_active ? 'positive' : 'negative'"
              text-color="white"
              size="sm"
            >
              {{ props.row.is_active ? 'Active' : 'Inactive' }}
            </q-chip>
          </q-td>
        </template>

        <!-- Permission Count -->
        <template v-slot:body-cell-permissions="props">
          <q-td :props="props">
            <q-chip color="primary" text-color="white" size="sm">
              {{ props.row.permissions?.length || 0 }} permissions
            </q-chip>
          </q-td>
        </template>

        <!-- Actions -->
        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn
              v-if="can('roles.update')"
              flat
              dense
              round
              color="primary"
              icon="edit"
              @click="openEditDialog(props.row)"
            >
              <q-tooltip>Edit Role</q-tooltip>
            </q-btn>
            <q-btn
              v-if="can('roles.update')"
              flat
              dense
              round
              color="blue"
              icon="security"
              @click="openPermissionsDialog(props.row)"
            >
              <q-tooltip>Manage Permissions</q-tooltip>
            </q-btn>
            <q-btn
              v-if="can('roles.delete') && props.row.slug !== 'admin'"
              flat
              dense
              round
              color="negative"
              icon="delete"
              @click="confirmDelete(props.row)"
            >
              <q-tooltip>Delete Role</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </div>

    <!-- Create/Edit Role Dialog -->
    <q-dialog v-model="showRoleDialog" persistent>
      <q-card style="min-width: 400px">
        <q-card-section>
          <div class="text-h6">{{ isEditMode ? 'Edit Role' : 'Create Role' }}</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-form @submit="saveRole">
            <q-input
              v-model="roleForm.name"
              label="Role Name *"
              filled
              required
              :rules="[val => !!val || 'Role name is required']"
              class="q-mb-md"
            />

            <q-input
              v-model="roleForm.display_name"
              label="Display Name *"
              filled
              required
              :rules="[val => !!val || 'Display name is required']"
              class="q-mb-md"
            />

            <q-input
              v-model="roleForm.description"
              label="Description"
              filled
              type="textarea"
              rows="3"
              class="q-mb-md"
            />

            <q-toggle
              v-model="roleForm.is_active"
              label="Active"
              color="positive"
            />

            <div class="row q-gutter-sm q-mt-md">
              <q-btn
                type="submit"
                color="primary"
                label="Save"
                :loading="saving"
              />
              <q-btn
                flat
                label="Cancel"
                @click="closeRoleDialog"
                :disable="saving"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Permissions Dialog -->
    <q-dialog v-model="showPermissionsDialog" persistent>
      <q-card style="min-width: 600px; max-width: 800px">
        <q-card-section>
          <div class="text-h6">Manage Permissions: {{ currentRole?.name }}</div>
          <p class="text-caption text-grey-7">
            Select permissions for this role
          </p>
        </q-card-section>

        <q-separator />

        <q-card-section style="max-height: 60vh" class="scroll">
          <div v-if="permissionStore.loading" class="text-center q-pa-md">
            <q-spinner size="lg" color="primary" />
            <p>Loading permissions...</p>
          </div>

          <div v-else>
            <!-- Group permissions by module -->
            <div
              v-for="module in permissionStore.getAllModules()"
              :key="module"
              class="q-mb-lg"
            >
              <div class="row items-center q-mb-sm">
                <div class="col">
                  <div class="text-h6 text-capitalize">{{ module }}</div>
                </div>
                <div class="col-auto">
                  <q-btn
                    flat
                    dense
                    size="sm"
                    color="primary"
                    :label="isModuleFullySelected(module) ? 'Deselect All' : 'Select All'"
                    @click="toggleModulePermissions(module)"
                  />
                </div>
              </div>

              <div class="row q-col-gutter-md">
                <div
                  v-for="permission in permissionStore.getPermissionsByModule(module)"
                  :key="permission.id"
                  class="col-12 col-sm-6 col-md-4"
                >
                  <q-checkbox
                    v-model="selectedPermissionIds"
                    :val="permission.id"
                    :label="permission.display_name"
                    color="primary"
                  >
                    <q-tooltip v-if="permission.description">
                      {{ permission.description }}
                    </q-tooltip>
                  </q-checkbox>
                </div>
              </div>

              <q-separator class="q-mt-md" />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancel"
            @click="closePermissionsDialog"
            :disable="saving"
          />
          <q-btn
            color="primary"
            label="Save Permissions"
            @click="savePermissions"
            :loading="saving"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoleStore } from '@/stores/role'
import { usePermissionStore } from '@/stores/permission'
import { usePermissions } from '@/composables/usePermissions'
import type { Role } from '@/types'

const $q = useQuasar()
const roleStore = useRoleStore()
const permissionStore = usePermissionStore()
const { can } = usePermissions()

// Table columns
const columns = [
  {
    name: 'name',
    label: 'Role Name',
    field: 'name',
    align: 'left' as const,
    sortable: true,
  },
  {
    name: 'description',
    label: 'Description',
    field: 'description',
    align: 'left' as const,
  },
  {
    name: 'is_active',
    label: 'Status',
    field: 'is_active',
    align: 'center' as const,
  },
  {
    name: 'permissions',
    label: 'Permissions',
    field: 'permissions',
    align: 'center' as const,
  },
  {
    name: 'actions',
    label: 'Actions',
    field: 'actions',
    align: 'center' as const,
  },
]

// Role form dialog
const showRoleDialog = ref(false)
const isEditMode = ref(false)
const currentRole = ref<Role | null>(null)
const saving = ref(false)
const roleForm = ref({
  name: '',
  display_name: '',
  description: '',
  is_active: true,
})

// Permissions dialog
const showPermissionsDialog = ref(false)
const selectedPermissionIds = ref<number[]>([])

function openCreateDialog() {
  isEditMode.value = false
  currentRole.value = null
  roleForm.value = {
    name: '',
    display_name: '',
    description: '',
    is_active: true,
  }
  showRoleDialog.value = true
}

function openEditDialog(role: Role) {
  isEditMode.value = true
  currentRole.value = role
  roleForm.value = {
    name: role.name,
    display_name: role.name,
    description: role.description || '',
    is_active: role.is_active,
  }
  showRoleDialog.value = true
}

function closeRoleDialog() {
  showRoleDialog.value = false
  currentRole.value = null
}

async function saveRole() {
  saving.value = true

  try {
    if (isEditMode.value && currentRole.value) {
      await roleStore.updateRole(currentRole.value.id, roleForm.value)
      $q.notify({
        type: 'positive',
        message: 'Role updated successfully',
      })
    } else {
      await roleStore.createRole(roleForm.value)
      $q.notify({
        type: 'positive',
        message: 'Role created successfully',
      })
    }

    closeRoleDialog()
    await roleStore.fetchRoles()
  } catch (error) {
    console.error('Error saving role:', error)
  } finally {
    saving.value = false
  }
}

function openPermissionsDialog(role: Role) {
  currentRole.value = role
  selectedPermissionIds.value = role.permissions?.map(p => p.id) || []
  showPermissionsDialog.value = true

  if (permissionStore.permissions.length === 0) {
    permissionStore.fetchAllPermissions()
  }
}

function closePermissionsDialog() {
  showPermissionsDialog.value = false
  currentRole.value = null
  selectedPermissionIds.value = []
}

async function savePermissions() {
  if (!currentRole.value) return

  saving.value = true

  try {
    await roleStore.assignPermissions(currentRole.value.id, selectedPermissionIds.value)

    $q.notify({
      type: 'positive',
      message: 'Permissions updated successfully',
    })

    closePermissionsDialog()
    await roleStore.fetchRoles()
  } catch (error) {
    console.error('Error saving permissions:', error)
  } finally {
    saving.value = false
  }
}

function isModuleFullySelected(module: string): boolean {
  const modulePermissions = permissionStore.getPermissionsByModule(module)
  return modulePermissions.every(p => selectedPermissionIds.value.includes(p.id))
}

function toggleModulePermissions(module: string) {
  const modulePermissions = permissionStore.getPermissionsByModule(module)
  const allSelected = isModuleFullySelected(module)

  if (allSelected) {
    selectedPermissionIds.value = selectedPermissionIds.value.filter(
      id => !modulePermissions.some(p => p.id === id)
    )
  } else {
    const moduleIds = modulePermissions.map(p => p.id)
    selectedPermissionIds.value = [
      ...selectedPermissionIds.value,
      ...moduleIds.filter(id => !selectedPermissionIds.value.includes(id))
    ]
  }
}

function confirmDelete(role: Role) {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete the role "${role.name}"? This action cannot be undone.`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await roleStore.deleteRole(role.id)
      $q.notify({
        type: 'positive',
        message: 'Role deleted successfully',
      })
      await roleStore.fetchRoles()
    } catch (error) {
      console.error('Error deleting role:', error)
    }
  })
}

onMounted(async () => {
  await roleStore.fetchRoles()
  await permissionStore.fetchAllPermissions()
})
</script>

<style scoped>
.text-capitalize {
  text-transform: capitalize;
}
</style>
