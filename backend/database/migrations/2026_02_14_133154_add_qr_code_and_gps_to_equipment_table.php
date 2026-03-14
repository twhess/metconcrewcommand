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
        Schema::table('equipment', function (Blueprint $table) {
            // Add QR code for transport tracking
            $table->string('qr_code')->unique()->after('equipment_number');

            // Add GPS coordinates for location tracking
            $table->decimal('current_location_gps_lat', 10, 8)->nullable()->after('current_location_id');
            $table->decimal('current_location_gps_lng', 11, 8)->nullable()->after('current_location_gps_lat');

            // Add hour meter tracking for equipment with engines
            $table->boolean('has_hour_meter')->default(false)->after('current_location_gps_lng');
            $table->decimal('current_hours', 10, 1)->default(0)->after('has_hour_meter');
            $table->timestamp('last_hours_reading_at')->nullable()->after('current_hours');

            // Add index for QR code
            $table->index('qr_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['qr_code']);
            $table->dropColumn([
                'qr_code',
                'current_location_gps_lat',
                'current_location_gps_lng',
                'has_hour_meter',
                'current_hours',
                'last_hours_reading_at',
            ]);
        });
    }
};
