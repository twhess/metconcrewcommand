# RBAC System - Implementation Status & Future Planning

**Last Updated**: February 14, 2026

## Overview

The MetCon Crew Command application now has a **complete Role-Based Access Control (RBAC) system**. Users can have multiple roles, roles contain multiple permissions, and permissions are cumulative across all user's roles.

---

## ✅ Implemented Features

### Backend Authorization

1. **Authentication Enhanced**
   - Login and `/me` endpoints now return full user data with roles and nested permissions
   - Frontend can access user's complete permission set

2. **Policies Registered**
   - `UserPolicy`, `ProjectPolicy`, `EquipmentPolicy`, `SchedulePolicy` registered in `AppServiceProvider`
   - All policies use `User::hasPermission()` method to check permissions

3. **Controllers Protected**
   - **UserController**: All CRUD operations check permissions (viewAny, create, view, update, delete, assignRoles)
   - **ProjectController**: All CRUD operations protected
   - **EquipmentController**: All CRUD + special `move` action protected
   - Authorization via `$this->authorize()` calls before operations

4. **Permission API**
   - `GET /api/permissions` - Returns all permissions grouped by module
   - Used by frontend to display permission checkboxes in role management

5. **Database & Seeders**
   - **16 roles** seeded (Admin, Project Manager, Foreman, Laborer, etc.)
   - **33+ permissions** across 10 modules
   - Permissions in `module.action` format (e.g., `users.view`, `equipment.move`)
   - Permission assignments seeded for common roles

### Frontend Authorization

1. **usePermissions Composable** - Fully implemented
   - `can(permission)` - Check if user has specific permission
   - `hasRole(roleName)` - Check if user has specific role
   - `hasAnyRole(roleNames)` - Check if user has any of specified roles
   - `hasAllPermissions(permissions)` - Check if user has all specified permissions
   - `isAdmin` - Computed property for admin check

2. **Permission Store** - New Pinia store
   - Fetches all permissions grouped by module
   - Used by RolesPage to display permissions in organized UI
   - Helper methods: `getPermissionsByModule()`, `getAllModules()`

3. **Router Guards Enhanced**
   - Checks `meta.permission` field on routes
   - Redirects to dashboard if user lacks required permission
   - Warning logged to console for debugging

4. **RolesPage** - Complete UI for role management
   - Table showing all roles with permission counts
   - Create/Edit role dialogs
   - Permission assignment dialog with permissions grouped by module
   - "Select All" per module for easy bulk assignment
   - Delete role with confirmation (admin role protected)
   - All actions respect user permissions

5. **Sidebar** - Already has permission checks
   - Most menu items use `v-if="can('module.view')"`
   - Only shows items user has access to

---

## 🔄 Partially Complete / Needs Enhancement

### Controllers Still Needing Authorization

The following controllers exist but **do NOT have authorization** implemented yet:

1. **ScheduleController** - schedules.view, schedules.create, schedules.update, schedules.delete
2. **ContactController** - contacts.view, contacts.create, contacts.update, contacts.delete
3. **CompanyController** - companies.view, companies.create, companies.update, companies.delete
4. **InventoryController** - inventory.view, inventory.create, inventory.update, inventory.delete, inventory.adjust
5. **VacationController** - vacations.view, vacations.create, vacations.update, vacations.delete, vacations.approve
6. **VehicleController** - Similar to equipment (CRUD + special actions)
7. **ReportController** - reports.view (single permission for all reports)
8. **MaintenanceController** - maintenance permissions
9. **RoleController** - roles.view, roles.create, roles.update, roles.delete (manage the RBAC system itself)

### Frontend Pages Needing Permission Guards

The following pages exist but **do NOT have UI permission guards** on buttons/actions:

1. **EquipmentPage** - Add/Edit/Delete buttons need `v-if="can('equipment.create')"`
2. **ProjectsPage** - Add/Edit/Delete buttons
3. **ContactsPage** - Add/Edit/Delete buttons
4. **CompaniesPage** - Add/Edit/Delete buttons
5. **UsersPage** - Add/Edit/Delete buttons + Role Assignment button
6. **InventoryPage** - CRUD buttons
7. **VacationsPage** - Add/Edit/Delete/Approve buttons
8. **SchedulePage** - CRUD + assignment buttons

### User Management Enhancement

**UsersPage** needs a "Assign Roles" feature:
- Dialog showing available roles as checkboxes
- Pre-select user's current roles
- Submit to `POST /users/{id}/roles` (endpoint exists)
- Only visible if `can('users.update') && can('roles.view')`

---

## 📋 Permission Format & Module Structure

### Current Permissions (33+)

**Format**: `module.action`

#### Modules (10):
1. **users** - view, create, update, delete
2. **roles** - view, create, update, delete
3. **companies** - view, create, update, delete
4. **contacts** - view, create, update, delete
5. **projects** - view, create, update, delete
6. **schedules** - view, create, update, delete
7. **equipment** - view, create, update, delete, move
8. **inventory** - view, create, update, delete, adjust
9. **reports** - view
10. **vacations** - view, create, update, delete, approve

