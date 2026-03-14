<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use App\Models\MaintenancePart;
use App\Models\MaintenanceSchedule;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    /**
     * Create a maintenance record
     */
    public function createMaintenanceRecord(
        string $maintainableType,
        int $maintainableId,
        string $maintenanceType,
        string $category,
        Carbon $performedAt,
        string $performedByType,
        ?int $performedByUserId = null,
        ?int $vendorCompanyId = null,
        ?float $odometerAtService = null,
        ?float $hoursAtService = null,
        ?float $laborHours = null,
        ?float $laborCost = null,
        ?array $parts = null,
        ?string $description = null,
        ?string $notes = null,
        bool $isWarrantyWork = false,
        ?string $warrantyClaimNumber = null,
        ?string $warrantyProvider = null,
        ?string $workOrderNumber = null,
        ?string $invoiceNumber = null
    ): MaintenanceRecord {
        return DB::transaction(function () use (
            $maintainableType,
            $maintainableId,
            $maintenanceType,
            $category,
            $performedAt,
            $performedByType,
            $performedByUserId,
            $vendorCompanyId,
            $odometerAtService,
            $hoursAtService,
            $laborHours,
            $laborCost,
            $parts,
            $description,
            $notes,
            $isWarrantyWork,
            $warrantyClaimNumber,
            $warrantyProvider,
            $workOrderNumber,
            $invoiceNumber
        ) {
            // Create the maintenance record
            $record = MaintenanceRecord::create([
                'maintainable_type' => $maintainableType,
                'maintainable_id' => $maintainableId,
                'maintenance_type' => $maintenanceType,
                'category' => $category,
                'performed_at' => $performedAt,
                'performed_by_type' => $performedByType,
                'performed_by_user_id' => $performedByUserId,
                'vendor_company_id' => $vendorCompanyId,
                'odometer_at_service' => $odometerAtService,
                'hours_at_service' => $hoursAtService,
                'labor_hours' => $laborHours,
                'labor_cost' => $laborCost,
                'description' => $description,
                'notes' => $notes,
                'is_warranty_work' => $isWarrantyWork,
                'warranty_claim_number' => $warrantyClaimNumber,
                'warranty_provider' => $warrantyProvider,
                'work_order_number' => $workOrderNumber,
                'invoice_number' => $invoiceNumber,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Add parts if provided
            if ($parts && is_array($parts)) {
                $totalPartsCost = 0;

                foreach ($parts as $partData) {
                    $part = $this->addPartToMaintenance($record->id, $partData);
                    $totalPartsCost += $part->total_price ?? 0;
                }

                // Update parts cost on record
                $record->update([
                    'parts_cost' => $totalPartsCost,
                    'total_cost' => ($laborCost ?? 0) + $totalPartsCost,
                    'updated_by' => auth()->id(),
                ]);
            } else {
                $record->update([
                    'parts_cost' => 0,
                    'total_cost' => $laborCost ?? 0,
                    'updated_by' => auth()->id(),
                ]);
            }

            // Update related maintenance schedules
            $this->updateRelatedSchedules($record);

            return $record->load(['parts', 'performedByUser', 'vendorCompany']);
        });
    }

    /**
     * Add a part to a maintenance record
     */
    public function addPartToMaintenance(int $maintenanceRecordId, array $partData): MaintenancePart
    {
        // Calculate total price if not provided
        if (!isset($partData['total_price']) && isset($partData['quantity']) && isset($partData['unit_price'])) {
            $partData['total_price'] = $partData['quantity'] * $partData['unit_price'];
        }

        $partData['maintenance_record_id'] = $maintenanceRecordId;
        $partData['created_by'] = auth()->id();
        $partData['updated_by'] = auth()->id();

        $part = MaintenancePart::create($partData);

        // Recalculate maintenance record totals
        $this->recalculateMaintenanceCosts($maintenanceRecordId);

        return $part;
    }

    /**
     * Recalculate maintenance record costs
     */
    public function recalculateMaintenanceCosts(int $maintenanceRecordId): void
    {
        $record = MaintenanceRecord::findOrFail($maintenanceRecordId);

        $partsCost = $record->parts()->sum('total_price') ?? 0;
        $laborCost = $record->labor_cost ?? 0;

        $record->update([
            'parts_cost' => $partsCost,
            'total_cost' => $laborCost + $partsCost,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Update related maintenance schedules after a maintenance is performed
     */
    protected function updateRelatedSchedules(MaintenanceRecord $record): void
    {
        // Find matching schedules
        $schedules = MaintenanceSchedule::where('maintainable_type', $record->maintainable_type)
            ->where('maintainable_id', $record->maintainable_id)
            ->where('maintenance_type', $record->maintenance_type)
            ->where('is_active', true)
            ->get();

        foreach ($schedules as $schedule) {
            $schedule->updateAfterMaintenance($record);
        }
    }

    /**
     * Get upcoming maintenance for an entity
     */
    public function getUpcomingMaintenance(string $maintainableType, int $maintainableId, int $daysAhead = 30)
    {
        $entity = $this->getEntity($maintainableType, $maintainableId);

        $currentOdometer = $entity->current_odometer_miles ?? null;
        $currentHours = $entity->current_hours ?? null;

        return MaintenanceSchedule::where('maintainable_type', $maintainableType)
            ->where('maintainable_id', $maintainableId)
            ->where('is_active', true)
            ->where(function ($query) use ($daysAhead, $currentOdometer, $currentHours) {
                // Date-based upcoming
                $query->where(function ($q) use ($daysAhead) {
                    $q->whereNotNull('next_due_date')
                        ->where('next_due_date', '<=', now()->addDays($daysAhead));
                });

                // Odometer-based upcoming
                if ($currentOdometer) {
                    $query->orWhere(function ($q) use ($currentOdometer) {
                        $q->whereNotNull('next_due_odometer')
                            ->whereNotNull('notify_miles_before')
                            ->whereRaw('next_due_odometer - ? <= notify_miles_before', [$currentOdometer]);
                    });
                }

                // Hours-based upcoming
                if ($currentHours) {
                    $query->orWhere(function ($q) use ($currentHours) {
                        $q->whereNotNull('next_due_hours')
                            ->whereNotNull('notify_hours_before')
                            ->whereRaw('next_due_hours - ? <= notify_hours_before', [$currentHours]);
                    });
                }
            })
            ->with(['assignedUser', 'assignedVendor'])
            ->orderBy('next_due_date')
            ->get();
    }

    /**
     * Get overdue maintenance for an entity
     */
    public function getOverdueMaintenance(string $maintainableType, int $maintainableId)
    {
        $entity = $this->getEntity($maintainableType, $maintainableId);

        $currentOdometer = $entity->current_odometer_miles ?? null;
        $currentHours = $entity->current_hours ?? null;

        $schedules = MaintenanceSchedule::where('maintainable_type', $maintainableType)
            ->where('maintainable_id', $maintainableId)
            ->where('is_active', true)
            ->get();

        return $schedules->filter(function ($schedule) use ($currentOdometer, $currentHours) {
            return $schedule->isOverdueByDate()
                || $schedule->isOverdueByOdometer($currentOdometer)
                || $schedule->isOverdueByHours($currentHours);
        });
    }

    /**
     * Get all overdue maintenance across all entities
     */
    public function getAllOverdueMaintenance()
    {
        $schedules = MaintenanceSchedule::where('is_active', true)
            ->with(['maintainable', 'assignedUser', 'assignedVendor'])
            ->get();

        return $schedules->filter(function ($schedule) {
            $entity = $schedule->maintainable;
            $currentOdometer = $entity->current_odometer_miles ?? null;
            $currentHours = $entity->current_hours ?? null;

            return $schedule->isOverdueByDate()
                || $schedule->isOverdueByOdometer($currentOdometer)
                || $schedule->isOverdueByHours($currentHours);
        });
    }

    /**
     * Mark core charge as returned
     */
    public function returnCoreCharge(int $partId, Carbon $returnedDate): MaintenancePart
    {
        $part = MaintenancePart::findOrFail($partId);

        $part->update([
            'core_returned' => true,
            'core_returned_date' => $returnedDate,
            'updated_by' => auth()->id(),
        ]);

        // Recalculate costs if needed
        $this->recalculateMaintenanceCosts($part->maintenance_record_id);

        return $part;
    }

    /**
     * Helper: Get entity (Equipment or Vehicle)
     */
    protected function getEntity(string $type, int $id)
    {
        if ($type === 'App\\Models\\Equipment') {
            return Equipment::findOrFail($id);
        } elseif ($type === 'App\\Models\\Vehicle') {
            return Vehicle::findOrFail($id);
        }

        throw new \InvalidArgumentException("Invalid maintainable type: {$type}");
    }
}
