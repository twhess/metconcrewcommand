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
        Schema::table('contacts', function (Blueprint $table) {
            // Drop old single phone and role columns
            $table->dropColumn(['phone', 'role']);

            // Add multiple phone fields and preferred contact method
            $table->string('phone_mobile', 20)->nullable()->after('email');
            $table->string('phone_work', 20)->nullable()->after('phone_mobile');
            $table->string('phone_other', 20)->nullable()->after('phone_work');
            $table->string('preferred_contact_method')->nullable()->after('phone_other');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['phone_mobile', 'phone_work', 'phone_other', 'preferred_contact_method']);
            $table->string('phone')->after('email');
            $table->string('role')->nullable()->after('phone');
        });
    }
};
