<?php

namespace App\Services;

use App\Models\User;
use App\Models\Equipment;
use App\Models\Schedule;
use App\Models\InventoryItem;
use Carbon\Carbon;

class ReportService
{
    protected SchedulingService $schedulingService;
    protected EquipmentService $equipmentService;
    protected InventoryService $inventoryService;

    public function __construct(
        SchedulingService $schedulingService,
        EquipmentService $equipmentService,
        InventoryService $inventoryService
    ) {
        $this->schedulingService = $schedulingService;
        $this->equipmentService = $equipmentService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get available resources for a specific date
     */
    public function getAvailableResources(string $date): array
    {
        return [
            'date' => $date,
            'available_employees' => $this->getAvailableEmployeesReport($date),
            'unavailable_employees' => $this->getUnavailableEmployeesReport($date),
            'available_equipment' => $this->schedulingService->getAvailableEquipment($date),
            'scheduled_equipment' => $this->getScheduledEquipment($date),
            'low_stock_items' => $this->inventoryService->getLowStockItems(),
        ];
    }

    /**
     * Get available employees with details
     */
    private function getAvailableEmployeesReport(string $date): array
    {
        $availableEmployees = $this->schedulingService->getAvailableEmployees($date);

        return array_map(function ($employee) {
            return [
                'id' => $employee['id'],
                'name' => $employee['name'],
                'email' => $employee['email'],
                'phone' => $employee['phone'],
                'hourly_rate' => $employee['hourly_rate'],
            ];
        }, $availableEmployees);
    }

    /**
     * Get unavailable employees with reasons
     */
    private function getUnavailableEmployeesReport(string $date): array
    {
        $allEmployees = User::where('is_active', true)->get();
        $unavailable = [];

        foreach ($allEmployees as $employee) {
            if (!$this->schedulingService->validateEmployeeAvailability($employee->id, $date)) {
                $reason = 'Unknown';

                if (!$employee->is_available) {
                    $reason = 'Marked Unavailable';
                } elseif ($employee->isOnVacation($date)) {
                    $vacation = $employee->vacations()
                        ->where('start_date', '<=', $date)
                        ->where('end_date', '>=', $date)
                        ->where('approved', true)
                        ->first();
                    $reason = 'On Vacation';
                    $vacationDetails = [
                        'type' => $vacation->type ?? 'vacation',
                        'start_date' => $vacation->start_date ?? null,
                        'end_date' => $vacation->end_date ?? null,
                    ];
                } else {
                    // Already scheduled
                    $schedule = Schedule::whereHas('crewAssignments', function ($query) use ($employee) {
                        $query->where('user_id', $employee->id);
                    })->where('date', $date)->with('project')->first();

                    if ($schedule) {
                        $reason = 'Scheduled';
                        $scheduleDetails = [
                            'project_name' => $schedule->project->name ?? 'Unknown',
                            'start_time' => $schedule->start_time,
                        ];
                    }
                }

                $unavailable[] = [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'reason' => $reason,
                    'vacation_details' => $vacationDetails ?? null,
                    'schedule_details' => $scheduleDetails ?? null,
                ];
            }
        }

        return $unavailable;
    }

    /**
     * Get equipment that is scheduled for a specific date
     */
    private function getScheduledEquipment(string $date): array
    {
        $schedules = Schedule::where('date', $date)
            ->with(['equipmentAssignments.equipment', 'project'])
            ->get();

        $scheduled = [];
        foreach ($schedules as $schedule) {
            foreach ($schedule->equipmentAssignments as $assignment) {
                $scheduled[] = [
                    'equipment_id' => $assignment->equipment->id,
                    'equipment_name' => $assignment->equipment->name,
                    'equipment_number' => $assignment->equipment->equipment_number,
                    'project_name' => $schedule->project->name,
                    'start_time' => $schedule->start_time,
                ];
            }
        }

        return $scheduled;
    }

    /**
     * Get equipment location report
     */
    public function getEquipmentLocationReport(): array
    {
        return $this->equipmentService->getLocationReport();
    }

    /**
     * Get inventory status report
     */
    public function getInventoryStatusReport(): array
    {
        return $this->inventoryService->getInventoryStatus();
    }

    /**
     * Get daily schedule summary
     */
    public function getDailyScheduleSummary(string $date): array
    {
        $schedules = Schedule::where('date', $date)
            ->with(['project', 'crewAssignments.user', 'equipmentAssignments.equipment'])
            ->orderBy('start_time')
            ->get();

        return [
            'date' => $date,
            'total_schedules' => $schedules->count(),
            'total_crew_members' => $schedules->sum(fn($s) => $s->crewAssignments->count()),
            'total_equipment' => $schedules->sum(fn($s) => $s->equipmentAssignments->count()),
            'schedules' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'project_name' => $schedule->project->name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'status' => $schedule->status,
                    'crew_count' => $schedule->crewAssignments->count(),
                    'equipment_count' => $schedule->equipmentAssignments->count(),
                    'foreman' => $schedule->crewAssignments->where('is_foreman', true)->first()?->user->name,
                ];
            })->toArray(),
        ];
    }
}
