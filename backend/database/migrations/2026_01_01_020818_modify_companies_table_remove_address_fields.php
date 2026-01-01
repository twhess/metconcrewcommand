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
        Schema::table('companies', function (Blueprint $table) {
            // Remove address fields - they'll move to company_locations
            $table->dropColumn([
                'address_line1',
                'address_line2',
                'city',
                'state',
                'zip'
            ]);

            // Rename phone/email to main_phone/main_email for clarity
            $table->renameColumn('phone', 'main_phone');
            $table->renameColumn('email', 'main_email');

            // Add new fields
            $table->string('website')->nullable()->after('main_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Restore address fields
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 10)->nullable();

            // Rename back
            $table->renameColumn('main_phone', 'phone');
            $table->renameColumn('main_email', 'email');

            // Remove website
            $table->dropColumn('website');
        });
    }
};
