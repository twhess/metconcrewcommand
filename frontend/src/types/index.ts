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
  status: 'planning' | 'active' | 'on_hold' | 'completed' | 'cancelled'
  project_type?: string
  specification_template_id?: number
  specification_template?: SpecificationTemplate
  address_line1?: string
  address_line2?: string
  city?: string
  state?: string
  zip?: string
  start_date?: string
  end_date?: string
  description?: string
  notes?: string
  completed_at?: string
  completion_percentage?: number
  specifications?: ProjectSpecification[]
  phases?: ProjectPhase[]
  project_contacts?: ProjectContact[]
  project_vendors?: ProjectVendor[]
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

// Specification Template Types
export interface SpecificationTemplate {
  id: number
  name: string
  slug: string
  project_type: string
  description?: string
  is_active: boolean
  items?: SpecificationTemplateItem[]
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

export interface SpecificationTemplateItem {
  id: number
  specification_template_id: number
  category: 'safety' | 'tax' | 'prevailing_wage' | 'insurance' | 'regulatory' | 'custom'
  requirement_name: string
  requirement_description?: string
  is_required: boolean
  default_value?: any
  sort_order: number
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

// Project Specification Types
export interface ProjectSpecification {
  id: number
  project_id: number
  category: 'safety' | 'tax' | 'prevailing_wage' | 'insurance' | 'regulatory' | 'custom'
  requirement_name: string
  requirement_description?: string
  is_required: boolean
  value?: any
  compliance_status: 'not_started' | 'in_progress' | 'compliant' | 'non_compliant'
  compliance_notes?: string
  verified_by?: number
  verified_by_user?: User
  verified_at?: string
  sort_order: number
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

// Project Phase Types
export interface ProjectPhase {
  id: number
  project_id: number
  name: string
  description?: string
  phase_number: number
  status: 'pending' | 'in_progress' | 'completed' | 'on_hold'
  start_date?: string
  end_date?: string
  estimated_start_date?: string
  estimated_end_date?: string
  actual_start_date?: string
  actual_end_date?: string
  estimated_hours?: number
  actual_hours?: number
  completion_percentage: number
  equipment_needs?: string[]
  crew_size_estimate?: number
  notes?: string
  completed_at?: string
  completed_by?: number
  completed_by_user?: User
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

// Project Contact Types (project-specific contact assignments)
export interface ProjectContact {
  id: number
  project_id: number
  contact_id: number
  contact?: Contact
  role: string
  is_primary: boolean
  notes?: string
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

// Project Vendor Types
export interface ProjectVendor {
  id: number
  project_id: number
  company_id: number
  company?: Company
  vendor_type: string
  is_primary: boolean
  contract_number?: string
  is_active: boolean
  notes?: string
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

// Project Dashboard Response
export interface ProjectDashboard {
  project: Project
  specifications: {
    summary: {
      total: number
      required: number
      compliant: number
      non_compliant: number
      in_progress: number
      not_started: number
      verified: number
    }
    by_category: Record<string, ProjectSpecification[]>
  }
  phases: {
    summary: {
      total: number
      pending: number
      in_progress: number
      completed: number
      on_hold: number
      overall_completion: number
      total_estimated_hours: number
      total_actual_hours: number
    }
    list: ProjectPhase[]
  }
  contacts: {
    by_role: Record<string, {
      count: number
      primary?: Contact
      all: Contact[]
    }>
    total: number
  }
  vendors: {
    by_type: Record<string, {
      count: number
      primary?: Company
      all: Company[]
    }>
    total: number
  }
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
  qr_code: string
  type: 'trackable' | 'non_trackable'
  category?: string
  status: 'active' | 'inactive' | 'maintenance' | 'in_transit'
  current_location_type?: string
  current_location_id?: number
  current_location_gps_lat?: number
  current_location_gps_lng?: number
  has_hour_meter: boolean
  current_hours: number
  last_hours_reading_at?: string
  description?: string
  notes?: string
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
  deleted_at?: string
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
  from_location_gps_lat?: number
  from_location_gps_lng?: number
  to_location_type: string
  to_location_id?: number
  to_location_gps_lat?: number
  to_location_gps_lng?: number
  movement_type: 'pickup' | 'dropoff' | 'transfer' | 'return_to_yard'
  hours_reading?: number
  moved_at: string
  moved_by: number
  moved_by_user?: User
  transported_by_user_id?: number
  transported_by_user?: User
  transport_vehicle_id?: number
  transport_vehicle?: Vehicle
  temp_transport_vehicle?: string
  is_rental: boolean
  rental_company?: string
  rental_agreement_number?: string
  scanned_via_qr: boolean
  device_info?: string
  notes?: string
  created_at?: string
  updated_at?: string
}

// Vehicle Types
export interface Vehicle {
  id: number
  name: string
  vehicle_number?: string
  qr_code: string

