<?php

namespace App\Http\Controllers;

use App\Models\CaregiverProfile;
use App\Models\CareRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class CaregiverController extends Controller
{
    public function getCaregivers(Request $request)
    {
        $query = CaregiverProfile::with('user');

        // Apply filters
        if ($request->has('location') && $request->location) {
            $query->where('location', 'LIKE', '%' . $request->location . '%');
        }

        if ($request->has('careType') && $request->careType) {
            $query->where('specialties', 'LIKE', '%' . $request->careType . '%');
        }

        if ($request->has('minRate') && $request->minRate) {
            $query->where('hourly_rate', '>=', $request->minRate);
        }

        if ($request->has('maxRate') && $request->maxRate) {
            $query->where('hourly_rate', '<=', $request->maxRate);
        }

        if ($request->has('verified') && $request->verified === 'true') {
            $query->where('verified', true);
        }

        if ($request->has('minRating') && $request->minRating) {
            $query->where('rating', '>=', $request->minRating);
        }

        // Sorting
        $sortBy = $request->get('sortBy', 'rating');
        $sortOrder = $request->get('sortOrder', 'desc');
        
        if (in_array($sortBy, ['rating', 'hourly_rate', 'review_count', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->get('perPage', 10), 50); // Max 50 per page
        $caregivers = $query->paginate($perPage);

        // Transform the data
        $caregivers->getCollection()->transform(function ($caregiver) {
            return [
                'id' => $caregiver->id,
                'userId' => $caregiver->user_id,
                'name' => $caregiver->user->first_name . ' ' . $caregiver->user->last_name,
                'hourlyRate' => $caregiver->hourly_rate,
                'experience' => $caregiver->experience,
                'specialties' => json_decode($caregiver->specialties, true),
                'availability' => $caregiver->availability,
                'location' => $caregiver->location,
                'bio' => $caregiver->bio,
                'verified' => $caregiver->verified,
                'rating' => $caregiver->rating,
                'reviewCount' => $caregiver->review_count,
                'profileImage' => $caregiver->profile_image ?? '/default-avatar.png'
            ];
        });

        return response()->json($caregivers, 200);
    }

    public function getCaregiver($id)
    {
        $caregiver = CaregiverProfile::with(['user', 'reviews.careSeekerUser'])->find($id);

        if (!$caregiver) {
            return response()->json(['error' => 'Caregiver not found'], 404);
        }

        return response()->json([
            'id' => $caregiver->id,
            'userId' => $caregiver->user_id,
            'name' => $caregiver->user->first_name . ' ' . $caregiver->user->last_name,
            'email' => $caregiver->user->email,
            'phone' => $caregiver->user->phone,
            'hourlyRate' => $caregiver->hourly_rate,
            'experience' => $caregiver->experience,
            'specialties' => json_decode($caregiver->specialties, true),
            'availability' => $caregiver->availability,
            'location' => $caregiver->location,
            'bio' => $caregiver->bio,
            'verified' => $caregiver->verified,
            'rating' => $caregiver->rating,
            'reviewCount' => $caregiver->review_count,
            'profileImage' => $caregiver->profile_image ?? '/default-avatar.png',
            'reviews' => $caregiver->reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'reviewerName' => $review->careSeekerUser->first_name . ' ' . $review->careSeekerUser->last_name,
                    'createdAt' => $review->created_at
                ];
            })
        ], 200);
    }

    public function getCareRequests(Request $request)
    {
        $query = CareRequest::with(['careSeeker.user']);

        // Apply filters for caregivers
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('careType') && $request->careType) {
            $query->where('care_type', $request->careType);
        }

        if ($request->has('location') && $request->location) {
            $query->where('location', 'LIKE', '%' . $request->location . '%');
        }

        // For authenticated caregivers, show relevant requests
        if ($request->user() && $request->user()->user_type === 'caregiver') {
            $caregiverProfile = $request->user()->caregiverProfile;
            if ($caregiverProfile) {
                // Filter by caregiver's location and specialties
                $query->where('location', 'LIKE', '%' . $caregiverProfile->location . '%');
            }
        }

        $careRequests = $query->orderBy('created_at', 'desc')->paginate(10);

        $careRequests->getCollection()->transform(function ($request) {
            return [
                'id' => $request->id,
                'careSeekerId' => $request->care_seeker_id,
                'careType' => $request->care_type,
                'description' => $request->description,
                'location' => $request->location,
                'startDate' => $request->start_date,
                'endDate' => $request->end_date,
                'hourlyRate' => $request->hourly_rate,
                'status' => $request->status,
                'urgency' => $request->urgency,
                'careSeekerName' => $request->careSeeker->user->first_name . ' ' . $request->careSeeker->user->last_name,
                'createdAt' => $request->created_at
            ];
        });

        return response()->json($careRequests, 200);
    }

    public function createCaregiverProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hourlyRate' => 'required|numeric|min:0',
            'experience' => 'required|string',
            'specialties' => 'required|array',
            'availability' => 'required|string',
            'location' => 'required|string',
            'bio' => 'sometimes|string',
            'placeId' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'formattedAddress' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = $request->user();

        // Check if profile already exists
        if (CaregiverProfile::where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Caregiver profile already exists'], 400);
        }

        $profile = CaregiverProfile::create([
            'user_id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'hourly_rate' => $request->hourlyRate,
            'experience' => $request->experience,
            'specialties' => json_encode($request->specialties),
            'availability' => $request->availability,
            'location' => $request->location,
            'place_id' => $request->placeId,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'formatted_address' => $request->formattedAddress,
            'bio' => $request->bio,
            'verified' => false,
            'rating' => 0,
            'review_count' => 0,
            'active' => true
        ]);

        return response()->json([
            'message' => 'Caregiver profile created successfully',
            'profile' => $profile
        ], 201);
    }

    public function createCareRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'careType' => 'required|string',
            'description' => 'required|string',
            'location' => 'required|string',
            'startDate' => 'required|date',
            'endDate' => 'sometimes|date|after:startDate',
            'hourlyRate' => 'sometimes|numeric|min:0',
            'urgency' => 'sometimes|in:low,medium,high'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = $request->user();
        
        if (!$user || $user->user_type !== 'care_seeker') {
            return response()->json(['error' => 'Only care seekers can create care requests'], 403);
        }

        $careSeekerProfile = $user->careSeekerProfile;
        if (!$careSeekerProfile) {
            return response()->json(['error' => 'Care seeker profile required'], 400);
        }

        $careRequest = CareRequest::create([
            'care_seeker_id' => $careSeekerProfile->id,
            'care_type' => $request->careType,
            'description' => $request->description,
            'location' => $request->location,
            'start_date' => $request->startDate,
            'end_date' => $request->endDate,
            'hourly_rate' => $request->hourlyRate,
            'urgency' => $request->urgency ?? 'medium',
            'status' => 'open'
        ]);

        return response()->json([
            'message' => 'Care request created successfully',
            'careRequest' => $careRequest
        ], 201);
    }

    public function updateCareRequest(Request $request, $id)
    {
        $careRequest = CareRequest::find($id);

        if (!$careRequest) {
            return response()->json(['error' => 'Care request not found'], 404);
        }

        $user = $request->user();
        if (!$user || $careRequest->careSeeker->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'careType' => 'sometimes|string',
            'description' => 'sometimes|string',
            'location' => 'sometimes|string',
            'startDate' => 'sometimes|date',
            'endDate' => 'sometimes|date|after:startDate',
            'hourlyRate' => 'sometimes|numeric|min:0',
            'urgency' => 'sometimes|in:low,medium,high',
            'status' => 'sometimes|in:open,in_progress,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $updateData = [];
        if ($request->has('careType')) $updateData['care_type'] = $request->careType;
        if ($request->has('description')) $updateData['description'] = $request->description;
        if ($request->has('location')) $updateData['location'] = $request->location;
        if ($request->has('startDate')) $updateData['start_date'] = $request->startDate;
        if ($request->has('endDate')) $updateData['end_date'] = $request->endDate;
        if ($request->has('hourlyRate')) $updateData['hourly_rate'] = $request->hourlyRate;
        if ($request->has('urgency')) $updateData['urgency'] = $request->urgency;
        if ($request->has('status')) $updateData['status'] = $request->status;

        $careRequest->update($updateData);

        return response()->json([
            'message' => 'Care request updated successfully',
            'careRequest' => $careRequest
        ], 200);
    }
}

