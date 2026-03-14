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
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation (equipment or vehicle)
            $table->string('maintainable_type', 50);
            $table->unsignedBigInteger('maintainable_id');
            $table->index(['maintainable_type', 'maintainable_id']);

            // Schedule details
            $table->string('maintenance_type', 100); // oil_change, tire_rotation, annual_inspection, etc.
            $table->text('description')->nullable();
            $table->enum('category', ['preventive', 'inspection', 'seasonal'])->default('preventive');

            // Frequency options (use ONE of these methods)
            $table->enum('frequency_type', ['calendar', 'usage', 'hybrid'])->default('calendar');

            // Calendar-based
            $table->integer('frequency_days')->nullable(); // Every X days
            $table->date('last_performed_date')->nullable();
            $table->date('next_due_date')->nullable();

            // Usage-based (odometer or hours)
            $table->decimal('frequency_miles', 10, 1)->nullable(); // Every X miles
            $table->decimal('frequency_hours', 10, 1)->nullable(); // Every X hours
            $table->decimal('last_performed_odometer', 10, 1)->nullable();
            $table->decimal('last_performed_hours', 10, 1)->nullable();
            $table->decimal('next_due_odometer', 10, 1)->nullable();
            $table->decimal('next_due_hours', 10, 1)->nullable();

            // Lead time notifications
            $table->integer('notify_days_before')->default(7);
            $table->decimal('notify_miles_before', 10, 1)->nullable();
            $table->decimal('notify_hours_before', 10, 1)->nullable();

            // Assignment
            $table->enum('assigned_to_type', ['internal', 'vendor'])->default('internal');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_vendor_id')->nullable()->constrained('companies')->onDelete('set null');

            // Estimated cost/time
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('estimated_labor_hours', 6, 2)->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_overdue')->default(false);
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('maintenance_type');
            $table->index('next_due_date');
            $table->index('is_active');
            $table->index('is_overdue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