  // Identification
  vin?: string
  license_plate?: string
  registration_state?: string
  registration_expiration?: string

  // Details
  year?: number
  make?: string
  model?: string
  color?: string
  vehicle_type?: 'dump_truck' | 'concrete_mixer' | 'pickup' | 'flatbed' | 'lowboy' | 'skid_steer_trailer' | 'utility_van' | 'service_truck'

  // Specs
  fuel_type?: 'diesel' | 'gasoline' | 'electric' | 'hybrid' | 'propane'
  tank_capacity_gallons?: number
  weight_class?: string
  gvwr_pounds?: number
  towing_capacity_pounds?: number

  // Current State
  current_odometer_miles: number
  last_odometer_reading_at?: string

  // Compliance
  requires_cdl: boolean
  requires_dot_inspection: boolean
  last_dot_inspection_date?: string
  next_dot_inspection_due?: string

  // Insurance
  insurance_policy_number?: string
  insurance_provider?: string
  insurance_expiration?: string

  // Status & Location
  status: 'active' | 'inactive' | 'maintenance' | 'out_of_service' | 'in_transit'
  current_location_type?: string
  current_location_id?: number
  current_location_gps_lat?: number
  current_location_gps_lng?: number

  assigned_to_user_id?: number
  assigned_to?: User

  description?: string
  notes?: string

  // Relationships
  movements?: VehicleMovement[]
  latest_movement?: VehicleMovement
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

export interface VehicleMovement {
  id: number
  vehicle_id: number

  from_location_type?: string
  from_location_id?: number
  from_location_gps_lat?: number
  from_location_gps_lng?: number

  to_location_type: string
  to_location_id?: number
  to_location_gps_lat?: number
  to_location_gps_lng?: number

  movement_type: 'pickup' | 'dropoff' | 'transfer' | 'return_to_yard'
  odometer_reading?: number

  moved_at: string
  moved_by: number
  moved_by_user?: User

  transported_by_user_id?: number
  transported_by_user?: User

  scanned_via_qr: boolean
  device_info?: string

  notes?: string
  created_at?: string
  updated_at?: string
}

export interface Yard {
  id: number
  name: string
  yard_type: 'main_yard' | 'satellite_yard' | 'shop' | 'storage'

  address_line1?: string
  address_line2?: string
  city?: string
  state?: string
  zip?: string

  gps_latitude: number
  gps_longitude: number
  gps_radius_feet: number

  contact_phone?: string
  contact_email?: string

