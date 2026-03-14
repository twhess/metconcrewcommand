<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vehicle_number')->unique()->nullable();
            $table->string('qr_code')->unique();

            // Vehicle Identification
            $table->string('vin', 17)->unique()->nullable();
            $table->string('license_plate', 20)->nullable();
            $table->string('registration_state', 2)->nullable();
            $table->date('registration_expiration')->nullable();

            // Vehicle Details
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('vehicle_type', 50)->nullable();

            // Specifications
            $table->string('fuel_type', 30)->nullable();
            $table->decimal('tank_capacity_gallons', 6, 2)->nullable();
            $table->string('weight_class', 20)->nullable();
            $table->unsignedInteger('gvwr_pounds')->nullable();
            $table->unsignedInteger('towing_capacity_pounds')->nullable();

            // Current Readings
            $table->decimal('current_odometer_miles', 10, 1)->default(0);
            $table->timestamp('last_odometer_reading_at')->nullable();

            // DOT/Compliance
            $table->boolean('requires_cdl')->default(false);
            $table->boolean('requires_dot_inspection')->default(false);
            $table->date('last_dot_inspection_date')->nullable();
            $table->date('next_dot_inspection_due')->nullable();

            // Insurance
            $table->string('insurance_policy_number', 100)->nullable();
            $table->string('insurance_provider')->nullable();
            $table->date('insurance_expiration')->nullable();

            // Status & Location
            $table->enum('status', ['active', 'inactive', 'maintenance', 'out_of_service', 'in_transit'])->default('active');
            $table->string('current_location_type', 50)->nullable();
            $table->unsignedBigInteger('current_location_id')->nullable();
            $table->decimal('current_location_gps_lat', 10, 8)->nullable();
            $table->decimal('current_location_gps_lng', 11, 8)->nullable();

            // Assigned Operator
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Notes
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('vehicle_type');
            $table->index(['current_location_type', 'current_location_id']);
            $table->index('qr_code');
            $table->index('assigned_to_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
