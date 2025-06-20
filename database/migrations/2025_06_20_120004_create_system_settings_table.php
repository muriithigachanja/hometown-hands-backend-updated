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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string'); // string, number, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('system_settings')->insert([
            [
                'key' => 'platform_commission_rate',
                'value' => '0.15',
                'type' => 'number',
                'description' => 'Platform commission rate (15%)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'minimum_booking_hours',
                'value' => '2',
                'type' => 'number',
                'description' => 'Minimum booking duration in hours',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'maximum_booking_hours',
                'value' => '12',
                'type' => 'number',
                'description' => 'Maximum booking duration in hours',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'auto_approve_caregivers',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Automatically approve caregiver profiles',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'require_background_check',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Require background check for caregivers',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

