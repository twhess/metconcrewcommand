<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use App\Models\MaintenancePart;
use App\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    protected MaintenanceService $maintenanceService;

    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * Get all maintenance records (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRecord::class);

        $query = MaintenanceRecord::query()
            ->with(['maintainable', 'parts', 'performedByUser', 'vendorCompany']);

        // Filter by maintainable type
        if ($request->has('maintainable_type')) {
            $query->where('maintainable_type', $request->maintainable_type);
        }

        // Filter by maintainable ID
        if ($request->has('maintainable_id')) {
            $query->where('maintainable_id', $request->maintainable_id);
        }

        // Filter by maintenance type
        if ($request->has('maintenance_type')) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('performed_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('performed_at', '<=', $request->to_date);
        }

        // Filter by performed by type
        if ($request->has('performed_by_type')) {
            $query->where('performed_by_type', $request->performed_by_type);
        }

        // Filter by vendor
        if ($request->has('vendor_company_id')) {
            $query->where('vendor_company_id', $request->vendor_company_id);
        }

        // Filter by warranty work
        if ($request->has('is_warranty_work')) {
            $query->where('is_warranty_work', $request->boolean('is_warranty_work'));
        }

        $records = $query->orderBy('performed_at', 'desc')->paginate(50);

        return response()->json($records);
    }

    /**
     * Create a new maintenance record
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MaintenanceRecord::class);

        $validated = $request->validate([
            'maintainable_type' => 'required|string|in:App\\Models\\Equipment,App\\Models\\Vehicle',
            'maintainable_id' => 'required|integer',
            'maintenance_type' => 'required|string|max:100',
            'category' => 'required|in:preventive,corrective,inspection,warranty',
            'performed_at' => 'required|date',
            'performed_by_type' => 'required|in:internal,vendor',
            'performed_by_user_id' => 'nullable|exists:users,id',
            'vendor_company_id' => 'nullable|exists:companies,id',
            'odometer_at_service' => 'nullable|numeric|min:0',
            'hours_at_service' => 'nullable|numeric|min:0',
            'labor_hours' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'parts' => 'nullable|array',
            'parts.*.part_number' => 'nullable|string|max:100',
            'parts.*.part_name' => 'required|string|max:255',
            'parts.*.description' => 'nullable|string',
            'parts.*.quantity' => 'required|numeric|min:0',
            'parts.*.unit_of_measure' => 'nullable|string|max:20',
            'parts.*.unit_price' => 'nullable|numeric|min:0',
            'parts.*.vendor_company_id' => 'nullable|exists:companies,id',
            'parts.*.vendor_part_number' => 'nullable|string|max:100',
            'parts.*.is_warranty_part' => 'nullable|boolean',
            'parts.*.warranty_expires_at' => 'nullable|date',
            'parts.*.part_type' => 'nullable|in:oem,aftermarket,rebuilt,used',
            'parts.*.has_core_charge' => 'nullable|boolean',
            'parts.*.core_charge_amount' => 'nullable|numeric|min:0',
            'parts.*.notes' => 'nullable|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_warranty_work' => 'nullable|boolean',
            'warranty_claim_number' => 'nullable|string|max:100',
            'warranty_provider' => 'nullable|string',
            'work_order_number' => 'nullable|string|max:100',
            'invoice_number' => 'nullable|string|max:100',
        ]);

        $record = $this->maintenanceService->createMaintenanceRecord(
            maintainableType: $validated['maintainable_type'],
            maintainableId: $validated['maintainable_id'],
            maintenanceType: $validated['maintenance_type'],
            category: $validated['category'],
            performedAt: \Carbon\Carbon::parse($validated['performed_at']),
            performedByType: $validated['performed_by_type'],
            performedByUserId: $validated['performed_by_user_id'] ?? null,
            vendorCompanyId: $validated['vendor_company_id'] ?? null,
            odometerAtService: $validated['odometer_at_service'] ?? null,
            hoursAtService: $validated['hours_at_service'] ?? null,
            laborHours: $validated['labor_hours'] ?? null,
            laborCost: $validated['labor_cost'] ?? null,
            parts: $validated['parts'] ?? null,
            description: $validated['description'] ?? null,
            notes: $validated['notes'] ?? null,
            isWarrantyWork: $validated['is_warranty_work'] ?? false,
            warrantyClaimNumber: $validated['warranty_claim_number'] ?? null,
            warrantyProvider: $validated['warranty_provider'] ?? null,
            workOrderNumber: $validated['work_order_number'] ?? null,
            invoiceNumber: $validated['invoice_number'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record created successfully',
            'data' => $record,
        ], 201);
    }

    /**
     * Get a single maintenance record
     */
    public function show(int $id): JsonResponse
    {
        $record = MaintenanceRecord::with([
            'maintainable',
            'parts.vendorCompany',
            'performedByUser',
            'vendorCompany',
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);

        $this->authorize('view', $record);

        return response()->json($record);
    }

    /**
     * Update a maintenance record
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'maintenance_type' => 'sometimes|string|max:100',
            'category' => 'sometimes|in:preventive,corrective,inspection,warranty',
            'performed_at' => 'sometimes|date',
            'performed_by_type' => 'sometimes|in:internal,vendor',
            'performed_by_user_id' => 'nullable|exists:users,id',
            'vendor_company_id' => 'nullable|exists:companies,id',
            'odometer_at_service' => 'nullable|numeric|min:0',
            'hours_at_service' => 'nullable|numeric|min:0',
            'labor_hours' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_warranty_work' => 'nullable|boolean',
            'warranty_claim_number' => 'nullable|string|max:100',
            'warranty_provider' => 'nullable|string',
            'work_order_number' => 'nullable|string|max:100',
            'invoice_number' => 'nullable|string|max:100',
        ]);

        $record = MaintenanceRecord::findOrFail($id);

        $this->authorize('update', $record);

        $validated['updated_by'] = auth()->id();

        $record->update($validated);

        // Recalculate costs if labor cost was updated
        if (isset($validated['labor_cost'])) {
            $this->maintenanceService->recalculateMaintenanceCosts($id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record updated successfully',
            'data' => $record->load(['parts', 'performedByUser', 'vendorCompany']),
        ]);
    }

    /**
     * Delete a maintenance record
     */
    public function destroy(int $id): JsonResponse
    {
        $record = MaintenanceRecord::findOrFail($id);

        $this->authorize('delete', $record);

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record deleted successfully',
        ]);
    }

    /**
     * Add a part to an existing maintenance record
     */
    public function addPart(Request $request, int $maintenanceRecordId): JsonResponse
    {
        $record = MaintenanceRecord::findOrFail($maintenanceRecordId);
        $this->authorize('update', $record);

        $validated = $request->validate([
            'part_number' => 'nullable|string|max:100',
            'part_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'unit_of_measure' => 'nullable|string|max:20',
            'unit_price' => 'nullable|numeric|min:0',
            'vendor_company_id' => 'nullable|exists:companies,id',
            'vendor_part_number' => 'nullable|string|max:100',
            'invoice_number' => 'nullable|string|max:100',
            'is_warranty_part' => 'nullable|boolean',
            'warranty_expires_at' => 'nullable|date',
            'part_type' => 'nullable|in:oem,aftermarket,rebuilt,used',
            'has_core_charge' => 'nullable|boolean',
            'core_charge_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $part = $this->maintenanceService->addPartToMaintenance($maintenanceRecordId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Part added to maintenance record',
            'data' => $part->load('vendorCompany'),
        ], 201);
    }

    /**
     * Mark a core charge as returned
     */
    public function returnCore(Request $request, int $partId): JsonResponse
    {
        $validated = $request->validate([
            'returned_date' => 'required|date',
        ]);

        $part = MaintenancePart::findOrFail($partId);
        $this->authorize('update', MaintenanceRecord::findOrFail($part->maintenance_record_id));

        $part = $this->maintenanceService->returnCoreCharge(
            $partId,
            \Carbon\Carbon::parse($validated['returned_date'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Core charge marked as returned',
            'data' => $part,
        ]);
    }

    /**
     * Get upcoming maintenance for a specific item
     */
    public function upcomingForItem(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRecord::class);

        $validated = $request->validate([
            'maintainable_type' => 'required|string|in:App\\Models\\Equipment,App\\Models\\Vehicle',
            'maintainable_id' => 'required|integer',
            'days_ahead' => 'nullable|integer|min:1|max:365',
        ]);

        $upcoming = $this->maintenanceService->getUpcomingMaintenance(
            $validated['maintainable_type'],
            $validated['maintainable_id'],
            $validated['days_ahead'] ?? 30
        );

        return response()->json([
            'success' => true,
            'data' => $upcoming,
        ]);
    }

    /**
     * Get overdue maintenance for a specific item
     */
    public function overdueForItem(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRecord::class);

        $validated = $request->validate([
            'maintainable_type' => 'required|string|in:App\\Models\\Equipment,App\\Models\\Vehicle',
            'maintainable_id' => 'required|integer',
        ]);

        $overdue = $this->maintenanceService->getOverdueMaintenance(
            $validated['maintainable_type'],
            $validated['maintainable_id']
        );

        return response()->json([
            'success' => true,
            'data' => $overdue,
        ]);
    }

    /**
     * Get all overdue maintenance across all items
     */
    public function allOverdue(): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRecord::class);

        $overdue = $this->maintenanceService->getAllOverdueMaintenance();

        return response()->json([
            'success' => true,
            'data' => $overdue,
            'count' => $overdue->count(),
        ]);
    }

    /**
     * Get maintenance dashboard summary
     */
    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRecord::class);

        // Get overdue maintenance
        $overdueSchedules = $this->maintenanceService->getAllOverdueMaintenance();

        // Get upcoming maintenance (next 30 days)
        $upcomingMaintenance = \App\Models\MaintenanceSchedule::where('is_active', true)
            ->where('is_overdue', false)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('next_due_date')
                        ->where('next_due_date', '<=', now()->addDays(30));
                });
            })
            ->with(['maintainable', 'assignedUser', 'assignedVendor'])
            ->orderBy('next_due_date')
            ->limit(10)
            ->get();

        // Get recent maintenance (last 30 days)
        $recentMaintenance = MaintenanceRecord::where('performed_at', '>=', now()->subDays(30))
            ->with(['maintainable', 'performedByUser', 'vendorCompany', 'parts'])
            ->orderBy('performed_at', 'desc')
            ->limit(10)
            ->get();

        // Calculate cost summary (last 30 days)
        $costSummary = MaintenanceRecord::where('performed_at', '>=', now()->subDays(30))
            ->selectRaw('
                COUNT(*) as total_records,
                SUM(labor_cost) as total_labor_cost,
                SUM(parts_cost) as total_parts_cost,
                SUM(total_cost) as total_cost,
                SUM(CASE WHEN performed_by_type = "internal" THEN total_cost ELSE 0 END) as internal_cost,
                SUM(CASE WHEN performed_by_type = "vendor" THEN total_cost ELSE 0 END) as vendor_cost,
                SUM(CASE WHEN is_warranty_work = 1 THEN total_cost ELSE 0 END) as warranty_cost
            ')
            ->first();

        // Get items currently in maintenance
        $equipmentInMaintenance = \App\Models\Equipment::where('status', 'maintenance')->count();
        $vehiclesInMaintenance = \App\Models\Vehicle::where('status', 'maintenance')->count();

        // Get parts with outstanding core charges
        $outstandingCores = \App\Models\MaintenancePart::where('has_core_charge', true)
            ->where('core_returned', false)
            ->with(['maintenanceRecord.maintainable'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'overdue' => [
                    'count' => $overdueSchedules->count(),
                    'schedules' => $overdueSchedules->take(5),
                ],
                'upcoming' => [
                    'count' => $upcomingMaintenance->count(),
                    'schedules' => $upcomingMaintenance,
                ],
                'recent_maintenance' => [
                    'count' => $recentMaintenance->count(),
                    'records' => $recentMaintenance,
                ],
                'cost_summary_30_days' => [
                    'total_records' => $costSummary->total_records ?? 0,
                    'total_labor_cost' => (float) ($costSummary->total_labor_cost ?? 0),
                    'total_parts_cost' => (float) ($costSummary->total_parts_cost ?? 0),
                    'total_cost' => (float) ($costSummary->total_cost ?? 0),
                    'internal_cost' => (float) ($costSummary->internal_cost ?? 0),
                    'vendor_cost' => (float) ($costSummary->vendor_cost ?? 0),
                    'warranty_cost' => (float) ($costSummary->warranty_cost ?? 0),
                ],
                'items_in_maintenance' => [
                    'equipment' => $equipmentInMaintenance,
                    'vehicles' => $vehiclesInMaintenance,
                    'total' => $equipmentInMaintenance + $vehiclesInMaintenance,
                ],
                'outstanding_core_charges' => [
                    'count' => $outstandingCores->count(),
                    'total_amount' => $outstandingCores->sum('core_charge_amount'),
                    'parts' => $outstandingCores,
                ],
            ],
        ]);
    }

    /**
     * Get maintenance history for a specific item
     */
    public function historyForItem(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRecord::class);

        $validated = $request->validate([
            'maintainable_type' => 'required|string|in:App\\Models\\Equipment,App\\Models\\Vehicle',
            'maintainable_id' => 'required|integer',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'category' => 'nullable|in:preventive,corrective,inspection,warranty',
        ]);

        $query = MaintenanceRecord::where('maintainable_type', $validated['maintainable_type'])
            ->where('maintainable_id', $validated['maintainable_id'])
            ->with(['parts.vendorCompany', 'performedByUser', 'vendorCompany']);

        if (isset($validated['from_date'])) {
            $query->where('performed_at', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->where('performed_at', '<=', $validated['to_date']);
        }

        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        $records = $query->orderBy('performed_at', 'desc')->get();

        // Calculate summary stats
        $summary = [
            'total_records' => $records->count(),
            'total_cost' => $records->sum('total_cost'),
            'total_labor_cost' => $records->sum('labor_cost'),
            'total_parts_cost' => $records->sum('parts_cost'),
            'by_category' => $records->groupBy('category')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('total_cost'),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'records' => $records,
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Get maintenance cost report
     */
    public function costReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceRecord::class);

        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'group_by' => 'nullable|in:category,performed_by_type,maintainable_type,month',
        ]);

        $query = MaintenanceRecord::whereBetween('performed_at', [
            $validated['from_date'],
            $validated['to_date']
        ])->with(['maintainable', 'performedByUser', 'vendorCompany']);

        $records = $query->get();

        // Overall summary
        $summary = [
            'total_records' => $records->count(),
            'total_cost' => $records->sum('total_cost'),
            'total_labor_cost' => $records->sum('labor_cost'),
            'total_parts_cost' => $records->sum('parts_cost'),
            'average_cost_per_record' => $records->count() > 0 ? $records->sum('total_cost') / $records->count() : 0,
        ];

        // Group by requested field
        $groupBy = $validated['group_by'] ?? 'category';
        $grouped = [];

        if ($groupBy === 'month') {
            $grouped = $records->groupBy(function ($record) {
                return \Carbon\Carbon::parse($record->performed_at)->format('Y-m');
            })->map(function ($group, $month) {
                return [
                    'month' => $month,
                    'count' => $group->count(),
                    'total_cost' => $group->sum('total_cost'),
                    'labor_cost' => $group->sum('labor_cost'),
                    'parts_cost' => $group->sum('parts_cost'),
                ];
            })->values();
        } else {
            $grouped = $records->groupBy($groupBy)->map(function ($group, $key) {
                return [
                    'group' => $key,
                    'count' => $group->count(),
                    'total_cost' => $group->sum('total_cost'),
                    'labor_cost' => $group->sum('labor_cost'),
                    'parts_cost' => $group->sum('parts_cost'),
                ];
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'grouped_by' => $groupBy,
                'groups' => $grouped,
                'date_range' => [
                    'from' => $validated['from_date'],
                    'to' => $validated['to_date'],
                ],
            ],
        ]);
    }
}
