<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\CaregiverProfile;
use App\Models\CareSeekerProfile;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Testimonial;
use App\Models\SystemSetting;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create Admin User (only if doesn't exist)
        $admin = User::where('email', 'admin@hometownhands.com')->first();
        if (!$admin) {
            $admin = User::create([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@hometownhands.com',
                'password' => Hash::make('admin123'),
                'phone' => '+1-555-0100',
                'user_type' => 'admin',
                'role' => 'admin',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        }

        // Create Caregivers
        $caregivers = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@example.com',
                'phone' => '+1-555-0101',
                'bio' => 'Experienced caregiver with 8 years in elderly care. Specializes in dementia and Alzheimer\'s care.',
                'hourly_rate' => 25.00,
                'experience_years' => 8,
                'services_offered' => json_encode(['Dementia Care', 'Personal Care', 'Medication Management']),
                'certifications' => json_encode(['CNA', 'CPR', 'First Aid']),
                'availability' => json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
                'location' => 'Seattle, WA',
                'verification_status' => 'approved',
                'verified_at' => now(),
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Chen',
                'email' => 'michael.chen@example.com',
                'phone' => '+1-555-0102',
                'bio' => 'Compassionate caregiver with expertise in post-surgery recovery and physical therapy assistance.',
                'hourly_rate' => 28.00,
                'experience_years' => 6,
                'services_offered' => json_encode(['Physical Therapy', 'Post-Surgery Care', 'Mobility Assistance']),
                'certifications' => json_encode(['PTA', 'CPR', 'First Aid']),
                'availability' => json_encode(['Monday', 'Wednesday', 'Friday', 'Saturday', 'Sunday']),
                'location' => 'Portland, OR',
                'verification_status' => 'approved',
                'verified_at' => now(),
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Rodriguez',
                'email' => 'emily.rodriguez@example.com',
                'phone' => '+1-555-0103',
                'bio' => 'Bilingual caregiver specializing in companionship and meal preparation for seniors.',
                'hourly_rate' => 22.00,
                'experience_years' => 4,
                'services_offered' => json_encode(['Companionship', 'Meal Preparation', 'Light Housekeeping']),
                'certifications' => json_encode(['CPR', 'First Aid', 'Food Safety']),
                'availability' => json_encode(['Tuesday', 'Thursday', 'Saturday', 'Sunday']),
                'location' => 'San Francisco, CA',
                'verification_status' => 'approved',
                'verified_at' => now(),
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Thompson',
                'email' => 'david.thompson@example.com',
                'phone' => '+1-555-0104',
                'bio' => 'Male caregiver with experience in transportation and outdoor activities for active seniors.',
                'hourly_rate' => 26.00,
                'experience_years' => 5,
                'services_offered' => json_encode(['Transportation', 'Outdoor Activities', 'Exercise Assistance']),
                'certifications' => json_encode(['CPR', 'First Aid', 'Defensive Driving']),
                'availability' => json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
                'location' => 'Denver, CO',
                'verification_status' => 'pending',
                'verified_at' => null,
            ],
            [
                'first_name' => 'Lisa',
                'last_name' => 'Wang',
                'email' => 'lisa.wang@example.com',
                'phone' => '+1-555-0105',
                'bio' => 'Registered nurse providing skilled nursing care and medical support for complex conditions.',
                'hourly_rate' => 35.00,
                'experience_years' => 12,
                'services_offered' => json_encode(['Skilled Nursing', 'Wound Care', 'Medication Administration']),
                'certifications' => json_encode(['RN', 'CPR', 'BLS', 'Wound Care Specialist']),
                'availability' => json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']),
                'location' => 'Austin, TX',
                'verification_status' => 'approved',
                'verified_at' => now(),
            ],
        ];

        foreach ($caregivers as $caregiverData) {
            $user = User::create([
                'first_name' => $caregiverData['first_name'],
                'last_name' => $caregiverData['last_name'],
                'email' => $caregiverData['email'],
                'password' => Hash::make('password123'),
                'phone' => $caregiverData['phone'],
                'user_type' => 'caregiver',
                'role' => 'user',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            CaregiverProfile::create([
                'user_id' => $user->id,
                'name' => $caregiverData['first_name'] . ' ' . $caregiverData['last_name'],
                'bio' => $caregiverData['bio'],
                'hourly_rate' => $caregiverData['hourly_rate'],
                'experience' => $caregiverData['experience_years'] . ' years of experience',
                'services_offered' => $caregiverData['services_offered'],
                'certifications' => $caregiverData['certifications'],
                'availability' => $caregiverData['availability'],
                'location' => $caregiverData['location'],
                'verification_status' => $caregiverData['verification_status'] === 'approved' ? 'verified' : $caregiverData['verification_status'],
                'verified' => $caregiverData['verification_status'] === 'approved' ? 1 : 0,
            ]);
        }

        // Create Care Seekers
        $careSeekers = [
            [
                'first_name' => 'Robert',
                'last_name' => 'Miller',
                'email' => 'robert.miller@example.com',
                'phone' => '+1-555-0201',
                'care_needs' => json_encode(['Personal Care', 'Medication Reminders', 'Companionship']),
                'care_schedule' => json_encode(['Monday', 'Wednesday', 'Friday']),
                'location' => 'Seattle, WA',
                'emergency_contact' => json_encode([
                    'name' => 'Jennifer Miller',
                    'relationship' => 'Daughter',
                    'phone' => '+1-555-0301'
                ]),
            ],
            [
                'first_name' => 'Margaret',
                'last_name' => 'Davis',
                'email' => 'margaret.davis@example.com',
                'phone' => '+1-555-0202',
                'care_needs' => json_encode(['Meal Preparation', 'Light Housekeeping', 'Transportation']),
                'care_schedule' => json_encode(['Tuesday', 'Thursday', 'Saturday']),
                'location' => 'Portland, OR',
                'emergency_contact' => json_encode([
                    'name' => 'John Davis',
                    'relationship' => 'Son',
                    'phone' => '+1-555-0302'
                ]),
            ],
            [
                'first_name' => 'William',
                'last_name' => 'Garcia',
                'email' => 'william.garcia@example.com',
                'phone' => '+1-555-0203',
                'care_needs' => json_encode(['Physical Therapy', 'Exercise Assistance', 'Mobility Support']),
                'care_schedule' => json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
                'location' => 'San Francisco, CA',
                'emergency_contact' => json_encode([
                    'name' => 'Maria Garcia',
                    'relationship' => 'Wife',
                    'phone' => '+1-555-0303'
                ]),
            ],
            [
                'first_name' => 'Dorothy',
                'last_name' => 'Wilson',
                'email' => 'dorothy.wilson@example.com',
                'phone' => '+1-555-0204',
                'care_needs' => json_encode(['Companionship', 'Medication Management', 'Doctor Appointments']),
                'care_schedule' => json_encode(['Monday', 'Wednesday', 'Friday', 'Sunday']),
                'location' => 'Denver, CO',
                'emergency_contact' => json_encode([
                    'name' => 'Susan Wilson',
                    'relationship' => 'Daughter',
                    'phone' => '+1-555-0304'
                ]),
            ],
        ];

        foreach ($careSeekers as $careSeekerData) {
            $user = User::create([
                'first_name' => $careSeekerData['first_name'],
                'last_name' => $careSeekerData['last_name'],
                'email' => $careSeekerData['email'],
                'password' => Hash::make('password123'),
                'phone' => $careSeekerData['phone'],
                'user_type' => 'care_seeker',
                'role' => 'user',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            CareSeekerProfile::create([
                'user_id' => $user->id,
                'recipient_name' => $careSeekerData['first_name'] . ' ' . $careSeekerData['last_name'],
                'care_needs' => $careSeekerData['care_needs'],
                'schedule' => $careSeekerData['care_schedule'],
                'location' => $careSeekerData['location'],
                'emergency_contact_name' => json_decode($careSeekerData['emergency_contact'], true)['name'],
                'emergency_contact_phone' => json_decode($careSeekerData['emergency_contact'], true)['phone'],
            ]);
        }

        // Create Bookings
        $caregiverProfiles = CaregiverProfile::all();
        $careSeekerProfiles = CareSeekerProfile::all();

        $bookings = [
            [
                'caregiver_id' => $caregiverProfiles[0]->id,
                'care_seeker_id' => $careSeekerProfiles[0]->id,
                'service_type' => 'Personal Care',
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '13:00:00',
                'hourly_rate' => 25.00,
                'total_amount' => 100.00,
                'status' => 'confirmed',
                'payment_status' => 'pending',
                'special_instructions' => 'Patient has mobility issues, please assist with walking.',
            ],
            [
                'caregiver_id' => $caregiverProfiles[1]->id,
                'care_seeker_id' => $careSeekerProfiles[1]->id,
                'service_type' => 'Meal Preparation',
                'date' => now()->addDays(2),
                'start_time' => '11:00:00',
                'end_time' => '15:00:00',
                'hourly_rate' => 28.00,
                'total_amount' => 112.00,
                'status' => 'confirmed',
                'payment_status' => 'completed',
                'special_instructions' => 'Low sodium diet required.',
            ],
            [
                'caregiver_id' => $caregiverProfiles[2]->id,
                'care_seeker_id' => $careSeekerProfiles[2]->id,
                'service_type' => 'Companionship',
                'date' => now()->subDays(1),
                'start_time' => '14:00:00',
                'end_time' => '18:00:00',
                'hourly_rate' => 22.00,
                    'total_amount' => 88.00,
                'status' => 'completed',
                'payment_status' => 'completed',
                'special_instructions' => 'Enjoys reading and light conversation.',
            ],
            [
                'caregiver_id' => $caregiverProfiles[4]->id,
                'care_seeker_id' => $careSeekerProfiles[3]->id,
                'service_type' => 'Skilled Nursing',
                'date' => now()->addDays(3),
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
                'hourly_rate' => 35.00,
                'total_amount' => 140.00,
                'status' => 'pending',
                'payment_status' => 'pending',
                'special_instructions' => 'Wound care and medication administration required.',
            ],
            [
                'caregiver_id' => $caregiverProfiles[0]->id,
                'care_seeker_id' => $careSeekerProfiles[1]->id,
                'service_type' => 'Transportation',
                'date' => now()->subDays(3),
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'hourly_rate' => 25.00,
                    'total_amount' => 50.00,
                'status' => 'completed',
                'payment_status' => 'completed',
                'special_instructions' => 'Doctor appointment at General Hospital.',
            ],
            [
                'caregiver_id' => $caregiverProfiles[1]->id,
                'care_seeker_id' => $careSeekerProfiles[0]->id,
                'service_type' => 'Physical Therapy',
                'date' => now()->subDays(7),
                'start_time' => '15:00:00',
                'end_time' => '17:00:00',
                'hourly_rate' => 28.00,
                    'total_amount' => 56.00,
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'special_instructions' => 'Cancelled due to caregiver illness.',
            ],
        ];

        foreach ($bookings as $bookingData) {
            Booking::create($bookingData);
        }

        // Create Reviews
        $completedBookings = Booking::where('status', 'completed')->get();
        foreach ($completedBookings as $booking) {
            Review::create([
                'reviewed_user_id' => $booking->caregiver_id,
                'reviewer_id' => $booking->care_seeker_id,
                'booking_id' => $booking->id,
                'rating' => rand(4, 5),
                'comment' => $this->getRandomReviewComment(),
            ]);
        }

        // Create Testimonials
        $testimonials = [
            [
                'name' => 'Sarah Johnson',
                'location' => 'Seattle, WA',
                'rating' => 5,
                'comment' => 'Hometown Hands helped us find the perfect caregiver for my mother. The peace of mind is invaluable.',
                'featured' => true,
                'active' => true,
            ],
            [
                'name' => 'Michael Chen',
                'location' => 'Portland, OR',
                'rating' => 5,
                'comment' => 'Professional, caring, and reliable. Our caregiver has become like family to us.',
                'featured' => true,
                'active' => true,
            ],
            [
                'name' => 'Emily Rodriguez',
                'location' => 'San Francisco, CA',
                'rating' => 5,
                'comment' => 'I love having companionship and help with daily tasks. It allows me to stay independent.',
                'featured' => true,
                'active' => true,
            ],
            [
                'name' => 'Robert Miller',
                'location' => 'Seattle, WA',
                'rating' => 4,
                'comment' => 'Great service and very responsive support team. Highly recommend for anyone needing care services_offered.',
                'featured' => true,
                'active' => true,
            ],
            [
                'name' => 'Margaret Davis',
                'location' => 'Portland, OR',
                'rating' => 5,
                'comment' => 'The caregiver matching process was thorough and we found someone perfect for our needs.',
                'featured' => true,
                'active' => true,
            ],
        ];

        foreach ($testimonials as $testimonialData) {
            Testimonial::create($testimonialData);
        }

        // Create System Settings
        // $settings = [
        //     ['key' => 'site_name', 'value' => 'Hometown Hands', 'type' => 'string'],
        //     ['key' => 'site_description', 'value' => 'Compassionate Adult Care Services', 'type' => 'string'],
        //     ['key' => 'contact_email', 'value' => 'support@hometownhands.com', 'type' => 'string'],
        //     ['key' => 'contact_phone', 'value' => '+1-555-123-4567', 'type' => 'string'],
        //     ['key' => 'service_fee_percentage', 'value' => '10', 'type' => 'number'],
        //     ['key' => 'minimum_booking_hours', 'value' => '2', 'type' => 'number'],
        //     ['key' => 'maximum_booking_hours', 'value' => '12', 'type' => 'number'],
        //     ['key' => 'cancellation_policy_hours', 'value' => '24', 'type' => 'number'],
        //     ['key' => 'auto_approve_caregivers', 'value' => 'false', 'type' => 'boolean'],
        //     ['key' => 'require_background_check', 'value' => 'true', 'type' => 'boolean'],
        //     ['key' => 'enable_notifications', 'value' => 'true', 'type' => 'boolean'],
        //     ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean'],
        // ];

        // foreach ($settings as $setting) {
        //     SystemSetting::create($setting);
        // }
    }

    private function getRandomReviewComment()
    {
        $comments = [
            'Excellent caregiver, very professional and caring.',
            'Great experience, highly recommend this service.',
            'The caregiver was punctual and provided excellent care.',
            'Very satisfied with the quality of care provided.',
            'Compassionate and skilled caregiver, exceeded expectations.',
            'Professional service and great communication throughout.',
            'The caregiver was patient and understanding of our needs.',
            'Reliable and trustworthy, we felt very comfortable.',
        ];

        return $comments[array_rand($comments)];
    }
}

