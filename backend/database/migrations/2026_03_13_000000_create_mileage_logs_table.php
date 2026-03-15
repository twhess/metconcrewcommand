<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mileage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('driver_user_id')->constrained('users')->onDelete('restrict');
            $table->date('trip_date');
            $table->decimal('start_odometer', 10, 1);
            $table->decimal('end_odometer', 10, 1);
            $table->decimal('distance_miles', 10, 1);
            $table->enum('trip_type', ['business', 'personal', 'commute', 'delivery', 'service_call'])->default('business');
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('vehicle_id');
            $table->index('driver_user_id');
            $table->index('trip_date');
            $table->index('trip_type');
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mileage_logs');
    }
};
