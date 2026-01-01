<template>
  <q-page class="settings-page">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="text-h4 text-weight-bold">Settings</h1>
    </div>

    <!-- Settings Content -->
    <div class="settings-content">
      <!-- Color Scheme Section -->
      <q-card flat bordered class="settings-card">
        <q-card-section>
          <div class="text-h6 q-mb-md">Color Scheme</div>

          <!-- Preset Themes -->
          <div class="theme-section">
            <div class="section-label">Preset Themes</div>
            <div class="theme-grid">
              <q-card
                v-for="theme in presetThemes"
                :key="theme.name"
                class="theme-card"
                :class="{ 'theme-active': selectedTheme === theme.name }"
                clickable
                @click="selectTheme(theme.name)"
              >
                <q-card-section class="theme-preview">
                  <div class="color-dots">
                    <div class="color-dot" :style="{ backgroundColor: theme.colors.primary }"></div>
                    <div class="color-dot" :style="{ backgroundColor: theme.colors.secondary }"></div>
                    <div class="color-dot" :style="{ backgroundColor: theme.colors.accent }"></div>
                  </div>
                  <div class="theme-name">{{ theme.label }}</div>
                  <q-icon
                    v-if="selectedTheme === theme.name"
                    name="check_circle"
                    color="positive"
                    size="24px"
                    class="theme-check"
                  />
                </q-card-section>
              </q-card>
            </div>
          </div>

          <q-separator class="q-my-lg" />

          <!-- Custom Theme -->
          <div class="theme-section">
            <div class="section-label">Custom Theme</div>
            <q-card
              class="theme-card custom-theme-card"
              :class="{ 'theme-active': selectedTheme === 'custom' }"
              clickable
              @click="selectTheme('custom')"
            >
              <q-card-section>
                <div class="row items-center">
                  <div class="color-dots">
                    <div class="color-dot" :style="{ backgroundColor: customColors.primary }"></div>
                    <div class="color-dot" :style="{ backgroundColor: customColors.secondary }"></div>
                    <div class="color-dot" :style="{ backgroundColor: customColors.accent }"></div>
                  </div>
                  <div class="theme-name">Custom</div>
                  <q-space />
                  <q-icon
                    v-if="selectedTheme === 'custom'"
                    name="check_circle"
                    color="positive"
                    size="24px"
                  />
                </div>
              </q-card-section>
            </q-card>

            <!-- Custom Color Pickers -->
            <div v-if="selectedTheme === 'custom'" class="custom-colors-section q-mt-md">
              <div class="row q-col-gutter-md">
                <!-- Primary Color -->
                <div class="col-12 col-md-4">
                  <div class="color-picker-wrapper">
                    <div class="color-label">Primary Color</div>
                    <div class="color-input-wrapper">
                      <input
                        type="color"
                        v-model="customColors.primary"
                        class="color-picker"
                      />
                      <q-input
                        v-model="customColors.primary"
                        outlined
                        dense
                        readonly
                        class="color-text-input"
                      />
                    </div>
                  </div>
                </div>

                <!-- Secondary Color -->
                <div class="col-12 col-md-4">
                  <div class="color-picker-wrapper">
                    <div class="color-label">Secondary Color</div>
                    <div class="color-input-wrapper">
                      <input
                        type="color"
                        v-model="customColors.secondary"
                        class="color-picker"
                      />
                      <q-input
                        v-model="customColors.secondary"
                        outlined
                        dense
                        readonly
                        class="color-text-input"
                      />
                    </div>
                  </div>
                </div>

                <!-- Accent Color -->
                <div class="col-12 col-md-4">
                  <div class="color-picker-wrapper">
                    <div class="color-label">Accent Color</div>
                    <div class="color-input-wrapper">
                      <input
                        type="color"
                        v-model="customColors.accent"
                        class="color-picker"
                      />
                      <q-input
                        v-model="customColors.accent"
                        outlined
                        dense
                        readonly
                        class="color-text-input"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Additional Colors -->
              <div class="row q-col-gutter-md q-mt-md">
                <!-- Positive -->
                <div class="col-12 col-md-3">
                  <div class="color-picker-wrapper">
                    <div class="color-label">Positive (Success)</div>
                    <div class="color-input-wrapper">
                      <input
                        type="color"
                        v-model="customColors.positive"
                        class="color-picker"
                      />
                      <q-input
                        v-model="customColors.positive"
                        outlined
                        dense
                        readonly
                        class="color-text-input"
                      />
                    </div>
                  </div>
                </div>

                <!-- Negative -->
                <div class="col-12 col-md-3">
                  <div class="color-picker-wrapper">
                    <div class="color-label">Negative (Error)</div>
                    <div class="color-input-wrapper">
                      <input
                        type="color"
                        v-model="customColors.negative"
                        class="color-picker"
                      />
                      <q-input
                        v-model="customColors.negative"
                        outlined
                        dense
                        readonly
                        class="color-text-input"
                      />
                    </div>
                  </div>
                </div>

                <!-- Warning -->
                <div class="col-12 col-md-3">
                  <div class="color-picker-wrapper">
                    <div class="color-label">Warning</div>
                    <div class="color-input-wrapper">
                      <input
                        type="color"
                        v-model="customColors.warning"
                        class="color-picker"
                      />
                      <q-input
                        v-model="customColors.warning"
                        outlined
                        dense
                        readonly
                        class="color-text-input"
                      />
                    </div>
                  </div>
                </div>

                <!-- Info -->
                <div class="col-12 col-md-3">
                  <div class="color-picker-wrapper">
                    <div class="color-label">Info</div>
                    <div class="color-input-wrapper">
                      <input
                        type="color"
                        v-model="customColors.info"
                        class="color-picker"
                      />
                      <q-input
                        v-model="customColors.info"
                        outlined
                        dense
                        readonly
                        class="color-text-input"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <q-separator class="q-my-lg" />

          <!-- Preview Section -->
          <div class="preview-section">
            <div class="section-label">Preview</div>
            <div class="preview-grid">
              <q-btn color="primary" label="Primary" unelevated />
              <q-btn color="secondary" label="Secondary" unelevated />
              <q-btn color="accent" label="Accent" unelevated />
              <q-btn color="positive" label="Success" unelevated />
              <q-btn color="negative" label="Error" unelevated />
              <q-btn color="warning" label="Warning" unelevated />
              <q-btn color="info" label="Info" unelevated />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn
            flat
            label="Reset to Default"
            color="grey"
            @click="resetToDefault"
          />
          <q-btn
            label="Apply Changes"
            color="primary"
            unelevated
            @click="applyTheme"
          />
        </q-card-actions>
      </q-card>

      <!-- Quick Actions Configuration -->
      <q-card flat bordered class="settings-card">
        <q-card-section>
          <div class="text-h6 q-mb-md">Quick Actions</div>
          <p class="text-caption text-grey-7 q-mb-md">
            Customize which quick action links appear on your dashboard. Drag to reorder.
          </p>

          <!-- Active Quick Actions -->
          <div class="section-label">Active Actions ({{ activeActions.length }}/8)</div>
          <draggable
            v-model="activeActions"
            item-key="route"
            class="actions-list active-actions"
            group="actions"
            :animation="200"
          >
            <template #item="{ element }">
              <div class="action-item">
                <q-icon name="drag_indicator" class="drag-handle" />
                <q-icon :name="element.icon" :color="element.color" size="24px" />
                <div class="action-info">
                  <div class="action-title">{{ element.title }}</div>
                  <div class="action-description">{{ element.description }}</div>
                </div>
                <q-btn
                  flat
                  round
                  dense
                  icon="remove_circle"
                  color="negative"
                  @click="removeAction(element)"
                >
                  <q-tooltip>Remove</q-tooltip>
                </q-btn>
              </div>
            </template>
          </draggable>

          <q-separator class="q-my-md" />

          <!-- Available Quick Actions -->
          <div class="section-label">Available Actions</div>
          <div class="actions-grid">
            <div
              v-for="action in availableActions"
              :key="action.route"
              class="available-action-item"
              @click="addAction(action)"
            >
              <q-icon :name="action.icon" :color="action.color" size="32px" />
              <div class="action-title">{{ action.title }}</div>
              <q-btn
                round
                dense
                icon="add_circle"
                color="positive"
                size="sm"
                class="add-btn"
              >
                <q-tooltip>Add to Dashboard</q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn
            flat
            label="Reset to Default"
            color="grey"
            @click="resetQuickActions"
          />
          <q-btn
            label="Save Quick Actions"
            color="primary"
            unelevated
            @click="saveQuickActions"
          />
        </q-card-actions>
      </q-card>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useQuasar, setCssVar } from 'quasar'
