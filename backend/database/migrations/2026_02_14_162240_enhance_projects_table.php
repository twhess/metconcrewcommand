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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_type', 100)->nullable()->after('status');
            $table->foreignId('specification_template_id')->nullable()->after('project_type')->constrained('specification_templates')->onDelete('set null');
            $table->timestamp('completed_at')->nullable()->after('updated_by');

            $table->index('project_type');
            $table->index('specification_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['specification_template_id']);
            $table->dropIndex(['project_type']);
            $table->dropIndex(['specification_template_id']);
            $table->dropColumn(['project_type', 'specification_template_id', 'completed_at']);
        });
    }
};
