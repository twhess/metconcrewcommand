<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MileageLog;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MileageLogController extends Controller
{
    public function index(Request $request, int $vehicleId): JsonResponse
    {
        $this->authorize('viewAny', MileageLog::class);

        $vehicle = Vehicle::findOrFail($vehicleId);

        $query = $vehicle->mileageLogs()->with(['driver', 'project']);

        if ($request->has('start_date')) {
            $query->where('trip_date', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('trip_date', '<=', $request->input('end_date'));
        }

        if ($request->has('trip_type')) {
            $query->where('trip_type', $request->input('trip_type'));
        }

        if ($request->has('driver_user_id')) {
            $query->where('driver_user_id', $request->input('driver_user_id'));
        }

        $logs = $query->orderBy('trip_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function store(Request $request, int $vehicleId): JsonResponse
    {
        $this->authorize('create', MileageLog::class);

        $vehicle = Vehicle::findOrFail($vehicleId);

        $validated = $request->validate([
            'driver_user_id' => 'required|exists:users,id',
            'trip_date' => 'required|date',
            'start_odometer' => 'required|numeric|min:0',
            'end_odometer' => 'required|numeric|gte:start_odometer',
            'trip_type' => 'required|in:business,personal,commute,delivery,service_call',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['vehicle_id'] = $vehicle->id;
        $validated['distance_miles'] = $validated['end_odometer'] - $validated['start_odometer'];
        $validated['created_by'] = auth()->id();

        $log = MileageLog::create($validated);

        // Update vehicle odometer if this log's end reading is higher
        if ($validated['end_odometer'] > $vehicle->current_odometer_miles) {
            $vehicle->update([
                'current_odometer_miles' => $validated['end_odometer'],
                'last_odometer_reading_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mileage log created successfully',
            'data' => $log->load(['driver', 'project']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $log = MileageLog::findOrFail($id);

        $this->authorize('update', $log);

        $validated = $request->validate([
            'driver_user_id' => 'sometimes|required|exists:users,id',
            'trip_date' => 'sometimes|required|date',
            'start_odometer' => 'sometimes|required|numeric|min:0',
            'end_odometer' => 'sometimes|required|numeric',
            'trip_type' => 'sometimes|required|in:business,personal,commute,delivery,service_call',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $startOdo = $validated['start_odometer'] ?? $log->start_odometer;
        $endOdo = $validated['end_odometer'] ?? $log->end_odometer;

        if ($endOdo < $startOdo) {
            return response()->json([
                'success' => false,
                'message' => 'End odometer must be greater than or equal to start odometer',
            ], 422);
        }

        $validated['distance_miles'] = $endOdo - $startOdo;
        $validated['updated_by'] = auth()->id();

        $log->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mileage log updated successfully',
            'data' => $log->load(['driver', 'project']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $log = MileageLog::findOrFail($id);

        $this->authorize('delete', $log);

        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mileage log deleted successfully',
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $this->authorize('report', MileageLog::class);

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        $query = MileageLog::query();

        if ($request->has('start_date')) {
            $query->where('trip_date', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('trip_date', '<=', $request->input('end_date'));
        }

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }

        $byVehicle = (clone $query)
            ->select('vehicle_id', DB::raw('SUM(distance_miles) as total_miles'), DB::raw('COUNT(*) as trip_count'))
            ->groupBy('vehicle_id')
            ->with('vehicle:id,name,vehicle_number')
            ->get();

        $byTripType = (clone $query)
            ->select('trip_type', DB::raw('SUM(distance_miles) as total_miles'), DB::raw('COUNT(*) as trip_count'))
            ->groupBy('trip_type')
            ->get();

        $totals = (clone $query)
            ->select(
                DB::raw('SUM(distance_miles) as total_miles'),
                DB::raw('COUNT(*) as total_trips'),
                DB::raw('AVG(distance_miles) as avg_trip_distance')
            )
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'by_vehicle' => $byVehicle,
                'by_trip_type' => $byTripType,
                'totals' => $totals,
            ],
        ]);
    }
}
