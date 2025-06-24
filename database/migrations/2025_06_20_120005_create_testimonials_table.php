<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Testimonial;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('location');
            $table->integer('rating')->default(5);
            $table->text('comment');
            $table->string('user_type')->default('care_seeker'); // care_seeker, caregiver
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // Insert sample testimonials
        // Testimonial::insert([
        //     [
        //         'name' => 'Sarah Johnson',
        //         'location' => 'Seattle, WA',
        //         'rating' => 5,
        //         'comment' => 'Hometown Hands helped me find the perfect caregiver for my mother. The process was smooth and the caregiver is wonderful.',
        //         'user_type' => 'care_seeker',
        //         'featured' => true,
        //         'active' => true,
        //         'created_at' => now(),
        //         'updated_at' => now()
        //     ],
        //     [
        //         'name' => 'Michael Chen',
        //         'location' => 'Portland, OR',
        //         'rating' => 5,
        //         'comment' => 'As a caregiver, I love working with Hometown Hands. They connect me with families who truly need my help.',
        //         'user_type' => 'caregiver',
        //         'featured' => true,
        //         'active' => true,
        //         'created_at' => now(),
        //         'updated_at' => now()
        //     ],
        //     [
        //         'name' => 'Emily Rodriguez',
        //         'location' => 'San Francisco, CA',
        //         'rating' => 5,
        //         'comment' => 'The peace of mind knowing my father is in good hands is priceless. Thank you Hometown Hands!',
        //         'user_type' => 'care_seeker',
        //         'featured' => true,
        //         'active' => true,
        //         'created_at' => now(),
        //         'updated_at' => now()
        //     ],
        //     [
        //         'name' => 'David Wilson',
        //         'location' => 'Los Angeles, CA',
        //         'rating' => 5,
        //         'comment' => 'The platform made it easy to find qualified caregivers in my area. Highly recommend!',
        //         'user_type' => 'care_seeker',
        //         'featured' => false,
        //         'active' => true,
        //         'created_at' => now(),
        //         'updated_at' => now()
        //     ]
        // ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};

