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

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Roles & Permissions
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{id}/permissions', [RoleController::class, 'assignPermissions']);

    // Users
    Route::apiResource('users', UserController::class);
    Route::post('users/{id}/roles', [UserController::class, 'assignRoles']);
    Route::get('users/available/{date}', [UserController::class, 'availability']);

    // Vacations
    Route::apiResource('vacations', VacationController::class);
    Route::post('vacations/{id}/approve', [VacationController::class, 'approve']);

    // Companies, Locations & Contacts
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('company-locations', CompanyLocationController::class);
    Route::apiResource('contacts', ContactController::class);

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Schedules (core scheduling)
    Route::apiResource('schedules', ScheduleController::class);
    Route::post('schedules/{id}/crew', [ScheduleController::class, 'assignCrew']);
    Route::post('schedules/{id}/equipment', [ScheduleController::class, 'assignEquipment']);
    Route::post('schedules/{id}/materials', [ScheduleController::class, 'addMaterials']);

    // Equipment
    Route::apiResource('equipment', EquipmentController::class);
    Route::post('equipment/{id}/move', [EquipmentController::class, 'move']);
    Route::get('equipment/available/{date}', [EquipmentController::class, 'availableForDate']);

    // Inventory
    Route::apiResource('inventory', InventoryController::class);
    Route::post('inventory/{id}/move', [InventoryController::class, 'move']);
    Route::post('inventory/{id}/usage', [InventoryController::class, 'recordUsage']);

    // Reports
    Route::get('reports/available-resources/{date}', [ReportController::class, 'availableResources']);
    Route::get('reports/equipment-locations', [ReportController::class, 'equipmentLocations']);
    Route::get('reports/inventory-status', [ReportController::class, 'inventoryStatus']);
    Route::get('reports/daily-schedule/{date}', [ReportController::class, 'dailySchedule']);
});