import draggable from 'vuedraggable'

const $q = useQuasar()

// Quick Actions Types
interface QuickAction {
  title: string
  description: string
  icon: string
  color: string
  route: string
}

// All available quick actions (including Companies)
const allQuickActions: QuickAction[] = [
  {
    title: 'Daily Schedule',
    description: "Manage today's work schedule",
    icon: 'calendar_today',
    color: 'primary',
    route: '/schedule'
  },
  {
    title: 'Projects',
    description: 'View and manage projects',
    icon: 'business_center',
    color: 'secondary',
    route: '/projects'
  },
  {
    title: 'Companies',
    description: 'Manage customers and vendors',
    icon: 'business',
    color: 'blue-7',
    route: '/companies'
  },
  {
    title: 'Contacts',
    description: 'Manage company contacts',
    icon: 'contacts',
    color: 'teal',
    route: '/contacts'
  },
  {
    title: 'Employees',
    description: 'Manage crew members',
    icon: 'people',
    color: 'positive',
    route: '/users'
  },
  {
    title: 'Equipment',
    description: 'Track equipment status',
    icon: 'engineering',
    color: 'warning',
    route: '/equipment'
  },
  {
    title: 'Inventory',
    description: 'Track materials and supplies',
    icon: 'inventory_2',
    color: 'orange',
    route: '/inventory'
  },
  {
    title: 'Vacations',
    description: 'Approve time off requests',
    icon: 'beach_access',
    color: 'info',
    route: '/vacations'
  },
  {
    title: 'Reports',
    description: 'View detailed reports',
    icon: 'analytics',
    color: 'purple',
    route: '/reports/available-resources'
  },
  {
    title: 'Roles & Permissions',
    description: 'Manage user access',
    icon: 'admin_panel_settings',
    color: 'deep-purple',
    route: '/roles'
  }
]

