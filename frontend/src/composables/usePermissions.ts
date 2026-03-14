import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function usePermissions() {
  const authStore = useAuthStore()

  const user = computed(() => authStore.user)

  /**
   * Check if the current user has a specific permission.
   * Permissions are cumulative across all user's roles.
   * @param permission - Permission name in format "module.action" (e.g., "users.view")
   * @returns true if user has the permission, false otherwise
   */
  function can(permission: string): boolean {
    if (!user.value || !user.value.roles) {
      return false
    }

    // Flatten all permissions from all roles
    const allPermissions = user.value.roles.flatMap(
      role => role.permissions?.map(p => p.name) || []
    )

    return allPermissions.includes(permission)
  }

  /**
   * Check if the current user has a specific role.
   * @param roleName - Role name or slug to check
   * @returns true if user has the role, false otherwise
   */
  function hasRole(roleName: string): boolean {
    if (!user.value || !user.value.roles) {
      return false
    }

    return user.value.roles.some(role =>
      role.slug === roleName || role.name === roleName
    )
  }

  /**
   * Check if the current user has any of the specified roles.
   * @param roleNames - Array of role names or slugs
   * @returns true if user has at least one of the roles, false otherwise
   */
  function hasAnyRole(roleNames: string[]): boolean {
    if (!user.value || !user.value.roles) {
      return false
    }

    return roleNames.some(roleName => hasRole(roleName))
  }

  /**
   * Check if the current user has all of the specified permissions.
   * @param permissions - Array of permission names
   * @returns true if user has all permissions, false otherwise
   */
  function hasAllPermissions(permissions: string[]): boolean {
    return permissions.every(permission => can(permission))
  }

  /**
   * Computed property to check if current user is an admin.
   */
  const isAdmin = computed(() => hasRole('admin'))

  return {
    can,
    hasRole,
    hasAnyRole,
    hasAllPermissions,
    isAdmin,
  }
}