  is_active: boolean
  notes?: string

  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
  deleted_at?: string
}

// Material Types
export interface Material {
  id: number
  schedule_id: number
  type: 'concrete' | 'gravel' | 'other'
  quantity?: number
  unit?: string
  yards_per_hour?: number
  additives?: string
  dispatch_number?: string
  dispatch_phone?: string
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
  type: 'warehouse' | 'yard' | 'shop' | 'project' | 'truck'
  project_id?: number
  vehicle_id?: number
  vehicle?: Vehicle
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
  item?: InventoryItem
  type: 'move' | 'usage' | 'adjustment' | 'receive'
  from_location_id?: number
  fromLocation?: InventoryLocation
  to_location_id?: number
  toLocation?: InventoryLocation
  project_id?: number
  project?: Project
  quantity: number
  notes?: string
  created_by?: number
  creator?: User
  created_at: string
}

export interface LowStockAlert {
  item: InventoryItem
  total_stock: number
  min_quantity: number
  locations: {
    location: InventoryLocation
    quantity: number
  }[]
}

export interface OrderSuggestion {
  item: InventoryItem
  current_stock: number
  min_quantity: number
  max_quantity: number
  suggested_order_qty: number
}

export interface LocationInventory {
  location?: InventoryLocation
  stocks: {
    item: InventoryItem
    quantity: number
    updated_at: string
  }[]
  total_items: number
}

export interface ProjectUsageSummary {
  usage_summary: {
    item: InventoryItem
    total_used: number
    transactions: InventoryTransaction[]
  }[]
  total_transactions: number
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

// Transport System Types
export interface TransportScanResult {
  success: boolean
  item_type: 'equipment' | 'vehicle'
  item: Equipment | Vehicle
  current_location: {
    type: string | null
    id: number | null
    name: string
    gps_lat: number | null
    gps_lng: number | null
  }
  suggested_actions: Array<{
    action: 'pickup' | 'dropoff'
    label: string
    enabled: boolean
  }>
}

export interface TransportPickupRequest {
  item_type: 'equipment' | 'vehicle'
  item_id?: number
  qr_code?: string

  // Transport details
  transported_by_user_id?: number
  transport_vehicle_id?: number
  temp_transport_vehicle?: string

  // Rental tracking (equipment only)
  is_rental?: boolean
  rental_company?: string
  rental_agreement_number?: string

  // Readings
  odometer_reading?: number // vehicles
  hours_reading?: number // equipment

  // GPS
  gps_latitude?: number
  gps_longitude?: number

  // Metadata
  scanned_via_qr?: boolean
  device_info?: string
  notes?: string
}

export interface TransportDropoffRequest {
  item_type: 'equipment' | 'vehicle'
  item_id?: number
  qr_code?: string

  // Destination
  location_type: 'project' | 'yard' | 'shop'
  location_id: number

  // Readings
  odometer_reading?: number
  hours_reading?: number

  // GPS
  gps_latitude?: number
  gps_longitude?: number

  // Metadata
  scanned_via_qr?: boolean
  device_info?: string
  notes?: string
}

export interface TransportInTransitResponse {
  success: boolean
  data: {
    equipment: Equipment[]
    vehicles: Vehicle[]
    total_count: number
  }
}

export interface MyActiveTransportsResponse {
  success: boolean
  data: {
    equipment_transports: EquipmentMovement[]
    vehicle_transports: VehicleMovement[]
    total_active: number
  }
}

// Maintenance System Types
export interface MaintenanceRecord {
  id: number

  // Polymorphic relation
  maintainable_type: 'App\\Models\\Equipment' | 'App\\Models\\Vehicle'
  maintainable_id: number
  maintainable?: Equipment | Vehicle

  // Maintenance details
  maintenance_type: string
  category: 'preventive' | 'corrective' | 'inspection' | 'warranty'

  // When & who
  performed_at: string
  performed_by_type: 'internal' | 'vendor'
  performed_by_user_id?: number
  performed_by_user?: User
  vendor_company_id?: number
  vendor_company?: Company

  // Readings at time of service
  odometer_at_service?: number
  hours_at_service?: number

  // Cost tracking
  labor_hours?: number
  labor_cost?: number
  parts_cost?: number
  total_cost?: number

  // Next service due
  next_due_date?: string
  next_due_odometer?: number
  next_due_hours?: number

  // Warranty
  is_warranty_work: boolean
  warranty_claim_number?: string
  warranty_provider?: string

  // Documentation
  work_order_number?: string
  invoice_number?: string
  description?: string
  notes?: string

  // Relationships
  parts?: MaintenancePart[]

  // Audit
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
  deleted_at?: string
}

export interface MaintenancePart {
  id: number
  maintenance_record_id: number

  // Part details
  part_number?: string
  part_name: string
  description?: string

  // Quantity & pricing
  quantity: number
  unit_of_measure: string
  unit_price?: number
  total_price?: number

  // Vendor/supplier tracking
  vendor_company_id?: number
  vendor_company?: Company
  vendor_part_number?: string
  invoice_number?: string

  // Warranty
  is_warranty_part: boolean
  warranty_expires_at?: string

  // OEM vs aftermarket
  part_type: 'oem' | 'aftermarket' | 'rebuilt' | 'used'

