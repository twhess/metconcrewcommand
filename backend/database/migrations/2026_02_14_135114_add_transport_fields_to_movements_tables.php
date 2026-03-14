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
        // Add transport fields to equipment_movements
        Schema::table('equipment_movements', function (Blueprint $table) {
            // Transporter (user doing the transport)
            $table->foreignId('transported_by_user_id')->nullable()->after('moved_by')->constrained('users')->onDelete('set null');

            // Vehicle used for transport (can be from vehicles table or temporary)
            $table->foreignId('transport_vehicle_id')->nullable()->after('transported_by_user_id')->constrained('vehicles')->onDelete('set null');
            $table->string('temp_transport_vehicle')->nullable()->after('transport_vehicle_id'); // For vehicles not in system

            // Is this rental equipment?
            $table->boolean('is_rental')->default(false)->after('temp_transport_vehicle');
            $table->string('rental_company')->nullable()->after('is_rental');
            $table->string('rental_agreement_number')->nullable()->after('rental_company');
        });

        // Add transport fields to vehicle_movements
        Schema::table('vehicle_movements', function (Blueprint $table) {
            // Transporter (user doing the transport)
            $table->foreignId('transported_by_user_id')->nullable()->after('moved_by')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_movements', function (Blueprint $table) {
            $table->dropForeign(['transported_by_user_id']);
            $table->dropForeign(['transport_vehicle_id']);
            $table->dropColumn([
                'transported_by_user_id',
                'transport_vehicle_id',
                'temp_transport_vehicle',
                'is_rental',
                'rental_company',
                'rental_agreement_number',
            ]);
        });

        Schema::table('vehicle_movements', function (Blueprint $table) {
            $table->dropForeign(['transported_by_user_id']);
            $table->dropColumn('transported_by_user_id');
        });
    }
};
