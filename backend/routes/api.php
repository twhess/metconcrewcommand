<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VacationController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyLocationController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\YardController;
use App\Http\Controllers\Api\TransportController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\MaintenanceScheduleController;
use App\Http\Controllers\Api\SpecificationTemplateController;
use App\Http\Controllers\Api\ProjectSpecificationController;
use App\Http\Controllers\Api\ProjectPhaseController;
use App\Http\Controllers\Api\ProjectContactController;
use App\Http\Controllers\Api\ProjectVendorController;
use App\Http\Controllers\Api\MileageLogController;
use App\Http\Controllers\Api\TransportOrderController;
use App\Http\Controllers\Api\EmailTestController;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Roles & Permissions
    Route::get('permissions', [RoleController::class, 'getAllPermissions']);
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions']);

    // Users
    Route::get('users/available/{date}', [UserController::class, 'availability']);
    Route::apiResource('users', UserController::class);
    Route::post('users/{id}/roles', [UserController::class, 'assignRoles']);

    // Vacations
    Route::apiResource('vacations', VacationController::class);
    Route::post('vacations/{id}/approve', [VacationController::class, 'approve']);

    // Companies, Locations & Contacts
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('company-locations', CompanyLocationController::class);
    Route::apiResource('contacts', ContactController::class);

    // Projects
    Route::apiResource('projects', ProjectController::class);
    Route::get('projects/{id}/summary', [ProjectController::class, 'summary']);
    Route::post('projects/{id}/complete', [ProjectController::class, 'complete']);
    Route::post('projects/{id}/duplicate', [ProjectController::class, 'duplicate']);

    // Schedules (core scheduling)
    Route::apiResource('schedules', ScheduleController::class);
    Route::post('schedules/{id}/crew', [ScheduleController::class, 'assignCrew']);
    Route::delete('schedules/{scheduleId}/crew/{userId}', [ScheduleController::class, 'removeCrewMember']);
    Route::post('schedules/{id}/equipment', [ScheduleController::class, 'assignEquipment']);
    Route::delete('schedules/{scheduleId}/equipment/{equipmentId}', [ScheduleController::class, 'removeEquipment']);
    Route::post('schedules/{id}/materials', [ScheduleController::class, 'addMaterials']);
    Route::delete('schedules/{scheduleId}/materials/{materialId}', [ScheduleController::class, 'deleteMaterial']);
    Route::post('schedules/{id}/duplicate', [ScheduleController::class, 'duplicate']);

    // Equipment
    Route::get('equipment/available/{date}', [EquipmentController::class, 'availableForDate']);
    Route::apiResource('equipment', EquipmentController::class);
    Route::post('equipment/{id}/move', [EquipmentController::class, 'move']);
    Route::post('equipment/{id}/update-hours', [EquipmentController::class, 'updateHours']);
    Route::post('equipment/{id}/generate-qr', [EquipmentController::class, 'generateQrCode']);

    // Vehicles
    Route::get('vehicles/available/{date}', [VehicleController::class, 'availableForDate']);
    Route::apiResource('vehicles', VehicleController::class);
    Route::post('vehicles/{id}/move', [VehicleController::class, 'move']);
    Route::get('vehicles/{id}/history', [VehicleController::class, 'history']);
    Route::post('vehicles/{id}/update-odometer', [VehicleController::class, 'updateOdometer']);
    Route::post('vehicles/{id}/assign-operator', [VehicleController::class, 'assignOperator']);
    Route::post('vehicles/{id}/generate-qr', [VehicleController::class, 'generateQrCode']);

    // Vehicle Mileage Logs
    Route::get('vehicles/{id}/mileage', [MileageLogController::class, 'index']);
    Route::post('vehicles/{id}/mileage', [MileageLogController::class, 'store']);
    Route::put('mileage-logs/{id}', [MileageLogController::class, 'update']);
    Route::delete('mileage-logs/{id}', [MileageLogController::class, 'destroy']);
    Route::get('mileage-logs/report', [MileageLogController::class, 'report']);

    // Yards
    Route::apiResource('yards', YardController::class);

    // Transport / QR Scanning
    Route::get('transport/scan/{qrCode}', [TransportController::class, 'scanQrCode']);
    Route::post('transport/pickup', [TransportController::class, 'pickup']);
    Route::post('transport/dropoff', [TransportController::class, 'dropoff']);
    Route::get('transport/in-transit', [TransportController::class, 'itemsInTransit']);
    Route::get('transport/my-transports', [TransportController::class, 'myActiveTransports']);

    // Transport Orders (Dispatch System)
    Route::get('transport-orders/dispatch-summary', [TransportOrderController::class, 'dispatchSummary']);
    Route::get('transport-orders/my-assignments', [TransportOrderController::class, 'myAssignments']);
    Route::post('transport-orders/adhoc-pickup', [TransportOrderController::class, 'adhocPickup']);
    Route::apiResource('transport-orders', TransportOrderController::class)->except(['destroy']);
    Route::post('transport-orders/{id}/assign', [TransportOrderController::class, 'assign']);
    Route::post('transport-orders/{id}/pickup', [TransportOrderController::class, 'pickup']);
    Route::post('transport-orders/{id}/dropoff', [TransportOrderController::class, 'dropoff']);
    Route::post('transport-orders/{id}/cancel', [TransportOrderController::class, 'cancel']);

    // Inventory
    Route::apiResource('inventory', InventoryController::class);
    Route::post('inventory/{id}/move', [InventoryController::class, 'move']);
    Route::post('inventory/{id}/usage', [InventoryController::class, 'recordUsage']);
    Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);
    Route::get('inventory/order-suggestions', [InventoryController::class, 'orderSuggestions']);
    Route::get('inventory/by-location/{locationId}', [InventoryController::class, 'byLocation']);
    Route::get('inventory/by-project/{projectId}', [InventoryController::class, 'byProject']);
    Route::post('inventory/receive', [InventoryController::class, 'receive']);
    Route::post('inventory/receive-batch', [InventoryController::class, 'receiveBatch']);
    Route::get('inventory/scan/{code}', [InventoryController::class, 'scanCode']);

    // Reports
    Route::get('reports/available-resources/{date}', [ReportController::class, 'availableResources']);
    Route::get('reports/equipment-locations', [ReportController::class, 'equipmentLocations']);
    Route::get('reports/inventory-status', [ReportController::class, 'inventoryStatus']);
    Route::get('reports/daily-schedule/{date}', [ReportController::class, 'dailySchedule']);

    // Maintenance Records
    Route::apiResource('maintenance-records', MaintenanceController::class);
    Route::post('maintenance-records/{id}/parts', [MaintenanceController::class, 'addPart']);
    Route::post('maintenance-parts/{id}/return-core', [MaintenanceController::class, 'returnCore']);
    Route::get('maintenance/dashboard', [MaintenanceController::class, 'dashboard']);
    Route::get('maintenance/upcoming', [MaintenanceController::class, 'upcomingForItem']);
    Route::get('maintenance/overdue', [MaintenanceController::class, 'overdueForItem']);
    Route::get('maintenance/all-overdue', [MaintenanceController::class, 'allOverdue']);
    Route::get('maintenance/history', [MaintenanceController::class, 'historyForItem']);
    Route::get('maintenance/cost-report', [MaintenanceController::class, 'costReport']);

    // Maintenance Schedules
    Route::apiResource('maintenance-schedules', MaintenanceScheduleController::class);
    Route::post('maintenance-schedules/{id}/activate', [MaintenanceScheduleController::class, 'activate']);
    Route::post('maintenance-schedules/{id}/deactivate', [MaintenanceScheduleController::class, 'deactivate']);
    Route::get('maintenance-schedules-active', [MaintenanceScheduleController::class, 'active']);
    Route::get('maintenance-schedules-overdue', [MaintenanceScheduleController::class, 'overdue']);
    Route::get('maintenance-schedules/for-item', [MaintenanceScheduleController::class, 'forItem']);

    // Specification Templates
    Route::apiResource('specification-templates', SpecificationTemplateController::class);
    Route::post('specification-templates/{id}/duplicate', [SpecificationTemplateController::class, 'duplicate']);
    Route::post('specification-templates/{id}/apply-to-project', [SpecificationTemplateController::class, 'applyToProject']);

    // Project Specifications (nested under projects)
    Route::prefix('projects/{projectId}')->group(function () {
        Route::get('specifications', [ProjectSpecificationController::class, 'index']);
        Route::post('specifications', [ProjectSpecificationController::class, 'store']);
        Route::get('specifications/{id}', [ProjectSpecificationController::class, 'show']);
        Route::put('specifications/{id}', [ProjectSpecificationController::class, 'update']);
        Route::delete('specifications/{id}', [ProjectSpecificationController::class, 'destroy']);
        Route::post('specifications/{id}/verify', [ProjectSpecificationController::class, 'verify']);

        // Project Phases
        Route::get('phases', [ProjectPhaseController::class, 'index']);
        Route::post('phases', [ProjectPhaseController::class, 'store']);
        Route::get('phases/{id}', [ProjectPhaseController::class, 'show']);
        Route::put('phases/{id}', [ProjectPhaseController::class, 'update']);
        Route::delete('phases/{id}', [ProjectPhaseController::class, 'destroy']);
        Route::post('phases/{id}/complete', [ProjectPhaseController::class, 'complete']);
        Route::post('phases/reorder', [ProjectPhaseController::class, 'reorder']);

        // Project Contacts
        Route::get('contacts', [ProjectContactController::class, 'index']);
        Route::post('contacts', [ProjectContactController::class, 'store']);
        Route::get('contacts/{id}', [ProjectContactController::class, 'show']);
        Route::put('contacts/{id}', [ProjectContactController::class, 'update']);
        Route::delete('contacts/{id}', [ProjectContactController::class, 'destroy']);
        Route::get('contacts/primary/{role}', [ProjectContactController::class, 'primaryByRole']);

        // Project Vendors
        Route::get('vendors', [ProjectVendorController::class, 'index']);
        Route::post('vendors', [ProjectVendorController::class, 'store']);
        Route::get('vendors/{id}', [ProjectVendorController::class, 'show']);
        Route::put('vendors/{id}', [ProjectVendorController::class, 'update']);
        Route::delete('vendors/{id}', [ProjectVendorController::class, 'destroy']);
        Route::post('vendors/{id}/activate', [ProjectVendorController::class, 'activate']);
        Route::post('vendors/{id}/deactivate', [ProjectVendorController::class, 'deactivate']);
        Route::get('vendors/primary/{type}', [ProjectVendorController::class, 'primaryByType']);
    });

    // Email Testing
    Route::get('email/config', [EmailTestController::class, 'getConfig']);
    Route::post('email/test', [EmailTestController::class, 'sendTest']);
});
