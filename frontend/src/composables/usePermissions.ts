import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function usePermissions() {
  const authStore = useAuthStore()

  const user = computed(() => authStore.user)

  function can(_permission: string): boolean {
    // TODO: Implement proper permission checking when user RBAC is fully implemented
    // For now, return true for all logged-in users
    return !!user.value
  }

  function hasRole(_roleName: string): boolean {
    // TODO: Implement proper role checking when user RBAC is fully implemented
    // For now, return true for all logged-in users
    return !!user.value
  }

  function hasAnyRole(_roleNames: string[]): boolean {
    // TODO: Implement proper role checking when user RBAC is fully implemented
    // For now, return true for all logged-in users
    return !!user.value
  }

  function hasAllPermissions(permissions: string[]): boolean {
    return permissions.every(permission => can(permission))
  }

  const isAdmin = computed(() => hasRole('admin'))

  return {
    can,
    hasRole,
    hasAnyRole,
    hasAllPermissions,
    isAdmin,
  }
}
