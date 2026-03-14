# API Documentation - Vehicle & Equipment Tracking System

## Base URL
- **Local Development**: `http://localhost:8002/api`
- **Production**: TBD

## Authentication
All protected endpoints require Bearer token authentication via Laravel Sanctum.

Include the token in the Authorization header:
```
Authorization: Bearer {your_token_here}
```

## Table of Contents
1. [Authentication](#authentication-endpoints)
2. [Maintenance Records](#maintenance-records)
3. [Maintenance Schedules](#maintenance-schedules)
4. [Equipment](#equipment)
5. [Vehicles](#vehicles)
6. [Transport System](#transport-system)
7. [Yards](#yards)

---

## Authentication Endpoints

### Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "token": "1|abcd1234...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com"
  }
}
```

### Logout
```http
POST /api/auth/logout
```
Requires authentication. Revokes the current access token.

### Get Current User
```http
GET /api/me
```
Returns the authenticated user's information.

---

## Maintenance Records

### Get Maintenance Dashboard
```http
GET /api/maintenance/dashboard
```

Returns comprehensive dashboard summary including:
- Overdue maintenance (top 5)
- Upcoming maintenance (next 30 days)
- Recent maintenance (last 30 days)
- Cost summary (last 30 days)
- Items currently in maintenance
- Outstanding core charges

**Response:**
```json
{
  "success": true,
  "data": {
    "overdue": {
      "count": 2,
      "schedules": [...]
    },
    "upcoming": {
      "count": 5,
      "schedules": [...]
    },
    "recent_maintenance": {
      "count": 10,
      "records": [...]
    },
    "cost_summary_30_days": {
      "total_records": 6,
      "total_labor_cost": 1650.00,
      "total_parts_cost": 3873.31,
      "total_cost": 5523.31,
      "internal_cost": 2418.41,
      "vendor_cost": 3104.90,
      "warranty_cost": 0.00
    },
    "items_in_maintenance": {
      "equipment": 1,
      "vehicles": 1,
      "total": 2
    },
    "outstanding_core_charges": {
      "count": 0,
      "total_amount": 0,
      "parts": []
    }
  }
}
```

### List Maintenance Records
```http
GET /api/maintenance-records
```

**Query Parameters:**
- `maintainable_type` - Filter by type: `App\Models\Equipment` or `App\Models\Vehicle`
- `maintainable_id` - Filter by specific item ID
- `maintenance_type` - Filter by type (e.g., "Oil Change")
- `category` - Filter: `preventive`, `corrective`, `inspection`, `warranty`
- `from_date` - Filter by date range (start)
- `to_date` - Filter by date range (end)
- `performed_by_type` - Filter: `internal` or `vendor`
- `vendor_company_id` - Filter by vendor
- `is_warranty_work` - Filter warranty work: `true` or `false`

**Response:** Paginated list of maintenance records (50 per page)

### Create Maintenance Record
```http
POST /api/maintenance-records
```

**Request Body:**
```json
{
  "maintainable_type": "App\\Models\\Equipment",
  "maintainable_id": 1,
  "maintenance_type": "Oil & Filter Change",
  "category": "preventive",
  "performed_at": "2024-02-14T10:00:00Z",
  "performed_by_type": "internal",
  "performed_by_user_id": 1,
  "hours_at_service": 3450.5,
  "labor_hours": 1.5,
  "labor_cost": 75.00,
  "parts": [
    {
      "part_name": "Engine Oil 15W-40",
      "quantity": 5,
      "unit_of_measure": "gal",
      "unit_price": 24.99,
      "part_type": "oem"
    },
    {
      "part_number": "1R-0750",
      "part_name": "Oil Filter",
      "quantity": 1,
      "unit_of_measure": "ea",
      "unit_price": 18.50,
      "part_type": "oem"
    }
  ],
  "description": "Regular scheduled oil and filter service",
  "notes": "All systems operating normally"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Maintenance record created successfully",
  "data": {
    "id": 1,
    "maintainable_type": "App\\Models\\Equipment",
    "maintainable_id": 1,
    "maintenance_type": "Oil & Filter Change",
    "category": "preventive",
    "performed_at": "2024-02-14T10:00:00.000000Z",
    "total_cost": 218.45,
    "parts_cost": 143.45,
    "labor_cost": 75.00,
    "parts": [...]
  }
}
```

### Get Single Maintenance Record
```http
GET /api/maintenance-records/{id}
```

Returns detailed maintenance record with all parts, performer info, and audit trail.

### Update Maintenance Record
```http
PUT /api/maintenance-records/{id}
```

Update existing maintenance record (same fields as create, all optional).

### Delete Maintenance Record
```http
DELETE /api/maintenance-records/{id}
```

Soft deletes the maintenance record.

### Add Part to Maintenance Record
```http
POST /api/maintenance-records/{id}/parts
```

**Request Body:**
```json
{
  "part_number": "ABC123",
  "part_name": "Hydraulic Filter",
  "quantity": 2,
  "unit_of_measure": "ea",
  "unit_price": 45.00,
  "vendor_company_id": 1,
  "part_type": "oem"
}
```

Automatically recalculates maintenance record total costs.

### Return Core Charge
```http
POST /api/maintenance-parts/{partId}/return-core
```

**Request Body:**
```json
{
  "returned_date": "2024-02-20"
}
```

Marks a core charge as returned and updates cost calculations.

### Get Upcoming Maintenance for Item
```http
GET /api/maintenance/upcoming
```

**Query Parameters:**
- `maintainable_type` (required) - `App\Models\Equipment` or `App\Models\Vehicle`
- `maintainable_id` (required) - Item ID
- `days_ahead` (optional) - Days to look ahead (default: 30)

Returns upcoming maintenance schedules based on calendar, odometer, or hours.

### Get Overdue Maintenance for Item
```http
GET /api/maintenance/overdue
```

**Query Parameters:**
- `maintainable_type` (required)
- `maintainable_id` (required)

Returns all overdue maintenance schedules for the item.

### Get All Overdue Maintenance
```http
GET /api/maintenance/all-overdue
```

Returns all overdue maintenance across all equipment and vehicles.

### Get Maintenance History for Item
```http
GET /api/maintenance/history
```

**Query Parameters:**
- `maintainable_type` (required)
- `maintainable_id` (required)
- `from_date` (optional)
- `to_date` (optional)
- `category` (optional) - Filter by category

**Response:**
```json
{
  "success": true,
  "data": {
    "records": [...],
    "summary": {
      "total_records": 15,
      "total_cost": 12450.00,
      "total_labor_cost": 3200.00,
      "total_parts_cost": 9250.00,
      "by_category": {
        "preventive": {
          "count": 10,
          "total_cost": 5200.00
        },
        "corrective": {
          "count": 5,
          "total_cost": 7250.00
        }
      }
    }
  }
}
```

### Get Maintenance Cost Report
```http
GET /api/maintenance/cost-report
```

**Query Parameters:**
- `from_date` (required) - Start date
- `to_date` (required) - End date
- `group_by` (optional) - Group by: `category`, `performed_by_type`, `maintainable_type`, or `month`

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_records": 50,
      "total_cost": 45230.50,
      "total_labor_cost": 12500.00,
      "total_parts_cost": 32730.50,
      "average_cost_per_record": 904.61
    },
    "grouped_by": "category",
    "groups": [
      {
        "group": "preventive",
        "count": 30,
        "total_cost": 18500.00,
        "labor_cost": 6000.00,
        "parts_cost": 12500.00
      },
      {
        "group": "corrective",
        "count": 20,
        "total_cost": 26730.50,
        "labor_cost": 6500.00,
        "parts_cost": 20230.50
      }
    ],
    "date_range": {
      "from": "2024-01-01",
      "to": "2024-02-14"
    }
  }
}
```

---

## Maintenance Schedules

### List Maintenance Schedules
```http
GET /api/maintenance-schedules
```

**Query Parameters:**
- `maintainable_type`
- `maintainable_id`
- `maintenance_type`
- `category`
- `is_active` - `true` or `false`
- `is_overdue` - `true` or `false`
- `frequency_type` - `calendar`, `usage`, or `hybrid`

**Response:** Paginated list (50 per page)

### Create Maintenance Schedule
```http
POST /api/maintenance-schedules
```

**Request Body (Calendar-based example):**
```json
{
  "maintainable_type": "App\\Models\\Vehicle",
  "maintainable_id": 1,
  "maintenance_type": "DOT Annual Inspection",
  "description": "Required annual DOT safety inspection",
  "category": "inspection",
  "frequency_type": "calendar",
  "frequency_days": 365,
  "next_due_date": "2025-12-01",
  "notify_days_before": 30,
  "assigned_to_type": "vendor",
  "assigned_vendor_id": 1,
  "estimated_cost": 150.00,
  "estimated_labor_hours": 2.0,
  "is_active": true
}
```

**Request Body (Usage-based example):**
```json
{
  "maintainable_type": "App\\Models\\Equipment",
  "maintainable_id": 1,
  "maintenance_type": "Oil Change",
  "category": "preventive",
  "frequency_type": "usage",
  "frequency_hours": 250.0,
  "next_due_hours": 3650.0,
  "notify_hours_before": 25.0,
  "assigned_to_type": "internal",
  "assigned_user_id": 1,
  "estimated_cost": 250.00,
  "is_active": true
}
```

### Get Schedule
```http
GET /api/maintenance-schedules/{id}
```

### Update Schedule
```http
PUT /api/maintenance-schedules/{id}
```

### Delete Schedule
```http
DELETE /api/maintenance-schedules/{id}
```

Soft deletes the schedule.

### Activate Schedule
```http
POST /api/maintenance-schedules/{id}/activate
```

Sets `is_active` to true.

### Deactivate Schedule
```http
POST /api/maintenance-schedules/{id}/deactivate
```

Sets `is_active` to false.

### Get All Active Schedules
```http
GET /api/maintenance-schedules-active
```

Returns all active schedules ordered by next due date.

### Get All Overdue Schedules
```http
GET /api/maintenance-schedules-overdue
```

Returns all active schedules that are currently overdue.

### Get Schedules for Item
```http
GET /api/maintenance-schedules/for-item
```

**Query Parameters:**
- `maintainable_type` (required)
- `maintainable_id` (required)

Returns all schedules for a specific item.

---

## Equipment

### List Equipment
```http
GET /api/equipment
```

### Get Equipment
```http
GET /api/equipment/{id}
```

### Create Equipment
```http
POST /api/equipment
```

### Update Equipment
```http
PUT /api/equipment/{id}
```

### Delete Equipment
```http
DELETE /api/equipment/{id}
```

### Move Equipment
```http
POST /api/equipment/{id}/move
```

**Request Body:**
```json
{
  "location_type": "yard",
  "location_id": 1,
  "movement_type": "return_to_yard",
  "hours_reading": 3500.5,
  "gps_latitude": 33.4484,
  "gps_longitude": -112.0740,
  "notes": "Returned from project"
}
```

### Update Equipment Hours
```http
POST /api/equipment/{id}/update-hours
```

**Request Body:**
```json
{
  "hours": 3500.5,
  "notes": "Regular hour meter reading"
}
```

### Generate QR Code
```http
POST /api/equipment/{id}/generate-qr
```

Generates a unique QR code for equipment tracking.

---

## Vehicles

### List Vehicles
```http
GET /api/vehicles
```

### Get Vehicle
```http
GET /api/vehicles/{id}
```

### Create Vehicle
```http
POST /api/vehicles
```

### Update Vehicle
```http
PUT /api/vehicles/{id}
```

### Delete Vehicle
```http
DELETE /api/vehicles/{id}
```

### Move Vehicle
```http
POST /api/vehicles/{id}/move
```

**Request Body:**
```json
{
  "location_type": "project",
  "location_id": 5,
  "movement_type": "dropoff",
  "odometer_reading": 87650.2,
  "gps_latitude": 33.4484,
  "gps_longitude": -112.0740,
  "notes": "Delivered to jobsite"
}
```

### Get Vehicle Movement History
```http
GET /api/vehicles/{id}/history
```

Returns complete movement history for a vehicle.

### Update Odometer
```http
POST /api/vehicles/{id}/update-odometer
```

**Request Body:**
```json
{
  "odometer_reading": 87650.2,
  "notes": "Weekly odometer check"
}
```

### Assign Operator
```http
POST /api/vehicles/{id}/assign-operator
```

**Request Body:**
```json
{
  "user_id": 5
}
```

### Generate QR Code
```http
POST /api/vehicles/{id}/generate-qr
```

---

## Transport System

### Scan QR Code
```http
GET /api/transport/scan/{qrCode}
```

Scans a QR code and returns item information with suggested actions.

**Response:**
```json
{
  "success": true,
  "item_type": "equipment",
  "item": {...},
  "current_location": {
    "type": "yard",
    "id": 1,
    "name": "Main Yard",
    "gps_lat": 33.4484,
    "gps_lng": -112.0740
  },
  "suggested_actions": [
    {
      "action": "pickup",
      "label": "Pick Up",
      "enabled": true
    }
  ]
}
```

### Pickup Item
```http
POST /api/transport/pickup
```

**Request Body:**
```json
{
  "item_type": "equipment",
  "qr_code": "EQP-EX001",
  "transported_by_user_id": 1,
  "transport_vehicle_id": 5,
  "hours_reading": 3450.0,
  "gps_latitude": 33.4484,
  "gps_longitude": -112.0740,
  "scanned_via_qr": true
}
```

### Dropoff Item
```http
POST /api/transport/dropoff
```

**Request Body:**
```json
{
  "item_type": "equipment",
  "qr_code": "EQP-EX001",
  "location_type": "project",
  "location_id": 5,
  "hours_reading": 3455.2,
  "gps_latitude": 33.5092,
  "gps_longitude": -111.8990,
  "scanned_via_qr": true
}
```

### Get Items in Transit
```http
GET /api/transport/in-transit
```

Returns all equipment and vehicles currently in transit.

### Get My Active Transports
```http
GET /api/transport/my-transports
```

Returns all items currently being transported by the authenticated user.

---

## Yards

### List Yards
```http
GET /api/yards
```

### Get Yard
```http
GET /api/yards/{id}
```

### Create Yard
```http
POST /api/yards
```

**Request Body:**
```json
{
  "name": "Main Yard - Headquarters",
  "yard_type": "main_yard",
  "address_line1": "1234 Industrial Parkway",
  "city": "Phoenix",
  "state": "AZ",
  "zip": "85001",
  "gps_latitude": 33.4484,
  "gps_longitude": -112.0740,
  "gps_radius_feet": 500,
  "contact_phone": "602-555-0100",
  "is_active": true
}
```

### Update Yard
```http
PUT /api/yards/{id}
```

### Delete Yard
```http
DELETE /api/yards/{id}
```

---

## Error Responses

All endpoints return standardized error responses:

**Validation Error (422):**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

**Unauthorized (401):**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**Server Error (500):**
```json
{
  "success": false,
  "message": "Server error occurred"
}
```

---

## Rate Limiting

API requests are rate-limited to prevent abuse:
- **Authenticated requests**: 60 requests per minute
- **Public endpoints**: 10 requests per minute

Rate limit headers are included in responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Requests remaining in window
- `X-RateLimit-Reset`: Timestamp when limit resets

---

## Pagination

List endpoints return paginated results:

```json
{
  "current_page": 1,
  "data": [...],
  "first_page_url": "http://localhost:8002/api/maintenance-records?page=1",
  "from": 1,
  "last_page": 3,
  "last_page_url": "http://localhost:8002/api/maintenance-records?page=3",
  "next_page_url": "http://localhost:8002/api/maintenance-records?page=2",
  "path": "http://localhost:8002/api/maintenance-records",
  "per_page": 50,
  "prev_page_url": null,
  "to": 50,
  "total": 150
}
```

---

## Testing

Test credentials:
- **Email**: admin@example.com
- **Password**: password123

Sample data is seeded including:
- 4 yards
- 12 equipment items
- 9 vehicles
- 6 maintenance records with parts
- 7 maintenance schedules
- 1 vendor company

---

## Changelog

### Phase 6 (Current)
- Added maintenance dashboard summary endpoint
- Added maintenance history endpoint
- Added maintenance cost report endpoint
- Enhanced API documentation

### Phase 4-5
- Implemented maintenance records system
- Implemented maintenance schedules
- Added polymorphic maintenance support
- Created comprehensive seeders

### Phase 1-3
- Core vehicle and equipment tracking
- QR code system
- Transport check-in/out system
- Movement audit trails
