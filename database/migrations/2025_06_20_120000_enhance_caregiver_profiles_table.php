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
        Schema::table('caregiver_profiles', function (Blueprint $table) {
            // Add Google Places API related fields
            $table->string('place_id')->nullable()->after('location');
            $table->decimal('latitude', 10, 8)->nullable()->after('place_id');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('formatted_address')->nullable()->after('longitude');
            
            // Add additional profile fields for enhanced functionality
            $table->text('bio')->nullable()->after('experience');
            $table->boolean('background_check')->default(false)->after('verification_status');
            $table->boolean('verified')->default(false)->after('background_check');
            $table->string('profile_image')->nullable()->after('verified');
            $table->json('work_history')->nullable()->after('profile_image');
            $table->json('education')->nullable()->after('work_history');
            $table->boolean('active')->default(true)->after('education');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caregiver_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'place_id',
                'latitude',
                'longitude',
                'formatted_address',
                'bio',
                'background_check',
                'verified',
                'profile_image',
                'work_history',
                'education',
                'active'
            ]);
        });
    }
};

