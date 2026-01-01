<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('equipment_number')->unique()->nullable();
            $table->string('type');
            $table->string('category')->nullable();
            $table->string('status')->default('active');
            $table->string('current_location_type')->nullable();
            $table->unsignedBigInteger('current_location_id')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->index('status');
            $table->index(['current_location_type', 'current_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
