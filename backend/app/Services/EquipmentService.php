<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentMovement;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EquipmentService
{
    /**
     * Move equipment to a new location
     * $locationType: 'project', 'location', 'available'
     * $locationId: project_id or inventory_location_id (null for 'available')
     */
    public function moveEquipment(int $equipmentId, string $locationType, ?int $locationId, ?string $notes = null): EquipmentMovement
    {
        $equipment = Equipment::findOrFail($equipmentId);

        DB::beginTransaction();
        try {
            // Log the movement
            $movement = EquipmentMovement::create([
                'equipment_id' => $equipmentId,
                'from_location_type' => $equipment->current_location_type,
                'from_location_id' => $equipment->current_location_id,
                'to_location_type' => $locationType,
                'to_location_id' => $locationId,
                'moved_at' => now(),
                'moved_by' => auth()->id(),
                'notes' => $notes,
            ]);

            // Update equipment's current location
            $equipment->update([
                'current_location_type' => $locationType,
                'current_location_id' => $locationId,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return $movement;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get equipment location history
     */
    public function getEquipmentHistory(int $equipmentId): array
    {
        return EquipmentMovement::where('equipment_id', $equipmentId)
            ->with(['movedByUser'])
            ->orderBy('moved_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get all equipment at a specific location
     */
    public function getEquipmentAtLocation(string $locationType, ?int $locationId = null): array
    {
        $query = Equipment::where('current_location_type', $locationType);

        if ($locationId !== null) {
            $query->where('current_location_id', $locationId);
        } else {
            $query->whereNull('current_location_id');
        }

        return $query->get()->toArray();
    }

    /**
     * Get equipment location report
     */
    public function getLocationReport(): array
    {
        $equipment = Equipment::with(['currentProject', 'latestMovement'])
            ->where('status', 'active')
            ->get();

        return $equipment->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'equipment_number' => $item->equipment_number,
                'type' => $item->type,
                'category' => $item->category,
                'current_location_type' => $item->current_location_type,
                'current_location_id' => $item->current_location_id,
                'current_location_name' => $this->getLocationName($item),
                'last_moved_at' => optional($item->latestMovement)->moved_at,
                'last_moved_by' => optional(optional($item->latestMovement)->movedByUser)->name,
            ];
        })->toArray();
    }

    /**
     * Get human-readable location name
     */
    private function getLocationName(Equipment $equipment): ?string
    {
        if (!$equipment->current_location_type) {
            return 'Not Set';
        }

        if ($equipment->current_location_type === 'available') {
            return 'Available Pool';
        }

        if ($equipment->current_location_type === 'project' && $equipment->current_location_id) {
            $project = Project::find($equipment->current_location_id);
            return $project ? $project->name : 'Unknown Project';
        }

        if ($equipment->current_location_type === 'location' && $equipment->current_location_id) {
            $location = \App\Models\InventoryLocation::find($equipment->current_location_id);
            return $location ? $location->name : 'Unknown Location';
        }

        return 'Unknown';
    }

    /**
     * Move multiple equipment items to the same location
     */
    public function bulkMoveEquipment(array $equipmentIds, string $locationType, ?int $locationId, ?string $notes = null): array
    {
        $moved = [];
        $errors = [];

        foreach ($equipmentIds as $equipmentId) {
            try {
                $movement = $this->moveEquipment($equipmentId, $locationType, $locationId, $notes);
                $moved[] = $equipmentId;
            } catch (\Exception $e) {
                $errors[] = [
                    'equipment_id' => $equipmentId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'moved' => $moved,
            'errors' => $errors,
        ];
    }
}
