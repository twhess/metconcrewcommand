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
        Schema::table('equipment_movements', function (Blueprint $table) {
            // Add GPS coordinates for from location
            $table->decimal('from_location_gps_lat', 10, 8)->nullable()->after('from_location_id');
            $table->decimal('from_location_gps_lng', 11, 8)->nullable()->after('from_location_gps_lat');

            // Add GPS coordinates for to location
            $table->decimal('to_location_gps_lat', 10, 8)->nullable()->after('to_location_id');
            $table->decimal('to_location_gps_lng', 11, 8)->nullable()->after('to_location_gps_lat');

            // Add movement type
            $table->enum('movement_type', ['pickup', 'dropoff', 'transfer', 'return_to_yard'])->default('transfer')->after('to_location_gps_lng');

            // Add hour meter reading
            $table->decimal('hours_reading', 10, 1)->nullable()->after('movement_type');

            // Add QR code scan metadata
            $table->boolean('scanned_via_qr')->default(false)->after('moved_by');
            $table->text('device_info')->nullable()->after('scanned_via_qr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_movements', function (Blueprint $table) {
            $table->dropColumn([
                'from_location_gps_lat',
                'from_location_gps_lng',
                'to_location_gps_lat',
                'to_location_gps_lng',
                'movement_type',
                'hours_reading',
                'scanned_via_qr',
                'device_info',
            ]);
        });
    }
};
