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
        // Check if caregiver_profiles table exists, if not create it
        if (!Schema::hasTable('caregiver_profiles')) {
            // Add status management fields to caregiver_profiles
        Schema::table('caregiver_profiles', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending')->after('verification_status');
            $table->text('admin_notes')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('admin_notes');
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->onDelete('set null');

            // Add indexes for better performance
            $table->index('status');
            $table->index('verification_status');
            $table->index(['status', 'verification_status']);
        });
        }

        

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caregiver_profiles', function (Blueprint $table) {
            $table->dropIndex(['caregiver_profiles_status_index']);
            $table->dropIndex(['caregiver_profiles_verification_status_index']);
            $table->dropIndex(['caregiver_profiles_status_verification_status_index']);
            
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'status',
                'verification_status', 
                'admin_notes',
                'approved_at',
                'approved_by'
            ]);
        });
    }
};

