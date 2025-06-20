<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Models\CaregiverProfile;
use App\Models\CareSeekerProfile;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicController extends Controller
{
    public function getStats()
    {
        return Cache::remember('public_stats', 300, function () {
            return response()->json([
                'caregivers' => CaregiverProfile::where('active', true)->count(),
                'families' => CareSeekerProfile::where('active', true)->count(),
                'bookings' => Booking::where('status', 'completed')->count(),
                'rating' => 4.9
            ]);
        });
    }

    public function getTestimonials(Request $request)
    {
        $query = Testimonial::active();

        if ($request->has('featured') && $request->featured === 'true') {
            $query->featured();
        }

        if ($request->has('userType') && $request->userType) {
            $query->byUserType($request->userType);
        }

        $testimonials = $query->orderBy('featured', 'desc')
                             ->orderBy('created_at', 'desc')
                             ->limit($request->get('limit', 10))
                             ->get();

        return response()->json($testimonials);
    }

    public function searchCaregivers(Request $request)
    {
        $query = CaregiverProfile::with('user')
                                ->where('active', true)
                                ->where('verified', true);

        // Location-based search
        if ($request->has('location') && $request->location) {
            $location = $request->location;
            $query->where(function ($q) use ($location) {
                $q->where('location', 'LIKE', '%' . $location . '%')
                  ->orWhere('formatted_address', 'LIKE', '%' . $location . '%');
            });
        }

        // Care type search
        if ($request->has('careType') && $request->careType) {
            $careType = $request->careType;
            $query->where(function ($q) use ($careType) {
                $q->whereJsonContains('specialties', $careType)
                  ->orWhereJsonContains('services_offered', $careType);
            });
        }

        // Radius search (if coordinates provided)
        if ($request->has('latitude') && $request->has('longitude') && $request->has('radius')) {
            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = $request->radius; // in miles

            $query->whereRaw("
                (3959 * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )) <= ?
            ", [$lat, $lng, $lat, $radius]);
        }

        // Sorting
        $sortBy = $request->get('sortBy', 'rating');
        $sortOrder = $request->get('sortOrder', 'desc');
        
        if (in_array($sortBy, ['rating', 'hourly_rate', 'review_count'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $caregivers = $query->limit($request->get('limit', 20))->get();

        $caregivers->transform(function ($caregiver) {
            return [
                'id' => $caregiver->id,
                'name' => $caregiver->name,
                'hourlyRate' => $caregiver->hourly_rate,
                'experience' => $caregiver->experience,
                'specialties' => $caregiver->specialties,
                'location' => $caregiver->location,
                'bio' => $caregiver->bio,
                'rating' => $caregiver->rating,
                'reviewCount' => $caregiver->review_count,
                'profileImage' => $caregiver->profile_image ?? '/default-avatar.png',
                'verified' => $caregiver->verified
            ];
        });

        return response()->json([
            'caregivers' => $caregivers,
            'total' => $caregivers->count()
        ]);
    }

    public function getServiceTypes()
    {
        return response()->json([
            'serviceTypes' => [
                'personal_care' => 'Personal Care',
                'companionship' => 'Companionship',
                'meal_preparation' => 'Meal Preparation',
                'transportation' => 'Transportation',
                'light_housekeeping' => 'Light Housekeeping',
                'medication_management' => 'Medication Management',
                'dementia_care' => 'Dementia & Memory Care',
                'respite_care' => 'Respite Care'
            ]
        ]);
    }
}

