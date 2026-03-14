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
        Schema::create('specification_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specification_template_id')->constrained('specification_templates')->onDelete('cascade');
            $table->string('category', 50);
            $table->string('requirement_name');
            $table->text('requirement_description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->json('default_value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('specification_template_id');
            $table->index('category');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specification_template_items');
    }
};
