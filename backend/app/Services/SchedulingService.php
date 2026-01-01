<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use App\Models\Equipment;
use App\Models\CrewAssignment;
use App\Models\EquipmentAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    /**
     * Validate if an employee is available for a specific date
     * Returns true if available, false if already scheduled or on approved vacation
     */
    public function validateEmployeeAvailability(int $userId, string $date): bool
    {
        $user = User::find($userId);

        if (!$user || !$user->is_active || !$user->is_available) {
            return false;
        }

        // Check if user is on approved vacation
        if ($user->isOnVacation($date)) {
            return false;
        }

        // Check if user is already scheduled on this date
        $existingAssignment = CrewAssignment::whereHas('schedule', function ($query) use ($date) {
            $query->where('date', $date);
        })->where('user_id', $userId)->exists();

        return !$existingAssignment;
    }

    /**
     * Validate if equipment is available for a specific date
     * Returns true if available, false if already scheduled
     */
    public function validateEquipmentAvailability(int $equipmentId, string $date): bool
    {
        $equipment = Equipment::find($equipmentId);

        if (!$equipment || $equipment->status !== 'active') {
            return false;
        }

        // Check if equipment is already scheduled on this date
        $existingAssignment = EquipmentAssignment::whereHas('schedule', function ($query) use ($date) {
            $query->where('date', $date);
        })->where('equipment_id', $equipmentId)->exists();

        return !$existingAssignment;
    }

    /**
     * Assign crew members to a schedule
     * $userIds is an array of user IDs
     * $foremanId is the user ID of the foreman (must be in $userIds)
     */
    public function assignCrewToSchedule(int $scheduleId, array $userIds, ?int $foremanId = null): array
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $date = $schedule->date->format('Y-m-d');
        $errors = [];
        $assigned = [];

        DB::beginTransaction();
        try {
            foreach ($userIds as $userId) {
                if (!$this->validateEmployeeAvailability($userId, $date)) {
                    $user = User::find($userId);
                    $errors[] = "Employee {$user->name} is not available on {$date}";
                    continue;
                }

                $isForeman = ($userId === $foremanId);

                CrewAssignment::create([
                    'schedule_id' => $scheduleId,
                    'user_id' => $userId,
                    'is_foreman' => $isForeman,
                    'created_by' => auth()->id(),
                ]);

                $assigned[] = $userId;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'assigned' => $assigned,
            'errors' => $errors,
        ];
    }

    /**
     * Assign equipment to a schedule
     * $equipmentIds is an array of equipment IDs
     */
    public function assignEquipmentToSchedule(int $scheduleId, array $equipmentIds): array
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $date = $schedule->date->format('Y-m-d');
        $errors = [];
        $assigned = [];

        DB::beginTransaction();
        try {
            foreach ($equipmentIds as $equipmentId) {
                if (!$this->validateEquipmentAvailability($equipmentId, $date)) {
                    $equipment = Equipment::find($equipmentId);
                    $errors[] = "Equipment {$equipment->name} is not available on {$date}";
                    continue;
                }

                EquipmentAssignment::create([
                    'schedule_id' => $scheduleId,
                    'equipment_id' => $equipmentId,
                    'created_by' => auth()->id(),
                ]);

                $assigned[] = $equipmentId;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'assigned' => $assigned,
            'errors' => $errors,
        ];
    }

    /**
     * Get available employees for a specific date
     */
    public function getAvailableEmployees(string $date): array
    {
        $users = User::where('is_active', true)
            ->where('is_available', true)
            ->get();

        return $users->filter(function ($user) use ($date) {
            return $this->validateEmployeeAvailability($user->id, $date);
        })->values()->toArray();
    }

    /**
     * Get available equipment for a specific date
     */
    public function getAvailableEquipment(string $date): array
    {
        $equipment = Equipment::where('status', 'active')->get();

        return $equipment->filter(function ($item) use ($date) {
            return $this->validateEquipmentAvailability($item->id, $date);
        })->values()->toArray();
    }

    /**
     * Remove a crew member from a schedule
     */
    public function removeCrewMember(int $scheduleId, int $userId): bool
    {
        return CrewAssignment::where('schedule_id', $scheduleId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Remove equipment from a schedule
     */
    public function removeEquipment(int $scheduleId, int $equipmentId): bool
    {
        return EquipmentAssignment::where('schedule_id', $scheduleId)
            ->where('equipment_id', $equipmentId)
            ->delete() > 0;
    }
}
