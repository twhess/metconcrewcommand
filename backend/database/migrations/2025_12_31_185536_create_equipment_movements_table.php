<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->string('from_location_type')->nullable();
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->string('to_location_type');
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->dateTime('moved_at');
            $table->foreignId('moved_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('equipment_id');
            $table->index('moved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_movements');
    }
};
