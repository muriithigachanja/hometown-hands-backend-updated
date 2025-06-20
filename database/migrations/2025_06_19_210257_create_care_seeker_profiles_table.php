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
        Schema::create('care_seeker_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('recipient_name');
            $table->integer('recipient_age')->nullable();
            $table->text('recipient_condition')->nullable();
            $table->json('care_needs')->nullable();
            $table->text('schedule')->nullable();
            $table->string('location')->nullable();
            $table->string('budget')->nullable();
            $table->json('preferences')->nullable();
            $table->text('additional_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_seeker_profiles');
    }
};

