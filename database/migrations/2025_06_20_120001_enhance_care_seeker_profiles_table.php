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
        Schema::table('care_seeker_profiles', function (Blueprint $table) {
            // Add Google Places API related fields
            $table->string('place_id')->nullable()->after('location');
            $table->decimal('latitude', 10, 8)->nullable()->after('place_id');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('formatted_address')->nullable()->after('longitude');
            
            // Add additional profile fields for enhanced functionality
            $table->text('care_description')->nullable()->after('care_needs');
            $table->json('preferred_schedule')->nullable()->after('care_description');
            $table->decimal('max_budget', 8, 2)->nullable()->after('budget');
            $table->json('special_requirements')->nullable()->after('max_budget');
            $table->string('emergency_contact_name')->nullable()->after('special_requirements');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->boolean('active')->default(true)->after('emergency_contact_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('care_seeker_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'place_id',
                'latitude',
                'longitude',
                'formatted_address',
                'care_description',
                'preferred_schedule',
                'max_budget',
                'special_requirements',
                'emergency_contact_name',
                'emergency_contact_phone',
                'active'
            ]);
        });
    }
};

