<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->enum('status', ['requested', 'assigned', 'picked_up', 'completed', 'cancelled'])->default('requested');
            $table->boolean('is_adhoc')->default(false);
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

            // Equipment being transported
            $table->foreignId('equipment_id')->constrained('equipment')->onDelete('cascade');

            // Pickup location (polymorphic: project, yard, shop)
            $table->string('pickup_location_type');
            $table->unsignedBigInteger('pickup_location_id');

            // Dropoff location (polymorphic: project, yard, shop)
            $table->string('dropoff_location_type');
            $table->unsignedBigInteger('dropoff_location_id');

            // Assignment
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');

            // Scheduling
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->text('special_instructions')->nullable();

            // Who requested
            $table->foreignId('requested_by')->constrained('users');

            // Links to actual movement records (set when pickup/dropoff happen)
            $table->foreignId('pickup_movement_id')->nullable()->constrained('equipment_movements')->onDelete('set null');
            $table->foreignId('dropoff_movement_id')->nullable()->constrained('equipment_movements')->onDelete('set null');

            // Timestamps for status transitions
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Audit
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('scheduled_date');
            $table->index('assigned_driver_id');
            $table->index('equipment_id');
            $table->index('is_adhoc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_orders');
    }
};