// Default quick actions (first 6)
const defaultQuickActions = allQuickActions.slice(0, 6)

// Active and available actions state
const activeActions = ref<QuickAction[]>([...defaultQuickActions])

// Available actions - those not currently active
const availableActions = computed(() => {
  return allQuickActions.filter(
    action => !activeActions.value.some(active => active.route === action.route)
  )
})

// Quick Actions Methods
function addAction(action: QuickAction) {
  if (activeActions.value.length >= 8) {
    $q.notify({
      type: 'warning',
      message: 'Maximum 8 quick actions allowed'
    })
    return
  }

  if (!activeActions.value.some(a => a.route === action.route)) {
    activeActions.value.push(action)
  }
}

function removeAction(action: QuickAction) {
  activeActions.value = activeActions.value.filter(a => a.route !== action.route)
}

function saveQuickActions() {
  localStorage.setItem('app-quick-actions', JSON.stringify(activeActions.value))
  $q.notify({
    type: 'positive',
    message: 'Quick actions saved successfully'
  })
}

function resetQuickActions() {
  activeActions.value = [...defaultQuickActions]
  localStorage.removeItem('app-quick-actions')
  $q.notify({
    type: 'info',
    message: 'Quick actions reset to default'
  })
}

function loadQuickActions() {
  const saved = localStorage.getItem('app-quick-actions')
  if (saved) {
    try {
      const parsed = JSON.parse(saved)
      // Validate that saved actions still exist in allQuickActions
      activeActions.value = parsed.filter((action: QuickAction) =>
        allQuickActions.some(a => a.route === action.route)
      )
    } catch (error) {
      console.error('Failed to parse saved quick actions:', error)
    }
  }
}