  // Core charge tracking
  has_core_charge: boolean
  core_charge_amount?: number
  core_returned: boolean
  core_returned_date?: string

  notes?: string

  // Audit
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

export interface MaintenanceSchedule {
  id: number

  // Polymorphic relation
  maintainable_type: 'App\\Models\\Equipment' | 'App\\Models\\Vehicle'
  maintainable_id: number
  maintainable?: Equipment | Vehicle

  // Schedule details
  maintenance_type: string
  description?: string
  category: 'preventive' | 'inspection' | 'seasonal'

  // Frequency options
  frequency_type: 'calendar' | 'usage' | 'hybrid'

  // Calendar-based
  frequency_days?: number
  last_performed_date?: string
  next_due_date?: string

  // Usage-based
  frequency_miles?: number
  frequency_hours?: number
  last_performed_odometer?: number
  last_performed_hours?: number
  next_due_odometer?: number
  next_due_hours?: number

  // Lead time notifications
  notify_days_before: number
  notify_miles_before?: number
  notify_hours_before?: number

  // Assignment
  assigned_to_type: 'internal' | 'vendor'
  assigned_user_id?: number
  assigned_user?: User
  assigned_vendor_id?: number
  assigned_vendor?: Company

  // Estimated cost/time
  estimated_cost?: number
  estimated_labor_hours?: number

  // Status
  is_active: boolean
  is_overdue: boolean
  notes?: string

  // Audit
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
  deleted_at?: string
}

export interface CreateMaintenanceRecordRequest {
  maintainable_type: 'App\\Models\\Equipment' | 'App\\Models\\Vehicle'
  maintainable_id: number
  maintenance_type: string
  category: 'preventive' | 'corrective' | 'inspection' | 'warranty'
  performed_at: string
  performed_by_type: 'internal' | 'vendor'
  performed_by_user_id?: number
  vendor_company_id?: number
  odometer_at_service?: number
  hours_at_service?: number
  labor_hours?: number
  labor_cost?: number
  parts?: Partial<MaintenancePart>[]
  description?: string
  notes?: string
  is_warranty_work?: boolean
  warranty_claim_number?: string
  warranty_provider?: string
  work_order_number?: string
  invoice_number?: string
}

export interface CreateMaintenanceScheduleRequest {
  maintainable_type: 'App\\Models\\Equipment' | 'App\\Models\\Vehicle'
  maintainable_id: number
  maintenance_type: string
  description?: string
  category: 'preventive' | 'inspection' | 'seasonal'
  frequency_type: 'calendar' | 'usage' | 'hybrid'
  frequency_days?: number
  next_due_date?: string
  frequency_miles?: number
  frequency_hours?: number
  next_due_odometer?: number
  next_due_hours?: number
  notify_days_before?: number
  notify_miles_before?: number
  notify_hours_before?: number
  assigned_to_type: 'internal' | 'vendor'
  assigned_user_id?: number
  assigned_vendor_id?: number
  estimated_cost?: number
  estimated_labor_hours?: number
  is_active?: boolean
  notes?: string
}

export interface MaintenanceUpcomingResponse {
  success: boolean
  data: MaintenanceSchedule[]
}

export interface MaintenanceOverdueResponse {
  success: boolean
  data: MaintenanceSchedule[]
  count?: number
}

export interface MaintenanceDashboardResponse {
  success: boolean
  data: {
    overdue: {
      count: number
      schedules: MaintenanceSchedule[]
    }
    upcoming: {
      count: number
      schedules: MaintenanceSchedule[]
    }
    recent_maintenance: {
      count: number
      records: MaintenanceRecord[]
    }
    cost_summary_30_days: {
      total_records: number
      total_labor_cost: number
      total_parts_cost: number
      total_cost: number
      internal_cost: number
      vendor_cost: number
      warranty_cost: number
    }
    items_in_maintenance: {
      equipment: number
      vehicles: number
      total: number
    }
    outstanding_core_charges: {
      count: number
      total_amount: number
      parts: MaintenancePart[]
    }
  }
}

export interface MaintenanceHistoryResponse {
  success: boolean
  data: {
    records: MaintenanceRecord[]
    summary: {
      total_records: number
      total_cost: number
      total_labor_cost: number
      total_parts_cost: number
      by_category: Record<string, {
        count: number
        total_cost: number
      }>
    }
  }
}

export interface MaintenanceCostReportResponse {
  success: boolean
  data: {
    summary: {
      total_records: number
      total_cost: number
      total_labor_cost: number
      total_parts_cost: number
      average_cost_per_record: number
    }
    grouped_by: 'category' | 'performed_by_type' | 'maintainable_type' | 'month'
    groups: Array<{
      group?: string
      month?: string
      count: number
      total_cost: number
      labor_cost: number
      parts_cost: number
    }>
    date_range: {
      from: string
      to: string
    }
  }
}

// Email Testing Types
export interface EmailTestRequest {
  to: string
  subject?: string
  message?: string
}

export interface EmailTestResponse {
  success: boolean
  message: string
  details?: {
    to: string
    subject: string
    sent_at: string
  }
  error?: string
}

export interface EmailConfigResponse {
  mailer: string
  from_address: string
  from_name: string
}

// Mileage Log Types
export interface MileageLog {
  id: number
  vehicle_id: number
  driver_user_id: number
  driver?: User
  trip_date: string
  start_odometer: number
  end_odometer: number
  distance_miles: number
  trip_type: 'business' | 'personal' | 'commute' | 'delivery' | 'service_call'
  from_location?: string
  to_location?: string
  project_id?: number
  project?: Project
  purpose?: string
  notes?: string
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

// Transport Order Types

export type TransportOrderStatus = 'requested' | 'assigned' | 'picked_up' | 'completed' | 'cancelled'

export interface TransportOrder {
  id: number
  order_number: string
  status: TransportOrderStatus
  is_adhoc: boolean
  priority: 'low' | 'normal' | 'high' | 'urgent'