### Standard CRUD Permissions
- `module.view` - List and view records
- `module.create` - Create new records
- `module.update` - Edit existing records
- `module.delete` - Delete records

### Special Action Permissions
- `equipment.move` - Move equipment between locations
- `inventory.adjust` - Adjust inventory quantities
- `vacations.approve` - Approve vacation requests

---

## 🚀 Adding Permissions for New Features

When you add a new feature that needs access control, follow this process:

### 1. Define Permission in PermissionSeeder

**File**: `backend/database/seeders/PermissionSeeder.php`

Add to the appropriate module or create a new module:

```php
// Example: Adding vehicle maintenance tracking
$modules = [
    // ... existing modules
    'vehicles' => ['view', 'create', 'update', 'delete', 'move', 'assign'],
    'maintenance' => ['view', 'create', 'update', 'delete', 'schedule'],
];
```

Run seeder: `php artisan db:seed --class=PermissionSeeder`

### 2. Assign Permission to Roles

**File**: `backend/database/seeders/RolePermissionSeeder.php`

```php
// Example: Give maintenance permissions to admins and supervisors
$admin->givePermissionTo([
    'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete', 'vehicles.move',
    'maintenance.view', 'maintenance.create', 'maintenance.update', 'maintenance.delete', 'maintenance.schedule',
]);

$supervisor->givePermissionTo([
    'vehicles.view', 'vehicles.move',
    'maintenance.view', 'maintenance.schedule',
]);
```

Run seeder: `php artisan db:seed --class=RolePermissionSeeder`

### 3. Create Policy (If Needed)

**File**: `backend/app/Policies/VehiclePolicy.php`

```php
class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('vehicles.view');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('vehicles.create');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.update');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.delete');
    }

    // Custom actions
    public function move(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.move');
    }

    public function assign(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermission('vehicles.assign');
    }
}
```

### 4. Register Policy

**File**: `backend/app/Providers/AppServiceProvider.php`

```php
use App\Models\Vehicle;
use App\Policies\VehiclePolicy;

public function boot(): void
{
    // ... existing policies
    Gate::policy(Vehicle::class, VehiclePolicy::class);
}
```

### 5. Add Authorization to Controller

**File**: `backend/app/Http/Controllers/Api/VehicleController.php`

```php
public function index()
{
    $this->authorize('viewAny', Vehicle::class);
    // ... rest of method
}

public function store(Request $request)
{
    $this->authorize('create', Vehicle::class);
    // ... rest of method
}

public function move(Request $request, int $id)
{
    $vehicle = Vehicle::findOrFail($id);
    $this->authorize('move', $vehicle);
    // ... rest of method
}
```

### 6. Add Route with Permission Meta

**File**: `frontend/src/router/index.ts`

```typescript
{
  path: '/vehicles',
  name: 'Vehicles',
  component: () => import('@/pages/VehiclesPage.vue'),
  meta: { requiresAuth: true, permission: 'vehicles.view' }
},
{
  path: '/vehicles/move',
  name: 'VehicleMovement',
  component: () => import('@/pages/VehicleMovementPage.vue'),
  meta: { requiresAuth: true, permission: 'vehicles.move' }
}
```

### 7. Add Permission Guards to Frontend UI

**File**: `frontend/src/pages/VehiclesPage.vue`

```vue
<template>
  <q-page>
    <!-- Add button only if user can create -->
    <q-btn
      v-if="can('vehicles.create')"
      @click="showCreateDialog"
      label="Add Vehicle"
    />

    <!-- Edit button only if user can update -->
    <q-btn
      v-if="can('vehicles.update')"
      @click="showEditDialog(vehicle)"
      icon="edit"
    />

    <!-- Delete button only if user can delete -->
    <q-btn
      v-if="can('vehicles.delete')"
      @click="deleteVehicle(vehicle)"
      icon="delete"
    />

    <!-- Special action button -->
    <q-btn
      v-if="can('vehicles.move')"
      @click="showMoveDialog(vehicle)"
      label="Move Vehicle"
    />
  </q-page>
</template>

<script setup lang="ts">
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()
</script>
```

### 8. Add to Sidebar (If Needed)

**File**: `frontend/src/components/Sidebar.vue`

```vue
<q-item
  v-if="can('vehicles.view')"
  clickable
  :to="{ name: 'Vehicles' }"
  active-class="sidebar-item-active"
  class="sidebar-item"
>
  <q-item-section avatar>
    <q-icon name="local_shipping" />
  </q-item-section>
  <q-item-section v-if="drawerExpanded">
    <q-item-label>Vehicles</q-item-label>
  </q-item-section>
</q-item>
```

---

## 🎯 Recommended Next Steps

### Immediate (High Priority)

