<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\Vehicle;
use App\Models\Project;
use App\Models\User;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->error('No users found. Please run UsersSeeder first.');
            return;
        }

        // Check if inventory data already exists
        if (InventoryItem::count() > 0) {
            $this->command->warn('Inventory data already seeded. Skipping.');
            $this->command->info('Summary:');
            $this->command->info('  - ' . InventoryItem::count() . ' inventory items');
            $this->command->info('  - ' . InventoryLocation::count() . ' locations');
            $this->command->info('  - ' . InventoryStock::count() . ' stock records');
            $this->command->info('  - ' . InventoryTransaction::count() . ' transactions');
            return;
        }

        $this->command->info('Seeding inventory items...');

        // Create Inventory Items
        $items = [
            // Concrete Materials
            [
                'name' => 'Portland Cement',
                'sku' => 'CEM-001',
                'barcode' => '123456789001',
                'type' => 'trackable',
                'category' => 'Concrete Materials',
                'unit' => 'bags',
                'min_quantity' => 50,
                'max_quantity' => 200,
                'description' => 'Type I/II Portland Cement, 94lb bags',
                'is_active' => true,
            ],
            [
                'name' => 'Sand',
                'sku' => 'SND-001',
                'barcode' => '123456789002',
                'type' => 'trackable',
                'category' => 'Concrete Materials',
                'unit' => 'tons',
                'min_quantity' => 10,
                'max_quantity' => 50,
                'description' => 'Concrete sand',
                'is_active' => true,
            ],
            [
                'name' => 'Gravel',
                'sku' => 'GRV-001',
                'barcode' => '123456789003',
                'type' => 'trackable',
                'category' => 'Concrete Materials',
                'unit' => 'tons',
                'min_quantity' => 10,
                'max_quantity' => 50,
                'description' => '3/4" crushed gravel',
                'is_active' => true,
            ],
            [
                'name' => 'Rebar #4',
                'sku' => 'RBR-004',
                'barcode' => '123456789004',
                'type' => 'trackable',
                'category' => 'Reinforcement',
                'unit' => 'pieces',
                'min_quantity' => 100,
                'max_quantity' => 500,
                'description' => '#4 rebar, 20ft lengths',
                'is_active' => true,
            ],
            [
                'name' => 'Rebar #5',
                'sku' => 'RBR-005',
                'barcode' => '123456789005',
                'type' => 'trackable',
                'category' => 'Reinforcement',
                'unit' => 'pieces',
                'min_quantity' => 50,
                'max_quantity' => 300,
                'description' => '#5 rebar, 20ft lengths',
                'is_active' => true,
            ],
            [
                'name' => 'Wire Mesh 6x6',
                'sku' => 'MSH-001',
                'barcode' => '123456789006',
                'type' => 'trackable',
                'category' => 'Reinforcement',
                'unit' => 'rolls',
                'min_quantity' => 20,
                'max_quantity' => 100,
                'description' => '6x6 W1.4xW1.4 welded wire mesh',
                'is_active' => true,
            ],
            // Form Materials
            [
                'name' => 'Form Plywood',
                'sku' => 'PLY-001',
                'barcode' => '123456789007',
                'type' => 'trackable',
                'category' => 'Forming',
                'unit' => 'sheets',
                'min_quantity' => 30,
                'max_quantity' => 150,
                'description' => '3/4" plywood sheets 4x8',
                'is_active' => true,
            ],
            [
                'name' => '2x4 Lumber',
                'sku' => 'LMB-204',
                'barcode' => '123456789008',
                'type' => 'trackable',
                'category' => 'Forming',
                'unit' => 'pieces',
                'min_quantity' => 100,
                'max_quantity' => 500,
                'description' => '2x4x8 dimensional lumber',
                'is_active' => true,
            ],
            [
                'name' => 'Form Release Agent',
                'sku' => 'RLS-001',
                'barcode' => '123456789009',
                'type' => 'trackable',
                'category' => 'Chemicals',
                'unit' => 'gallons',
                'min_quantity' => 10,
                'max_quantity' => 50,
                'description' => 'Concrete form release oil',
                'is_active' => true,
            ],
            // Tools & Supplies
            [
                'name' => 'Safety Cones',
                'sku' => 'SAF-001',
                'barcode' => '123456789010',
                'type' => 'trackable',
                'category' => 'Safety',
                'unit' => 'pieces',
                'min_quantity' => 20,
                'max_quantity' => 100,
                'description' => '28" orange safety cones',
                'is_active' => true,
            ],
            [
                'name' => 'Caution Tape',
                'sku' => 'SAF-002',
                'barcode' => '123456789011',
                'type' => 'trackable',
                'category' => 'Safety',
                'unit' => 'rolls',
                'min_quantity' => 10,
                'max_quantity' => 50,
                'description' => 'Yellow caution tape, 3" x 1000ft',
                'is_active' => true,
            ],
            [
                'name' => 'Work Gloves',
                'sku' => 'PPE-001',
                'barcode' => '123456789012',
                'type' => 'non_trackable',
                'category' => 'PPE',
                'unit' => 'pairs',
                'min_quantity' => 50,
                'max_quantity' => 200,
                'description' => 'Heavy duty work gloves',
                'is_active' => true,
            ],
            [
                'name' => 'Safety Glasses',
                'sku' => 'PPE-002',
                'barcode' => '123456789013',
                'type' => 'non_trackable',
                'category' => 'PPE',
                'unit' => 'pieces',
                'min_quantity' => 30,
                'max_quantity' => 150,
                'description' => 'Clear safety glasses',
                'is_active' => true,
            ],
            [
                'name' => 'Concrete Sealer',
                'sku' => 'SLR-001',
                'barcode' => '123456789014',
                'type' => 'trackable',
                'category' => 'Chemicals',
                'unit' => 'gallons',
                'min_quantity' => 15,
                'max_quantity' => 75,
                'description' => 'Acrylic concrete sealer',
                'is_active' => true,
            ],
            [
                'name' => 'Expansion Joint',
                'sku' => 'JNT-001',
                'barcode' => '123456789015',
                'type' => 'trackable',
                'category' => 'Accessories',
                'unit' => 'feet',
                'min_quantity' => 100,
                'max_quantity' => 500,
                'description' => '1/2" x 4" expansion joint material',
                'is_active' => true,
            ],
        ];

        $createdItems = [];
        foreach ($items as $itemData) {
            $itemData['created_by'] = $user->id;
            $itemData['updated_by'] = $user->id;
            $createdItems[] = InventoryItem::create($itemData);
        }

        $this->command->info('Created ' . count($createdItems) . ' inventory items.');

        // Create Inventory Locations
        $this->command->info('Creating inventory locations...');

        $locations = [
            [
                'name' => 'Main Warehouse',
                'type' => 'warehouse',
                'description' => 'Primary storage facility',
                'is_active' => true,
            ],
            [
                'name' => 'North Yard',
                'type' => 'yard',
                'description' => 'North storage yard',
                'is_active' => true,
            ],
            [
                'name' => 'Shop Storage',
                'type' => 'shop',
                'description' => 'Main shop storage area',
                'is_active' => true,
            ],
        ];

        $createdLocations = [];
        foreach ($locations as $locationData) {
            $createdLocations[] = InventoryLocation::create($locationData);
        }

        // Create truck locations for vehicles
        $vehicles = Vehicle::whereNotNull('status')->limit(3)->get();
        foreach ($vehicles as $vehicle) {
            $createdLocations[] = InventoryLocation::create([
                'name' => "Truck - {$vehicle->name}",
                'type' => 'truck',
                'vehicle_id' => $vehicle->id,
                'description' => "Inventory on {$vehicle->name}",
                'is_active' => true,
            ]);
        }

        $this->command->info('Created ' . count($createdLocations) . ' inventory locations.');

        // Create Initial Stock at Main Warehouse
        $this->command->info('Adding stock to locations...');

        $mainWarehouse = $createdLocations[0]; // Main Warehouse
        $northYard = $createdLocations[1]; // North Yard

        $stockData = [
            // Main Warehouse Stock
            [$createdItems[0], $mainWarehouse, 150],  // Portland Cement: 150 bags
            [$createdItems[1], $mainWarehouse, 35],   // Sand: 35 tons
            [$createdItems[2], $mainWarehouse, 30],   // Gravel: 30 tons
            [$createdItems[3], $mainWarehouse, 350],  // Rebar #4: 350 pieces
            [$createdItems[4], $mainWarehouse, 200],  // Rebar #5: 200 pieces
            [$createdItems[5], $mainWarehouse, 75],   // Wire Mesh: 75 rolls
            [$createdItems[6], $mainWarehouse, 100],  // Form Plywood: 100 sheets
            [$createdItems[7], $mainWarehouse, 300],  // 2x4 Lumber: 300 pieces
            [$createdItems[8], $mainWarehouse, 30],   // Form Release: 30 gallons
            [$createdItems[9], $mainWarehouse, 60],   // Safety Cones: 60 pieces
            [$createdItems[10], $mainWarehouse, 35],  // Caution Tape: 35 rolls
            [$createdItems[11], $mainWarehouse, 120], // Work Gloves: 120 pairs
            [$createdItems[12], $mainWarehouse, 80],  // Safety Glasses: 80 pieces
            [$createdItems[13], $mainWarehouse, 45],  // Concrete Sealer: 45 gallons
            [$createdItems[14], $mainWarehouse, 300], // Expansion Joint: 300 feet

            // North Yard Stock (some items)
            [$createdItems[1], $northYard, 15],       // Sand: 15 tons
            [$createdItems[2], $northYard, 20],       // Gravel: 20 tons
            [$createdItems[6], $northYard, 50],       // Form Plywood: 50 sheets
        ];

        // Add stock to trucks (smaller quantities)
        if (count($createdLocations) > 3) {
            $truck1 = $createdLocations[3];
            $stockData[] = [$createdItems[0], $truck1, 20];  // Cement: 20 bags
            $stockData[] = [$createdItems[9], $truck1, 10];  // Safety Cones: 10
            $stockData[] = [$createdItems[11], $truck1, 15]; // Work Gloves: 15 pairs
        }

        if (count($createdLocations) > 4) {
            $truck2 = $createdLocations[4];
            $stockData[] = [$createdItems[3], $truck2, 50];  // Rebar #4: 50 pieces
            $stockData[] = [$createdItems[7], $truck2, 40];  // 2x4 Lumber: 40 pieces
            $stockData[] = [$createdItems[10], $truck2, 5];  // Caution Tape: 5 rolls
        }

        foreach ($stockData as [$item, $location, $quantity]) {
            InventoryStock::create([
                'inventory_item_id' => $item->id,
                'inventory_location_id' => $location->id,
                'quantity' => $quantity,
            ]);

            // Create receive transaction for this stock
            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => 'receive',
                'from_location_id' => null,
                'to_location_id' => $location->id,
                'quantity' => $quantity,
                'notes' => 'Initial inventory',
                'created_by' => $user->id,
            ]);
        }

        $this->command->info('Created ' . count($stockData) . ' stock records with transactions.');

        // Create some sample usage transactions (if projects exist)
        $projects = Project::where('status', 'active')->limit(2)->get();
        if ($projects->count() > 0) {
            $this->command->info('Creating sample usage transactions...');

            $usageTransactions = [
                // Project 1 usage from main warehouse
                [$createdItems[0], $mainWarehouse, $projects[0], 25, 'Foundation pour'],
                [$createdItems[1], $mainWarehouse, $projects[0], 5, 'Foundation pour'],
                [$createdItems[3], $mainWarehouse, $projects[0], 100, 'Foundation reinforcement'],
            ];

            if ($projects->count() > 1) {
                // Project 2 usage
                $usageTransactions[] = [$createdItems[6], $mainWarehouse, $projects[1], 30, 'Forming'];
                $usageTransactions[] = [$createdItems[7], $mainWarehouse, $projects[1], 75, 'Forming'];
            }

            foreach ($usageTransactions as [$item, $location, $project, $quantity, $notes]) {
                // Deduct from stock
                $stock = InventoryStock::where('inventory_item_id', $item->id)
                    ->where('inventory_location_id', $location->id)
                    ->first();

                if ($stock && $stock->quantity >= $quantity) {
                    $stock->decrement('quantity', $quantity);

                    // Create usage transaction
                    InventoryTransaction::create([
                        'inventory_item_id' => $item->id,
                        'type' => 'usage',
                        'from_location_id' => $location->id,
                        'to_location_id' => null,
                        'project_id' => $project->id,
                        'quantity' => $quantity,
                        'notes' => $notes,
                        'created_by' => $user->id,
                    ]);
                }
            }

            $this->command->info('Created ' . count($usageTransactions) . ' usage transactions.');
        }

        // Create some low stock items for testing
        $this->command->info('Creating low stock test items...');

        $lowStockItem = InventoryItem::create([
            'name' => 'Concrete Nails',
            'sku' => 'NLS-001',
            'barcode' => '123456789020',
            'type' => 'trackable',
            'category' => 'Fasteners',
            'unit' => 'boxes',
            'min_quantity' => 50,
            'max_quantity' => 200,
            'description' => '3" concrete nails, 5lb boxes',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Add low stock (below minimum)
        InventoryStock::create([
            'inventory_item_id' => $lowStockItem->id,
            'inventory_location_id' => $mainWarehouse->id,
            'quantity' => 25, // Below min of 50
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $lowStockItem->id,
            'type' => 'receive',
            'to_location_id' => $mainWarehouse->id,
            'quantity' => 25,
            'notes' => 'Initial inventory - LOW STOCK',
            'created_by' => $user->id,
        ]);

        $this->command->info('✓ Inventory seeding completed successfully!');
        $this->command->info('Summary:');
        $this->command->info('  - ' . InventoryItem::count() . ' inventory items');
        $this->command->info('  - ' . InventoryLocation::count() . ' locations (including ' . $vehicles->count() . ' trucks)');
        $this->command->info('  - ' . InventoryStock::count() . ' stock records');
        $this->command->info('  - ' . InventoryTransaction::count() . ' transactions');
    }
}
