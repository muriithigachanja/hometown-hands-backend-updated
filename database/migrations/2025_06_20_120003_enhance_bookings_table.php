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
        Schema::table('bookings', function (Blueprint $table) {
            // Add Google Places API related fields for booking location
            $table->string("place_id")->nullable()->after("total_amount");
            $table->decimal("latitude", 10, 8)->nullable()->after("place_id");
            $table->decimal("longitude", 11, 8)->nullable()->after("latitude");
            $table->string("formatted_address")->nullable()->after("longitude");
            
            // Add payment and booking enhancement fields
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('total_amount');
            $table->integer('duration_hours')->nullable()->after('hourly_rate');
            $table->json('care_requirements')->nullable()->after('duration_hours');
            $table->text('special_instructions')->nullable()->after('care_requirements');
            $table->string('payment_status')->default('pending')->after('special_instructions');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('payment_transaction_id')->nullable()->after('payment_method');
            $table->timestamp('confirmed_at')->nullable()->after('payment_transaction_id');
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'place_id',
                'latitude',
                'longitude',
                'formatted_address',
                'hourly_rate',
                'duration_hours',
                'care_requirements',
                'special_instructions',
                'payment_status',
                'payment_method',
                'payment_transaction_id',
                'confirmed_at',
                'completed_at',
                'cancelled_at',
                'cancellation_reason'
            ]);
        });
    }
};

