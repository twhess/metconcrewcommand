<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    protected SchedulingService $schedulingService;

    public function __construct(SchedulingService $schedulingService)
    {
        $this->schedulingService = $schedulingService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Schedule::with(['project', 'crewAssignments.user', 'equipmentAssignments.equipment', 'materials']);

        if ($request->has('date')) {
            $query->where('date', $request->input('date'));
        }

        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $schedules = $query->orderBy('date')->orderBy('start_time')->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'status' => 'nullable|in:scheduled,in_progress,completed',
            'dispatch_instructions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $schedule = Schedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule->load(['project', 'crewAssignments', 'equipmentAssignments']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $schedule = Schedule::with([
            'project',
            'crewAssignments.user',
            'equipmentAssignments.equipment',
            'materials',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $schedule,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'project_id' => 'sometimes|required|exists:projects,id',
            'date' => 'sometimes|required|date',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'status' => 'nullable|in:scheduled,in_progress,completed',
            'dispatch_instructions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule->load(['project', 'crewAssignments', 'equipmentAssignments']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully',
        ]);
    }

    public function assignCrew(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'foreman_id' => 'nullable|exists:users,id',
        ]);

        $result = $this->schedulingService->assignCrewToSchedule(
            $id,
            $validated['user_ids'],
            $validated['foreman_id'] ?? null
        );

        $schedule = Schedule::with(['crewAssignments.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Crew assigned successfully',
            'assigned' => $result['assigned'],
            'errors' => $result['errors'],
            'data' => $schedule,
        ]);
    }

    public function assignEquipment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'equipment_ids' => 'required|array',
            'equipment_ids.*' => 'exists:equipment,id',
        ]);

        $result = $this->schedulingService->assignEquipmentToSchedule(
            $id,
            $validated['equipment_ids']
        );

        $schedule = Schedule::with(['equipmentAssignments.equipment'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Equipment assigned successfully',
            'assigned' => $result['assigned'],
            'errors' => $result['errors'],
            'data' => $schedule,
        ]);
    }

    public function addMaterials(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'materials' => 'required|array',
            'materials.*.type' => 'required|in:concrete,gravel,other',
            'materials.*.quantity' => 'nullable|numeric|min:0',
            'materials.*.unit' => 'nullable|string',
            'materials.*.yards_per_hour' => 'nullable|numeric|min:0',
            'materials.*.additives' => 'nullable|string',
            'materials.*.dispatch_number' => 'nullable|string',
            'materials.*.special_instructions' => 'nullable|string',
        ]);

        foreach ($validated['materials'] as $materialData) {
            $materialData['schedule_id'] = $id;
            $materialData['created_by'] = auth()->id();
            $schedule->materials()->create($materialData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Materials added successfully',
            'data' => $schedule->load('materials'),
        ]);
    }

    public function duplicate(int $id): JsonResponse
    {
        $originalSchedule = Schedule::with([
            'crewAssignments',
            'equipmentAssignments',
            'materials',
        ])->findOrFail($id);

        // Calculate next day
        $nextDate = date('Y-m-d', strtotime($originalSchedule->date . ' +1 day'));

        // Create new schedule with same details
        $newSchedule = Schedule::create([
            'project_id' => $originalSchedule->project_id,
            'date' => $nextDate,
            'start_time' => $originalSchedule->start_time,
            'end_time' => $originalSchedule->end_time,
            'status' => 'scheduled',
            'dispatch_instructions' => $originalSchedule->dispatch_instructions,
            'notes' => $originalSchedule->notes,
            'created_by' => auth()->id(),
        ]);

        $assignedCrew = [];
        $assignedEquipment = [];
        $crewErrors = [];
        $equipmentErrors = [];

        // Copy crew assignments with availability validation
        $userIds = $originalSchedule->crewAssignments->pluck('user_id')->toArray();
        $foremanId = $originalSchedule->crewAssignments->where('is_foreman', true)->first()?->user_id;

        if (!empty($userIds)) {
            $crewResult = $this->schedulingService->assignCrewToSchedule(
                $newSchedule->id,
                $userIds,
                $foremanId
            );
            $assignedCrew = $crewResult['assigned'];
            $crewErrors = $crewResult['errors'];
        }

        // Copy equipment assignments with availability validation
        $equipmentIds = $originalSchedule->equipmentAssignments->pluck('equipment_id')->toArray();

        if (!empty($equipmentIds)) {
            $equipmentResult = $this->schedulingService->assignEquipmentToSchedule(
                $newSchedule->id,
                $equipmentIds
            );
            $assignedEquipment = $equipmentResult['assigned'];
            $equipmentErrors = $equipmentResult['errors'];
        }

        // Copy materials (no validation needed)
        foreach ($originalSchedule->materials as $material) {
            $newSchedule->materials()->create([
                'type' => $material->type,
                'quantity' => $material->quantity,
                'unit' => $material->unit,
                'yards_per_hour' => $material->yards_per_hour,
                'additives' => $material->additives,
                'dispatch_number' => $material->dispatch_number,
                'special_instructions' => $material->special_instructions,
                'created_by' => auth()->id(),
            ]);
        }

        $newSchedule->load(['project', 'crewAssignments.user', 'equipmentAssignments.equipment', 'materials']);

        return response()->json([
            'success' => true,
            'message' => 'Schedule duplicated successfully',
            'data' => $newSchedule,
            'crew_assigned' => $assignedCrew,
            'crew_errors' => $crewErrors,
            'equipment_assigned' => $assignedEquipment,
            'equipment_errors' => $equipmentErrors,
        ], 201);
    }

    public function deleteMaterial(int $scheduleId, int $materialId): JsonResponse
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $material = $schedule->materials()->findOrFail($materialId);

        $material->delete();

        return response()->json([
            'success' => true,
            'message' => 'Material deleted successfully',
        ]);
    }

    public function removeCrewMember(int $scheduleId, int $userId): JsonResponse
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $assignment = $schedule->crewAssignments()->where('user_id', $userId)->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Crew member not found on this schedule',
            ], 404);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Crew member removed successfully',
            'data' => $schedule->load(['crewAssignments.user']),
        ]);
    }

    public function removeEquipment(int $scheduleId, int $equipmentId): JsonResponse
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $assignment = $schedule->equipmentAssignments()->where('equipment_id', $equipmentId)->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found on this schedule',
            ], 404);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equipment removed successfully',
            'data' => $schedule->load(['equipmentAssignments.equipment']),
        ]);
    }
}
