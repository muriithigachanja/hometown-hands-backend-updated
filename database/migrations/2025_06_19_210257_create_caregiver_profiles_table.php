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
        Schema::create('caregiver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('age')->nullable();
            $table->string('gender')->nullable();
            $table->json('languages')->nullable();
            $table->string('location')->nullable();
            $table->integer('radius')->nullable(); // service radius in miles
            $table->json('services_offered')->nullable();
            $table->json('availability')->nullable();
            $table->json('certifications')->nullable();
            $table->text('experience')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('flat_rate', 8, 2)->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('review_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caregiver_profiles');
    }
};

