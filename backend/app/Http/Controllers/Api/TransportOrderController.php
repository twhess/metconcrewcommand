<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportOrder;
use App\Services\TransportOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportOrderController extends Controller
{
    protected TransportOrderService $service;

    public function __construct(TransportOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * List transport orders with filters
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TransportOrder::class);

        $filters = $request->only(['status', 'date', 'priority', 'driver_id', 'is_adhoc']);

        $orders = $this->service->getOrdersForDispatch($filters);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Create a new transport order
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', TransportOrder::class);

        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'pickup_location_type' => 'nullable|string|in:project,yard,shop',
            'pickup_location_id' => 'nullable|integer',
            'dropoff_location_type' => 'required|string|in:project,yard,shop',
            'dropoff_location_id' => 'required|integer',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'special_instructions' => 'nullable|string',
            'assigned_driver_id' => 'nullable|exists:users,id',
            'assigned_vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        try {
            $order = $this->service->createOrder($validated);

            // If driver was provided at creation, assign them
            if (!empty($validated['assigned_driver_id']) && !empty($validated['assigned_vehicle_id'])) {
                $order = $this->service->assignDriver(
                    $order->id,
                    $validated['assigned_driver_id'],
                    $validated['assigned_vehicle_id']
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Transport order created successfully',
                'data' => $order->load(['equipment', 'assignedDriver', 'assignedVehicle', 'requestedByUser']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show a single transport order
     */
    public function show(int $id): JsonResponse
    {
        $order = TransportOrder::with([
            'equipment',
            'assignedDriver',
            'assignedVehicle',
            'requestedByUser',
            'pickupMovement.movedByUser',
            'dropoffMovement.movedByUser',
        ])->findOrFail($id);

        $this->authorize('view', $order);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Update a transport order (before pickup)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $order = TransportOrder::findOrFail($id);

        $this->authorize('view', $order);

        if (!in_array($order->status, ['requested', 'assigned'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update order after pickup.',
            ], 422);
        }

        $validated = $request->validate([
            'dropoff_location_type' => 'nullable|string|in:project,yard,shop',
            'dropoff_location_id' => 'nullable|integer',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'scheduled_date' => 'nullable|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'special_instructions' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();
        $order->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transport order updated',
            'data' => $order->load(['equipment', 'assignedDriver', 'assignedVehicle']),
        ]);
    }

    /**
     * Assign driver and vehicle to an order
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $order = TransportOrder::findOrFail($id);
        $this->authorize('assign', $order);

        $validated = $request->validate([
            'assigned_driver_id' => 'required|exists:users,id',
            'assigned_vehicle_id' => 'required|exists:vehicles,id',
        ]);

        try {
            $order = $this->service->assignDriver($id, $validated['assigned_driver_id'], $validated['assigned_vehicle_id']);

            return response()->json([
                'success' => true,
                'message' => 'Driver assigned successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Driver confirms pickup
     */
    public function pickup(Request $request, int $id): JsonResponse
    {
        $order = TransportOrder::findOrFail($id);
        $this->authorize('execute', $order);

        $validated = $request->validate([
            'hours_reading' => 'nullable|numeric|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'scanned_via_qr' => 'nullable|boolean',
            'device_info' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $order = $this->service->executePickup($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Equipment picked up successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Driver confirms dropoff
     */
    public function dropoff(Request $request, int $id): JsonResponse
    {
        $order = TransportOrder::findOrFail($id);
        $this->authorize('execute', $order);

        $validated = $request->validate([
            'hours_reading' => 'nullable|numeric|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'scanned_via_qr' => 'nullable|boolean',
            'device_info' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $order = $this->service->executeDropoff($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Equipment dropped off successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Ad-hoc pickup — driver initiates without a pre-existing order
     */
    public function adhocPickup(Request $request): JsonResponse
    {
        $this->authorize('create', TransportOrder::class);

        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'dropoff_location_type' => 'required|string|in:project,yard,shop',
            'dropoff_location_id' => 'required|integer',
            'transport_vehicle_id' => 'nullable|exists:vehicles,id',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'hours_reading' => 'nullable|numeric|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'scanned_via_qr' => 'nullable|boolean',
            'device_info' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $order = $this->service->createAdhocOrder($validated);

            return response()->json([
                'success' => true,
                'message' => 'Ad-hoc pickup recorded. Equipment is now in transit.',
                'data' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel an order
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = TransportOrder::findOrFail($id);
        $this->authorize('cancel', $order);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        try {
            $order = $this->service->cancelOrder($id, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Transport order cancelled',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Dispatch summary — counts by status for a given date
     */
    public function dispatchSummary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TransportOrder::class);

        $date = $request->query('date');

        return response()->json([
            'success' => true,
            'data' => $this->service->getDispatchSummary($date),
        ]);
    }

    /**
     * My assignments — orders for the authenticated driver
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $date = $request->query('date');
        $orders = $this->service->getOrdersForDriver(auth()->id(), $date);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }
}