1. **Add authorization to remaining controllers**
   - Start with most-used: ScheduleController, ContactController, CompanyController
   - Follow pattern from UserController/ProjectController/EquipmentController

2. **Add UI permission guards to existing pages**
   - EquipmentPage - Add/Edit/Delete buttons
   - ContactsPage - Add/Edit/Delete buttons
   - ProjectsPage - Add/Edit/Delete buttons

3. **Enhance UsersPage with role assignment**
   - Add "Assign Roles" button/dialog
   - Show user's current roles
   - Submit to existing `POST /users/{id}/roles` endpoint

### Medium Priority

4. **Add authorization to specialty controllers**
   - VehicleController
   - MaintenanceController
   - InventoryController
   - ReportController

5. **Create comprehensive permission testing**
   - Test each role can only access permitted features
   - Test permission denial redirects correctly
   - Test admin has full access

### Low Priority

6. **Enhanced permission features**
   - Permission groups/categories for easier management
   - Role templates for quick setup
   - Audit log for permission changes
   - Permission inheritance/hierarchy

7. **User experience improvements**
   - Better "Access Denied" page instead of redirect to dashboard
   - Toast notification when access is denied
   - Show disabled buttons with tooltip explaining missing permission

---

## 📝 Testing Checklist

When testing the RBAC system:

### Backend Testing
- [ ] Login as admin - should have full access
- [ ] Login as laborer (view-only) - API should return 403 for create/update/delete
- [ ] Try accessing `/api/users` without auth - should return 401
- [ ] Try accessing `/api/roles` without permission - should return 403
- [ ] Check `/api/permissions` returns all permissions grouped by module

### Frontend Testing
- [ ] Login as admin - should see all sidebar items, all CRUD buttons
- [ ] Login as laborer - should only see view pages, no edit/delete buttons
- [ ] Login as foreman - should see equipment movement, limited access
- [ ] Try accessing `/roles` as laborer - should redirect to dashboard
- [ ] RolesPage - Create new role, assign permissions, edit, delete
- [ ] Logout/login - permissions should persist

### Permission Granularity
- [ ] User with `projects.view` can see project list but not create
- [ ] User with `equipment.view` + `equipment.move` can move but not delete
- [ ] User with multiple roles gets cumulative permissions
- [ ] Removing permission from role immediately affects users

---

## 🔒 Security Notes

1. **Never trust frontend** - Always enforce permissions on backend
2. **Permission format** - Always use `module.action` format
3. **Cumulative permissions** - Users get ALL permissions from ALL their roles
4. **Admin role** - Protected from deletion, typically has all permissions
5. **Policy methods** - Use standard names: viewAny, view, create, update, delete
6. **Self-deletion** - Users cannot delete their own account (enforced in UserPolicy)

---

## 📊 Current Role Definitions

### Admin
- All permissions across all modules
- Cannot be deleted

### Project Manager
- Full access: projects, schedules, companies, contacts, equipment, reports
- View only: users, inventory

### Job Superintendent
- Full access: projects, schedules, equipment, inventory
- View only: companies, contacts, reports

### Foreman
- Full access: schedules (own), equipment (move)
- View only: projects, equipment, inventory
- Limited: Cannot create projects or manage users

### Supervisor
- Full access: schedules, equipment, inventory
- View only: projects, reports

### Laborer
- View only: All modules
- No create/update/delete permissions
- Intended for viewing schedules, equipment locations, etc.

### Dispatch/Scheduling
- Full access: schedules, equipment (move)
- View only: projects, equipment, users

### Purchasing
- Full access: inventory, companies, contacts
- View only: projects

### Safety Manager
- View access: Most modules
- Special: Can approve vacation requests
- Focus: Review compliance, incident tracking (future)

---

## 💡 Best Practices

1. **Principle of Least Privilege** - Only grant permissions necessary for job function
2. **Use Roles, Not Direct Permissions** - Assign roles to users, not individual permissions
3. **Test with Real Users** - Create test accounts for each role type
4. **Document Changes** - Update this file when adding new permissions or roles
5. **Consistent Naming** - Always use `module.action` format for permissions
6. **Audit Regularly** - Review user roles quarterly to ensure appropriate access
7. **Protect Admin Role** - Never allow admin role to be modified or deleted
8. **Backend First** - Always implement backend authorization before frontend guards

---

## 📚 Quick Reference

### Check Permission in Backend
```php
$this->authorize('actionName', ModelClass::class);  // For index/store
$this->authorize('actionName', $model);  // For show/update/delete
```

### Check Permission in Frontend
```typescript
import { usePermissions } from '@/composables/usePermissions'
const { can } = usePermissions()

if (can('module.action')) {
  // User has permission
}
```

### Add Permission to Route
```typescript
meta: { requiresAuth: true, permission: 'module.action' }
```

### Guard UI Element
```vue
<q-btn v-if="can('module.action')" />
```

---

**End of Document**