// Theme state
const selectedTheme = ref('default')
const customColors = ref({
  primary: '#1976D2',
  secondary: '#26A69A',
  accent: '#9C27B0',
  positive: '#21BA45',
  negative: '#C10015',
  warning: '#F2C037',
  info: '#31CCEC'
})

// Preset themes
const presetThemes = [
  {
    name: 'default',
    label: 'Default Blue',
    colors: {
      primary: '#1976D2',
      secondary: '#26A69A',
      accent: '#9C27B0',
      positive: '#21BA45',
      negative: '#C10015',
      warning: '#F2C037',
      info: '#31CCEC'
    }
  },
  {
    name: 'dark-blue',
    label: 'Dark Blue',
    colors: {
      primary: '#0D47A1',
      secondary: '#00838F',
      accent: '#6A1B9A',
      positive: '#2E7D32',
      negative: '#C62828',
      warning: '#F57C00',
      info: '#0277BD'
    }
  },
  {
    name: 'green',
    label: 'Green Nature',
    colors: {
      primary: '#43A047',
      secondary: '#66BB6A',
      accent: '#8BC34A',
      positive: '#4CAF50',
      negative: '#E53935',
      warning: '#FFA726',
      info: '#29B6F6'
    }
  },
  {
    name: 'purple',
    label: 'Purple',
    colors: {
      primary: '#7B1FA2',
      secondary: '#AB47BC',
      accent: '#BA68C8',
      positive: '#66BB6A',
      negative: '#EF5350',
      warning: '#FFCA28',
      info: '#42A5F5'
    }
  },
  {
    name: 'orange',
    label: 'Orange Sunset',
    colors: {
      primary: '#F57C00',
      secondary: '#FF9800',
      accent: '#FFB74D',
      positive: '#66BB6A',
      negative: '#E53935',
      warning: '#FFA726',
      info: '#42A5F5'
    }
  },
  {
    name: 'teal',
    label: 'Teal Ocean',
    colors: {
      primary: '#00897B',
      secondary: '#26A69A',
      accent: '#4DB6AC',
      positive: '#66BB6A',
      negative: '#EF5350',
      warning: '#FFCA28',
      info: '#29B6F6'
    }
  }
]

function selectTheme(themeName: string) {
  selectedTheme.value = themeName

  if (themeName !== 'custom') {
    const theme = presetThemes.find(t => t.name === themeName)
    if (theme) {
      // Update custom colors to match preset (in case user switches back to custom)
      customColors.value = { ...theme.colors }
    }
  }
}

function applyTheme() {
  const themeColors = selectedTheme.value === 'custom'
    ? customColors.value
    : presetThemes.find(t => t.name === selectedTheme.value)?.colors || customColors.value

  // Apply colors using Quasar's setCssVar on document root
  setCssVar('primary', themeColors.primary, document.documentElement)
  setCssVar('secondary', themeColors.secondary, document.documentElement)
  setCssVar('accent', themeColors.accent, document.documentElement)
  setCssVar('positive', themeColors.positive, document.documentElement)
  setCssVar('negative', themeColors.negative, document.documentElement)
  setCssVar('warning', themeColors.warning, document.documentElement)
  setCssVar('info', themeColors.info, document.documentElement)

  // Save to localStorage
  localStorage.setItem('app-theme', selectedTheme.value)
  localStorage.setItem('app-custom-colors', JSON.stringify(customColors.value))

  $q.notify({
    type: 'positive',
    message: 'Theme applied successfully'
  })
}

function resetToDefault() {
  selectedTheme.value = 'default'
  customColors.value = { ...presetThemes[0].colors }
  applyTheme()
}

