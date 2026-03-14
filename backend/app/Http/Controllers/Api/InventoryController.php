<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::with('stock.location');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $items = $query->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:inventory_items',
            'barcode' => 'nullable|string|max:255|unique:inventory_items',
            'qr_code' => 'nullable|string|max:255|unique:inventory_items',
            'type' => 'required|in:trackable,non_trackable',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();

        $item = InventoryItem::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item created successfully',
            'data' => $item,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = InventoryItem::with(['stock.location', 'transactions'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:inventory_items,sku,' . $id,
            'barcode' => 'nullable|string|max:255|unique:inventory_items,barcode,' . $id,
            'qr_code' => 'nullable|string|max:255|unique:inventory_items,qr_code,' . $id,
            'type' => 'sometimes|required|in:trackable,non_trackable',
            'category' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item updated successfully',
            'data' => $item,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory item deleted successfully',
        ]);
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'from_location_id' => 'required|exists:inventory_locations,id',
            'to_location_id' => 'required|exists:inventory_locations,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $transaction = $this->inventoryService->moveInventory(
            $id,
            $validated['from_location_id'],
            $validated['to_location_id'],
            $validated['quantity'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Inventory moved successfully',
            'data' => $transaction,
        ]);
    }

    public function recordUsage(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'from_location_id' => 'required|exists:inventory_locations,id',
            'project_id' => 'required|exists:projects,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $transaction = $this->inventoryService->recordUsage(
            $id,
            $validated['from_location_id'],
            $validated['project_id'],
            $validated['quantity'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Usage recorded successfully',
            'data' => $transaction,
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $items = $this->inventoryService->getLowStockItems();

        return response()->json([
            'success' => true,
            'data' => $items,
            'count' => count($items),
        ]);
    }

    public function orderSuggestions(Request $request): JsonResponse
    {
        $suggestions = $this->inventoryService->getOrderSuggestions();

        return response()->json([
            'success' => true,
            'data' => $suggestions,
            'count' => count($suggestions),
        ]);
    }

    public function byLocation(Request $request, int $locationId): JsonResponse
    {
        $inventory = $this->inventoryService->getLocationInventory($locationId);

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    public function byProject(Request $request, int $projectId): JsonResponse
    {
        $usage = $this->inventoryService->getProjectUsageSummary($projectId);

        return response()->json([
            'success' => true,
            'data' => $usage,
        ]);
    }

    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'location_id' => 'required|exists:inventory_locations,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $transaction = $this->inventoryService->receiveInventory(
            $validated['inventory_item_id'],
            $validated['location_id'],
            $validated['quantity'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Inventory received successfully',
            'data' => $transaction,
        ]);
    }

    public function receiveBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:inventory_locations,id',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $transactions = $this->inventoryService->receiveBatch(
            $validated['location_id'],
            $validated['items'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => count($transactions) . ' items received successfully',
            'data' => $transactions,
        ]);
    }

    public function scanCode(string $code): JsonResponse
    {
        $item = InventoryItem::where('qr_code', $code)
            ->orWhere('barcode', $code)
            ->orWhere('sku', $code)
            ->with('stock.location')
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found for code: ' . $code
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }
}
