<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Move inventory between locations
     */
    public function moveInventory(
        int $inventoryItemId,
        int $fromLocationId,
        int $toLocationId,
        float $quantity,
        ?string $notes = null
    ): InventoryTransaction {
        DB::beginTransaction();
        try {
            // Validate sufficient stock at source location
            $fromStock = InventoryStock::where('inventory_item_id', $inventoryItemId)
                ->where('inventory_location_id', $fromLocationId)
                ->first();

            if (!$fromStock || $fromStock->quantity < $quantity) {
                throw new \Exception('Insufficient inventory at source location');
            }

            // Decrease stock at source
            $fromStock->decrement('quantity', $quantity);

            // Increase stock at destination (create if doesn't exist)
            $toStock = InventoryStock::firstOrCreate(
                [
                    'inventory_item_id' => $inventoryItemId,
                    'inventory_location_id' => $toLocationId,
                ],
                ['quantity' => 0]
            );
            $toStock->increment('quantity', $quantity);

            // Log transaction
            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'move',
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'quantity' => $quantity,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Record inventory usage on a project
     */
    public function recordUsage(
        int $inventoryItemId,
        int $fromLocationId,
        int $projectId,
        float $quantity,
        ?string $notes = null
    ): InventoryTransaction {
        DB::beginTransaction();
        try {
            // Validate sufficient stock
            $stock = InventoryStock::where('inventory_item_id', $inventoryItemId)
                ->where('inventory_location_id', $fromLocationId)
                ->first();

            if (!$stock || $stock->quantity < $quantity) {
                throw new \Exception('Insufficient inventory');
            }

            // Decrease stock
            $stock->decrement('quantity', $quantity);

            // Log transaction
            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'usage',
                'from_location_id' => $fromLocationId,
                'to_location_id' => null,
                'project_id' => $projectId,
                'quantity' => $quantity,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Receive inventory (add to stock)
     */
    public function receiveInventory(
        int $inventoryItemId,
        int $locationId,
        float $quantity,
        ?string $notes = null
    ): InventoryTransaction {
        DB::beginTransaction();
        try {
            // Increase stock (create if doesn't exist)
            $stock = InventoryStock::firstOrCreate(
                [
                    'inventory_item_id' => $inventoryItemId,
                    'inventory_location_id' => $locationId,
                ],
                ['quantity' => 0]
            );
            $stock->increment('quantity', $quantity);

            // Log transaction
            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'receive',
                'from_location_id' => null,
                'to_location_id' => $locationId,
                'quantity' => $quantity,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Adjust inventory (manual correction)
     */
    public function adjustInventory(
        int $inventoryItemId,
        int $locationId,
        float $newQuantity,
        ?string $notes = null
    ): InventoryTransaction {
        DB::beginTransaction();
        try {
            $stock = InventoryStock::where('inventory_item_id', $inventoryItemId)
                ->where('inventory_location_id', $locationId)
                ->first();

            $oldQuantity = $stock ? $stock->quantity : 0;
            $adjustmentQuantity = $newQuantity - $oldQuantity;

            if (!$stock) {
                $stock = InventoryStock::create([
                    'inventory_item_id' => $inventoryItemId,
                    'inventory_location_id' => $locationId,
                    'quantity' => $newQuantity,
                ]);
            } else {
                $stock->update(['quantity' => $newQuantity]);
            }

            // Log transaction
            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $inventoryItemId,
                'type' => 'adjustment',
                'from_location_id' => $locationId,
                'to_location_id' => $locationId,
                'quantity' => $adjustmentQuantity,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get inventory status report
     */
    public function getInventoryStatus(): array
    {
        $items = InventoryItem::with(['stock.location'])
            ->where('is_active', true)
            ->get();

        return $items->map(function ($item) {
            $totalStock = $item->stock->sum('quantity');
            $isLow = $item->min_quantity && $totalStock < $item->min_quantity;

            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'type' => $item->type,
                'category' => $item->category,
                'unit' => $item->unit,
                'total_stock' => $totalStock,
                'min_quantity' => $item->min_quantity,
                'is_low_stock' => $isLow,
                'locations' => $item->stock->map(function ($stock) {
                    return [
                        'location_id' => $stock->inventory_location_id,
                        'location_name' => $stock->location->name,
                        'quantity' => $stock->quantity,
                    ];
                }),
            ];
        })->toArray();
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems(): array
    {
        $items = InventoryItem::with(['stock'])
            ->where('is_active', true)
            ->whereNotNull('min_quantity')
            ->get();

        return $items->filter(function ($item) {
            $totalStock = $item->stock->sum('quantity');
            return $totalStock < $item->min_quantity;
        })->values()->toArray();
    }

    /**
     * Get inventory transaction history
     */
    public function getTransactionHistory(int $inventoryItemId, ?int $limit = null): array
    {
        $query = InventoryTransaction::where('inventory_item_id', $inventoryItemId)
            ->with(['fromLocation', 'toLocation', 'project', 'creator'])
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->toArray();
    }

    /**
     * Get order suggestions based on min/max thresholds
     */
    public function getOrderSuggestions(): array
    {
        $items = InventoryItem::with(['stock'])
            ->where('is_active', true)
            ->whereNotNull('min_quantity')
            ->whereNotNull('max_quantity')
            ->get();

        return $items->filter(function ($item) {
            $totalStock = $item->stock->sum('quantity');
            return $totalStock < $item->min_quantity;
        })->map(function ($item) {
            $currentStock = $item->stock->sum('quantity');
            $suggestedOrderQty = $item->max_quantity - $currentStock;

            return [
                'item' => $item,
                'current_stock' => $currentStock,
                'min_quantity' => $item->min_quantity,
                'max_quantity' => $item->max_quantity,
                'suggested_order_qty' => max(0, $suggestedOrderQty),
            ];
        })->values()->toArray();
    }

    /**
     * Get project usage summary
     */
    public function getProjectUsageSummary(int $projectId): array
    {
        $transactions = InventoryTransaction::where('project_id', $projectId)
            ->where('type', 'usage')
            ->with(['item', 'fromLocation', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by item and sum quantities
        $groupedByItem = $transactions->groupBy('inventory_item_id')->map(function ($itemTransactions) {
            $item = $itemTransactions->first()->item;
            $totalUsed = $itemTransactions->sum('quantity');

            return [
                'item' => $item,
                'total_used' => $totalUsed,
                'transactions' => $itemTransactions->toArray(),
            ];
        })->values();

        return [
            'usage_summary' => $groupedByItem->toArray(),
            'total_transactions' => $transactions->count(),
        ];
    }

    /**
     * Get inventory at a specific location
     */
    public function getLocationInventory(int $locationId): array
    {
        $stocks = InventoryStock::where('inventory_location_id', $locationId)
            ->with(['item', 'location'])
            ->get();

        return [
            'location' => $stocks->first()?->location,
            'stocks' => $stocks->map(function ($stock) {
                return [
                    'item' => $stock->item,
                    'quantity' => $stock->quantity,
                    'updated_at' => $stock->updated_at,
                ];
            })->toArray(),
            'total_items' => $stocks->count(),
        ];
    }

    /**
     * Batch receive inventory (multiple items at once)
     */
    public function receiveBatch(int $locationId, array $items, ?string $notes = null): array
    {
        DB::beginTransaction();
        try {
            $transactions = [];

            foreach ($items as $itemData) {
                $transaction = $this->receiveInventory(
                    $itemData['inventory_item_id'],
                    $locationId,
                    $itemData['quantity'],
                    $itemData['notes'] ?? $notes
                );
                $transactions[] = $transaction;
            }

            DB::commit();

            return $transactions;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
