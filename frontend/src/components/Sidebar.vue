<template>
  <q-drawer
    v-model="drawerOpen"
    show-if-above
    :width="drawerExpanded ? 250 : 70"
    :breakpoint="500"
    bordered
    class="sidebar-drawer"
  >
    <!-- Toggle Button -->
    <div class="sidebar-toggle">
      <q-btn
        flat
        dense
        round
        :icon="drawerExpanded ? 'chevron_left' : 'chevron_right'"
        @click="toggleDrawer"
      />
    </div>

    <!-- Navigation Menu -->
    <q-list class="sidebar-menu">
      <!-- Dashboard -->
      <q-item
        clickable
        :to="{ name: 'Dashboard' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="dashboard" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Dashboard</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Daily Schedule -->
      <q-item
        v-if="can('schedules.view')"
        clickable
        :to="{ name: 'DailySchedule' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="event" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Daily Schedule</q-item-label>
        </q-item-section>
      </q-item>

      <q-separator class="q-my-sm" />

      <!-- Section: Management -->
      <q-item-label v-if="drawerExpanded" header class="sidebar-section-header">
        Management
      </q-item-label>

      <!-- Projects -->
      <q-item
        v-if="can('projects.view')"
        clickable
        :to="{ name: 'Projects' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="business_center" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Projects</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Companies -->
      <q-item
        v-if="can('companies.view')"
        clickable
        :to="{ name: 'Companies' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="business" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Companies</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Contacts -->
      <q-item
        v-if="can('contacts.view')"
        clickable
        :to="{ name: 'Contacts' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="contacts" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Contacts</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Employees/Users -->
      <q-item
        v-if="can('users.view')"
        clickable
        :to="{ name: 'Users' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="people" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Employees</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Vacations -->
      <q-item
        v-if="can('vacations.view')"
        clickable
        :to="{ name: 'Vacations' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="beach_access" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Vacations</q-item-label>
        </q-item-section>
      </q-item>

      <q-separator class="q-my-sm" />

      <!-- Section: Resources -->
      <q-item-label v-if="drawerExpanded" header class="sidebar-section-header">
        Resources
      </q-item-label>

      <!-- Equipment -->
      <q-item
        v-if="can('equipment.view')"
        clickable
        :to="{ name: 'Equipment' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="construction" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Equipment</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Dispatch Board -->
      <q-item
        v-if="can('transport.view')"
        clickable
        :to="{ name: 'DispatchDashboard' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="hub" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Dispatch Board</q-item-label>
        </q-item-section>
      </q-item>

      <!-- My Assignments (Driver) -->
      <q-item
        v-if="can('transport.execute')"
        clickable
        :to="{ name: 'DriverAssignments' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="assignment" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>My Assignments</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Vehicles -->
      <q-item
        v-if="can('vehicles.view')"
        clickable
        :to="{ name: 'Vehicles' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="directions_car" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Vehicles</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Inventory -->
      <q-item
        v-if="can('inventory.view')"
        clickable
        :to="{ name: 'Inventory' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="inventory_2" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Inventory</q-item-label>
        </q-item-section>
      </q-item>

      <q-separator class="q-my-sm" />

      <!-- Section: Reports -->
      <q-item-label v-if="drawerExpanded" header class="sidebar-section-header">
        Reports
      </q-item-label>

      <!-- Available Resources Report -->
      <q-item
        v-if="can('reports.view')"
        clickable
        :to="{ name: 'AvailableResourcesReport' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="assessment" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Available Resources</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Equipment Location Report -->
      <q-item
        v-if="can('reports.view')"
        clickable
        :to="{ name: 'EquipmentLocationReport' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="location_on" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Equipment Locations</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Inventory Status Report -->
      <q-item
        v-if="can('reports.view')"
        clickable
        :to="{ name: 'InventoryStatusReport' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="analytics" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Inventory Status</q-item-label>
        </q-item-section>
      </q-item>

      <q-separator class="q-my-sm" />

      <!-- Section: Admin -->
      <q-item-label v-if="drawerExpanded" header class="sidebar-section-header">
        Admin
      </q-item-label>

      <!-- Roles & Permissions -->
      <q-item
        v-if="can('roles.view')"
        clickable
        :to="{ name: 'Roles' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="admin_panel_settings" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Roles & Permissions</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Email Test -->
      <q-item
        clickable
        :to="{ name: 'EmailTest' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="mail" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Email Test</q-item-label>
        </q-item-section>
      </q-item>

      <!-- Settings -->
      <q-item
        clickable
        :to="{ name: 'Settings' }"
        active-class="sidebar-item-active"
        class="sidebar-item"
      >
        <q-item-section avatar>
          <q-icon name="settings" />
        </q-item-section>
        <q-item-section v-if="drawerExpanded">
          <q-item-label>Settings</q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </q-drawer>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()

// Drawer state
const drawerOpen = ref(true)
const drawerExpanded = ref(true)

// Methods
function toggleDrawer() {
  drawerExpanded.value = !drawerExpanded.value
}

// Expose drawerOpen for parent component to control
defineExpose({
  drawerOpen
})
</script>

<style scoped>
.sidebar-drawer {
  background: #fff;
  transition: width 0.3s ease;
}

.sidebar-toggle {
  display: flex;
  justify-content: flex-end;
  padding: 8px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.sidebar-menu {
  padding: 8px 0;
}

.sidebar-item {
  border-radius: 0;
  margin: 2px 8px;
  transition: all 0.2s;
}

.sidebar-item:hover {
  background: rgba(0, 0, 0, 0.04);
}

.sidebar-item-active {
  background: rgba(25, 118, 210, 0.12);
  color: #1976D2;
}

.sidebar-item-active .q-icon {
  color: #1976D2;
}

.sidebar-section-header {
  font-size: 12px;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.6);
  text-transform: uppercase;
  padding: 16px 16px 8px 16px;
  margin: 0;
}

.sidebar-item .q-item__section--avatar {
  min-width: 40px;
  padding-right: 0;
}

.sidebar-item .q-icon {
  font-size: 24px;
  color: rgba(0, 0, 0, 0.54);
}
</style>