function loadSavedTheme() {
  const savedTheme = localStorage.getItem('app-theme')
  const savedColors = localStorage.getItem('app-custom-colors')

  if (savedTheme) {
    selectedTheme.value = savedTheme
  }

  if (savedColors) {
    try {
      customColors.value = JSON.parse(savedColors)
    } catch (error) {
      console.error('Failed to parse saved colors:', error)
    }
  }

  // Apply the saved theme without showing notification
  if (savedTheme) {
    const themeColors = selectedTheme.value === 'custom'
      ? customColors.value
      : presetThemes.find(t => t.name === selectedTheme.value)?.colors || customColors.value

    // Apply colors using Quasar's setCssVar on document root
    setCssVar('primary', themeColors.primary, document.documentElement)
    setCssVar('secondary', themeColors.secondary, document.documentElement)
    setCssVar('accent', themeColors.accent, document.documentElement)
    setCssVar('positive', themeColors.positive, document.documentElement)
    setCssVar('negative', themeColors.negative, document.documentElement)
    setCssVar('warning', themeColors.warning, document.documentElement)
    setCssVar('info', themeColors.info, document.documentElement)
  }
}

onMounted(() => {
  loadSavedTheme()
  loadQuickActions()
})
</script>

<style scoped>
.settings-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

.page-header {
  margin-bottom: 24px;
}

.settings-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.settings-card {
  background: white;
}

.theme-section {
  margin-bottom: 16px;
}

.section-label {
  font-size: 14px;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.6);
  text-transform: uppercase;
  margin-bottom: 16px;
  letter-spacing: 0.5px;
}

.theme-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 16px;
}

.theme-card {
  position: relative;
  border: 2px solid transparent;
  transition: all 0.3s;
  cursor: pointer;
}

.theme-card:hover {
  border-color: rgba(25, 118, 210, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.theme-active {
  border-color: #1976D2;
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
}

.theme-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 20px 16px;
}

.color-dots {
  display: flex;
  gap: 8px;
}

.color-dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.theme-name {
  font-size: 14px;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.87);
}

.theme-check {
  position: absolute;
  top: 8px;
  right: 8px;
}

.custom-theme-card {
  border: 2px dashed rgba(0, 0, 0, 0.12);
}

.custom-colors-section {
  padding: 16px;
  background: rgba(0, 0, 0, 0.02);
  border-radius: 8px;
}

.color-picker-wrapper {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.color-label {
  font-size: 12px;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.6);
  text-transform: uppercase;
}

.color-input-wrapper {
  display: flex;
  gap: 8px;
  align-items: center;
}

.color-picker {
  width: 60px;
  height: 40px;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 4px;
  cursor: pointer;
}

.color-text-input {
  flex: 1;
}

.preview-section {
  margin-top: 16px;
}

.preview-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

/* Quick Actions Styles */
.actions-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-height: 100px;
  padding: 8px;
  background: rgba(0, 0, 0, 0.02);
  border-radius: 8px;
  border: 2px dashed rgba(0, 0, 0, 0.12);
}

.active-actions {
  background: rgba(25, 118, 210, 0.04);
  border-color: rgba(25, 118, 210, 0.2);
}

.action-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: white;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  cursor: move;
  transition: all 0.2s;
}

.action-item:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.drag-handle {
  color: rgba(0, 0, 0, 0.3);
  cursor: grab;
}

.drag-handle:active {
  cursor: grabbing;
}

.action-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.action-title {
  font-size: 14px;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.87);
}

.action-description {
  font-size: 12px;
  color: rgba(0, 0, 0, 0.6);
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
}

.available-action-item {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px;
  background: white;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.available-action-item:hover {
  border-color: rgba(25, 118, 210, 0.5);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.available-action-item .action-title {
  text-align: center;
  font-size: 13px;
}

.available-action-item .add-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  opacity: 0;
  transition: opacity 0.2s;
}

.available-action-item:hover .add-btn {
  opacity: 1;
}

@media (max-width: 768px) {
  .settings-page {
    padding: 16px;
  }

  .theme-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .preview-grid {
    flex-direction: column;
  }

  .actions-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .action-item {
    padding: 10px 12px;
  }

  .action-title {
    font-size: 13px;
  }

  .action-description {
    font-size: 11px;
  }
}
</style>
