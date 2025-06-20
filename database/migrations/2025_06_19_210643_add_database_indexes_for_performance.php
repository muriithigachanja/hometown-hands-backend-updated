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
        // Add indexes for better query performance
        
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
            $table->index('role');
            $table->index('status');
            $table->index(['role', 'status']);
        });

        // Caregiver profiles indexes
        Schema::table('caregiver_profiles', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('location');
            $table->index('verification_status');
            $table->index('rating');
            $table->index(['location', 'verification_status']);
            $table->index(['rating', 'verification_status']);
        });

        // Care seeker profiles indexes
        Schema::table('care_seeker_profiles', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('location');
        });

        // Care requests indexes
        Schema::table('care_requests', function (Blueprint $table) {
            $table->index('care_seeker_id');
            $table->index('status');
            $table->index('location');
            $table->index('service_type');
            $table->index(['status', 'created_at']);
            $table->index(['location', 'status']);
        });

        // Messages indexes
        Schema::table('messages', function (Blueprint $table) {
            $table->index('sender_id');
            $table->index('receiver_id');
            $table->index('read');
            $table->index(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'read']);
            $table->index('created_at');
        });

        // Bookings indexes
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('care_seeker_id');
            $table->index('caregiver_id');
            $table->index('status');
            $table->index('date');
            $table->index(['date', 'status']);
            $table->index(['caregiver_id', 'status']);
            $table->index(['care_seeker_id', 'status']);
        });

        // Reviews indexes
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('reviewer_id');
            $table->index('reviewed_user_id');
            $table->index('booking_id');
            $table->index('rating');
            $table->index(['reviewed_user_id', 'rating']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropIndex(['role', 'status']);
        });

        Schema::table('caregiver_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['location']);
            $table->dropIndex(['verification_status']);
            $table->dropIndex(['rating']);
            $table->dropIndex(['location', 'verification_status']);
            $table->dropIndex(['rating', 'verification_status']);
        });

        Schema::table('care_seeker_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['location']);
        });

        Schema::table('care_requests', function (Blueprint $table) {
            $table->dropIndex(['care_seeker_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['location']);
            $table->dropIndex(['service_type']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['location', 'status']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['receiver_id']);
            $table->dropIndex(['read']);
            $table->dropIndex(['sender_id', 'receiver_id']);
            $table->dropIndex(['receiver_id', 'read']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['care_seeker_id']);
            $table->dropIndex(['caregiver_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['date']);
            $table->dropIndex(['date', 'status']);
            $table->dropIndex(['caregiver_id', 'status']);
            $table->dropIndex(['care_seeker_id', 'status']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['reviewer_id']);
            $table->dropIndex(['reviewed_user_id']);
            $table->dropIndex(['booking_id']);
            $table->dropIndex(['rating']);
            $table->dropIndex(['reviewed_user_id', 'rating']);
            $table->dropIndex(['created_at']);
        });
    }
};

