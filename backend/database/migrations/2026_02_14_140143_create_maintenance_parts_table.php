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
        Schema::create('maintenance_parts', function (Blueprint $table) {
            $table->id();

            // Link to maintenance record
            $table->foreignId('maintenance_record_id')->constrained('maintenance_records')->onDelete('cascade');

            // Part details
            $table->string('part_number', 100)->nullable();
            $table->string('part_name', 255);
            $table->text('description')->nullable();

            // Quantity & pricing
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit_of_measure', 20)->default('ea'); // ea, gal, qt, ft, lb, etc.
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();

            // Vendor/supplier tracking
            $table->foreignId('vendor_company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->string('vendor_part_number', 100)->nullable();
            $table->string('invoice_number', 100)->nullable();

            // Warranty
            $table->boolean('is_warranty_part')->default(false);
            $table->date('warranty_expires_at')->nullable();

            // OEM vs aftermarket
            $table->enum('part_type', ['oem', 'aftermarket', 'rebuilt', 'used'])->default('oem');

            // Core charge tracking (for batteries, alternators, etc.)
            $table->boolean('has_core_charge')->default(false);
            $table->decimal('core_charge_amount', 10, 2)->nullable();
            $table->boolean('core_returned')->default(false);
            $table->date('core_returned_date')->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('maintenance_record_id');
            $table->index('part_number');
            $table->index('vendor_company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_parts');
    }
};
