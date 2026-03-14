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
        Schema::create('vehicle_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');

            // From Location Data
            $table->string('from_location_type', 50)->nullable();
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->decimal('from_location_gps_lat', 10, 8)->nullable();
            $table->decimal('from_location_gps_lng', 11, 8)->nullable();

            // To Location Data
            $table->string('to_location_type', 50);
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->decimal('to_location_gps_lat', 10, 8)->nullable();
            $table->decimal('to_location_gps_lng', 11, 8)->nullable();

            // Movement Details
            $table->enum('movement_type', ['pickup', 'dropoff', 'transfer', 'return_to_yard'])->default('transfer');
            $table->decimal('odometer_reading', 10, 1)->nullable();

            // Timestamps
            $table->timestamp('moved_at');
            $table->foreignId('moved_by')->constrained('users')->onDelete('restrict');

            // QR Code Scan Metadata
            $table->boolean('scanned_via_qr')->default(false);
            $table->text('device_info')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('vehicle_id');
            $table->index('moved_at');
            $table->index(['from_location_type', 'from_location_id']);
            $table->index(['to_location_type', 'to_location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_movements');
    }
};