  equipment_id: number
  equipment?: Equipment

  pickup_location_type: string
  pickup_location_id: number
  dropoff_location_type: string
  dropoff_location_id: number

  assigned_driver_id?: number
  assigned_driver?: User
  assigned_vehicle_id?: number
  assigned_vehicle?: Vehicle

  scheduled_date: string
  scheduled_time?: string
  special_instructions?: string

  requested_by: number
  requested_by_user?: User

  pickup_movement_id?: number
  pickup_movement?: EquipmentMovement
  dropoff_movement_id?: number
  dropoff_movement?: EquipmentMovement

  picked_up_at?: string
  delivered_at?: string
  completed_at?: string
  cancelled_at?: string
  cancellation_reason?: string

  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
}

export interface CreateTransportOrderRequest {
  equipment_id: number
  pickup_location_type?: string
  pickup_location_id?: number
  dropoff_location_type: string
  dropoff_location_id: number
  priority?: string
  scheduled_date: string
  scheduled_time?: string
  special_instructions?: string
  assigned_driver_id?: number
  assigned_vehicle_id?: number
}

export interface AssignDriverRequest {
  assigned_driver_id: number
  assigned_vehicle_id: number
}

export interface TransportActionRequest {
  hours_reading?: number
  gps_latitude?: number
  gps_longitude?: number
  scanned_via_qr?: boolean
  device_info?: string
  notes?: string
}

export interface AdhocPickupRequest {
  equipment_id: number
  pickup_location_type: string
  pickup_location_id: number
  dropoff_location_type: string
  dropoff_location_id: number
  transport_vehicle_id?: number
  priority?: string
  hours_reading?: number
  gps_latitude?: number
  gps_longitude?: number
  scanned_via_qr?: boolean
  device_info?: string
  notes?: string
}

export interface DispatchSummary {
  date: string
  counts: {
    requested: number
    assigned: number
    picked_up: number
    completed: number
    cancelled: number
  }
}

export interface MileageReport {
  by_vehicle: Array<{
    vehicle_id: number
    total_miles: number
    trip_count: number
    vehicle?: Vehicle
  }>
  by_trip_type: Array<{
    trip_type: string
    total_miles: number
    trip_count: number
  }>
  totals: {
    total_miles: number
    total_trips: number
    avg_trip_distance: number
  }
}
