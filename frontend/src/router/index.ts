import { createRouter, createWebHistory, type Router, type RouteRecordRaw, type NavigationGuardNext, type RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePermissions } from '@/composables/usePermissions'
import MainLayout from '@/layouts/MainLayout.vue'
import LoginPage from '@/pages/LoginPage.vue'
import DashboardPage from '@/pages/DashboardPage.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: MainLayout,
    children: [
      {
        path: '',
        redirect: '/dashboard'
      },
      {
        path: '/login',
        name: 'Login',
        component: LoginPage,
        meta: { requiresGuest: true }
      },
      {
        path: '/dashboard',
        name: 'Dashboard',
        component: DashboardPage,
        meta: { requiresAuth: true }
      },
      // Scheduling
      {
        path: '/schedule',
        name: 'DailySchedule',
        component: () => import('@/pages/DailySchedulePage.vue'),
        meta: { requiresAuth: true, permission: 'schedules.view' }
      },
      // Projects
      {
        path: '/projects',
        name: 'Projects',
        component: () => import('@/pages/ProjectsPage.vue'),
        meta: { requiresAuth: true, permission: 'projects.view' }
      },
      // Equipment
      {
        path: '/equipment',
        name: 'Equipment',
        component: () => import('@/pages/EquipmentPage.vue'),
        meta: { requiresAuth: true, permission: 'equipment.view' }
      },
      {
        path: '/equipment/move',
        name: 'EquipmentMovement',
        component: () => import('@/pages/EquipmentMovementPage.vue'),
        meta: { requiresAuth: true, permission: 'equipment.move' }
      },
      // Transport Dispatch
      {
        path: '/dispatch',
        name: 'DispatchDashboard',
        component: () => import('@/pages/DispatchDashboardPage.vue'),
        meta: { requiresAuth: true, permission: 'transport.view' }
      },
      {
        path: '/my-assignments',
        name: 'DriverAssignments',
        component: () => import('@/pages/DriverAssignmentsPage.vue'),
        meta: { requiresAuth: true, permission: 'transport.execute' }
      },
      // Vehicles
      {
        path: '/vehicles',
        name: 'Vehicles',
        component: () => import('@/pages/VehiclesPage.vue'),
        meta: { requiresAuth: true, permission: 'vehicles.view' }
      },
      // Inventory
      {
        path: '/inventory',
        name: 'Inventory',
        component: () => import('@/pages/InventoryPage.vue'),
        meta: { requiresAuth: true, permission: 'inventory.view' }
      },
      {
        path: '/checkout',
        name: 'MaterialCheckout',
        component: () => import('@/pages/EmployeeCheckoutPage.vue'),
        meta: { requiresAuth: true, title: 'Material Checkout' }
      },
      // Users & Roles
      {
        path: '/users',
        name: 'Users',
        component: () => import('@/pages/UsersPage.vue'),
        meta: { requiresAuth: true, permission: 'users.view' }
      },
      {
        path: '/roles',
        name: 'Roles',
        component: () => import('@/pages/RolesPage.vue'),
        meta: { requiresAuth: true, permission: 'roles.view' }
      },
      // Vacations
      {
        path: '/vacations',
        name: 'Vacations',
        component: () => import('@/pages/VacationsPage.vue'),
        meta: { requiresAuth: true, permission: 'vacations.view' }
      },
      // Companies & Contacts
      {
        path: '/companies',
        name: 'Companies',
        component: () => import('@/pages/CompaniesPage.vue'),
        meta: { requiresAuth: true, permission: 'companies.view' }
      },
      {
        path: '/contacts',
        name: 'Contacts',
        component: () => import('@/pages/ContactsPage.vue'),
        meta: { requiresAuth: true, permission: 'contacts.view' }
      },
      // Reports
      {
        path: '/reports/available-resources',
        name: 'AvailableResourcesReport',
        component: () => import('@/pages/reports/AvailableResourcesReportPage.vue'),
        meta: { requiresAuth: true, permission: 'reports.view' }
      },
      {
        path: '/reports/equipment-locations',
        name: 'EquipmentLocationReport',
        component: () => import('@/pages/reports/EquipmentLocationReportPage.vue'),
        meta: { requiresAuth: true, permission: 'reports.view' }
      },
      {
        path: '/reports/inventory-status',
        name: 'InventoryStatusReport',
        component: () => import('@/pages/reports/InventoryStatusReportPage.vue'),
        meta: { requiresAuth: true, permission: 'reports.view' }
      },
      // Settings
      {
        path: '/settings',
        name: 'Settings',
        component: () => import('@/pages/SettingsPage.vue'),
        meta: { requiresAuth: true }
      },
      // Email Test
      {
        path: '/email-test',
        name: 'EmailTest',
        component: () => import('@/pages/EmailTestPage.vue'),
        meta: { requiresAuth: true }
      }
    ]
  }
]

const router: Router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((
  to: RouteLocationNormalized,
  _from: RouteLocationNormalized,
  next: NavigationGuardNext
): void => {
  const authStore = useAuthStore()
  const { can } = usePermissions()
  const isAuthenticated = authStore.isAuthenticated

  // Check authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login')
    return
  }

  if (to.meta.requiresGuest && isAuthenticated) {
    next('/dashboard')
    return
  }

  // Check permissions
  if (to.meta.permission && isAuthenticated) {
    const permission = to.meta.permission as string
    if (!can(permission)) {
      // User doesn't have required permission
      console.warn(`Access denied: User lacks permission "${permission}" for route ${to.path}`)
      next('/dashboard') // Redirect to dashboard
      return
    }
  }

  next()
})

export default router
