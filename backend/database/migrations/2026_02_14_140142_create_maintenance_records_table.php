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
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation (equipment or vehicle)
            $table->string('maintainable_type', 50);
            $table->unsignedBigInteger('maintainable_id');
            $table->index(['maintainable_type', 'maintainable_id']);

            // Maintenance details
            $table->string('maintenance_type', 100); // oil_change, tire_rotation, brake_service, etc.
            $table->enum('category', ['preventive', 'corrective', 'inspection', 'warranty'])->default('preventive');

            // When & who
            $table->timestamp('performed_at');
            $table->enum('performed_by_type', ['internal', 'vendor'])->default('internal');
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('vendor_company_id')->nullable()->constrained('companies')->onDelete('set null');

            // Readings at time of service
            $table->decimal('odometer_at_service', 10, 1)->nullable();
            $table->decimal('hours_at_service', 10, 1)->nullable();

            // Cost tracking
            $table->decimal('labor_hours', 6, 2)->nullable();
            $table->decimal('labor_cost', 10, 2)->nullable();
            $table->decimal('parts_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();

            // Next service due
            $table->date('next_due_date')->nullable();
            $table->decimal('next_due_odometer', 10, 1)->nullable();
            $table->decimal('next_due_hours', 10, 1)->nullable();

            // Warranty
            $table->boolean('is_warranty_work')->default(false);
            $table->string('warranty_claim_number', 100)->nullable();
            $table->string('warranty_provider')->nullable();

            // Documentation
            $table->string('work_order_number', 100)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('maintenance_type');
            $table->index('performed_at');
            $table->index('next_due_date');
            $table->index('vendor_company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
