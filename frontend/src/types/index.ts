// User & Auth Types
export interface User {
  id: number
  username?: string
  name: string
  first_name?: string
  last_name?: string
  preferred_name?: string
  email: string
  phone?: string
  is_active: boolean
  is_available: boolean
  roles?: Role[]
  vacations?: Vacation[]
}

export interface LoginRequest {
  email: string
  password: string
}

export interface AuthResponse {
  token: string
  user: User
}

export interface UserResponse {
  user: User
}

export interface RouteMeta {
  requiresAuth?: boolean
  requiresGuest?: boolean
  permission?: string
}

// RBAC Types (for user permissions - different from contact roles)
export interface Role {
  id: number
  name: string
  slug: string
  description?: string
  is_active: boolean
  created_by?: number
  updated_by?: number
}

export interface Permission {
  id: number
  name: string
  display_name: string
  module: string
  description?: string
}

// Vacation Types
export interface Vacation {
  id: number
  user_id: number
  user?: User
  start_date: string
  end_date: string
  type: 'vacation' | 'sick' | 'personal' | 'other'
  notes?: string
  approved: boolean
  approved_by?: number
}

// Company & Contact Types
export interface Company {
  id: number
  name: string
  type: 'customer' | 'vendor' | 'contractor' | 'internal'
  main_phone?: string
  main_email?: string
  website?: string
  notes?: string
  is_active: boolean
  locations?: CompanyLocation[]
  primary_location?: CompanyLocation
  contacts?: Contact[]
  created_by?: number
  updated_by?: number
}

export interface CompanyLocation {
  id: number
  company_id: number
  company?: Company
  location_name: string
  location_type?: string // office / yard / billing / shipping / other
  is_primary: boolean
  address1?: string
  address2?: string
  city?: string
  state?: string
  zip?: string
  country?: string
  phone?: string
  email?: string
  hours?: string
  notes?: string
  is_active: boolean
  contact_roles?: ContactRole[]
  created_by?: number
  updated_by?: number
}

export interface Contact {
  id: number
  company_id: number
  company?: Company
  first_name: string
  last_name: string
  full_name?: string
  title?: string
  email: string
  phone_mobile?: string
  phone_work?: string
  phone_other?: string
  preferred_contact_method?: 'email' | 'mobile' | 'work' | 'other'
  notes?: string
  is_active: boolean
  roles?: Role[]
  contact_roles?: ContactRole[]
  created_by?: number
  updated_by?: number
}

export interface ContactRole {
  id: number
  contact_id: number
  contact?: Contact
  role_id: number
  role?: Role
  location_id?: number
  location?: CompanyLocation
  is_primary_for_role: boolean
  notes?: string
  created_by?: number
  updated_by?: number
}

// Project Types
export interface Project {
  id: number
  company_id: number
  company?: Company
  name: string
  project_number?: string
  status: 'active' | 'inactive' | 'completed'
  address_line1?: string
  address_line2?: string
  city?: string
  state?: string
  zip?: string
  start_date?: string
  end_date?: string
  description?: string
  notes?: string
}

// Schedule Types
export interface Schedule {
  id: number
  project_id: number
  project?: Project
  date: string
  start_time: string
  end_time?: string
  status: 'scheduled' | 'in_progress' | 'completed'
  dispatch_instructions?: string
  notes?: string
  crew_assignments?: CrewAssignment[]
  equipment_assignments?: EquipmentAssignment[]
  materials?: Material[]
}

export interface CrewAssignment {
  id: number
  schedule_id: number
  user_id: number
  user?: User
  is_foreman: boolean
}

// Equipment Types
export interface Equipment {
  id: number
  name: string
  equipment_number?: string
  type: 'trackable' | 'non_trackable'
  category?: string
  status: 'active' | 'inactive' | 'maintenance'
  current_location_type?: string
  current_location_id?: number
  description?: string
  notes?: string
}

export interface EquipmentAssignment {
  id: number
  schedule_id: number
  equipment_id: number
  equipment?: Equipment
}

export interface EquipmentMovement {
  id: number
  equipment_id: number
  from_location_type?: string
  from_location_id?: number
  to_location_type: string
  to_location_id?: number
  moved_at: string
  moved_by: number
  notes?: string
}

// Material Types
export interface Material {
  id: number
  schedule_id: number
  type: 'concrete' | 'gravel' | 'other'
  quantity?: number
  unit?: string
  rate_per_hour?: number
  additives?: string
  dispatch_number?: string
  special_instructions?: string
}

// Inventory Types
export interface InventoryItem {
  id: number
  name: string
  sku?: string
  barcode?: string
  qr_code?: string
  type: 'trackable' | 'non_trackable'
  category?: string
  unit?: string
  min_quantity?: number
  max_quantity?: number
  description?: string
  is_active: boolean
  stock?: InventoryStock[]
}

export interface InventoryLocation {
  id: number
  name: string
  type: 'warehouse' | 'yard' | 'shop' | 'project'
  project_id?: number
  description?: string
  is_active: boolean
}

export interface InventoryStock {
  id: number
  inventory_item_id: number
  inventory_location_id: number
  location?: InventoryLocation
  quantity: number
}

export interface InventoryTransaction {
  id: number
  inventory_item_id: number
  type: 'move' | 'usage' | 'adjustment' | 'receive'
  from_location_id?: number
  to_location_id?: number
  project_id?: number
  quantity: number
  notes?: string
  created_at: string
}

// API Response Types
export interface ApiResponse<T> {
  success: boolean
  data: T
  message?: string
}

export interface ApiErrorResponse {
  success: false
  message: string
  errors?: Record<string, string[]>
}

// Report Types
export interface AvailableResourcesReport {
  date: string
  available_employees: User[]
  unavailable_employees: Array<{
    id: number
    name: string
    reason: string
    vacation_details?: {
      type: string
      start_date: string
      end_date: string
    }
    schedule_details?: {
      project_name: string
      start_time: string
    }
  }>
  available_equipment: Equipment[]
  scheduled_equipment: Array<{
    equipment_id: number
    equipment_name: string
    equipment_number?: string
    project_name: string
    start_time: string
  }>
  low_stock_items: InventoryItem[]
}

export interface DailyScheduleSummary {
  date: string
  total_schedules: number
  total_crew_members: number
  total_equipment: number
  schedules: Array<{
    id: number
    project_name: string
    start_time: string
    end_time?: string
    status: string
    crew_count: number
    equipment_count: number
    foreman?: string
  }>
}
