<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CaregiverProfile;
use App\Models\CareSeekerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string',
            'password' => 'required|string|min:6',
            'userType' => 'required|in:caregiver,care_seeker'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::create([
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'user_type' => $request->userType,
            'email_verified_at' => now() // Auto-verify for demo purposes
        ]);

        // Create token for immediate login
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
                'userType' => $user->user_type,
                'phone' => $user->phone,
                'createdAt' => $user->created_at
            ],
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Update last login
        $user->updateLastLogin();

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
                'userType' => $user->user_type,
                'role' => $user->role ?? 'user',
                'phone' => $user->phone,
                'createdAt' => $user->created_at
            ],
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
                'userType' => $user->user_type,
                'phone' => $user->phone,
                'createdAt' => $user->created_at
            ]
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'firstName' => 'sometimes|string|max:255',
            'lastName' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string',
            'password' => 'sometimes|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $updateData = [];
        if ($request->has('firstName')) $updateData['first_name'] = $request->firstName;
        if ($request->has('lastName')) $updateData['last_name'] = $request->lastName;
        if ($request->has('phone')) $updateData['phone'] = $request->phone;
        if ($request->has('password')) $updateData['password'] = Hash::make($request->password);

        $user->update($updateData);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
                'userType' => $user->user_type,
                'phone' => $user->phone,
                'createdAt' => $user->created_at
            ]
        ], 200);
    }

    public function createCaregiverProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hourlyRate' => 'required|numeric|min:0',
            'experience' => 'required|string',
            'specialties' => 'required|array',
            'availability' => 'required|string',
            'location' => 'required|string',
            'bio' => 'sometimes|string'
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
            'hourly_rate' => $request->hourlyRate,
            'experience' => $request->experience,
            'specialties' => json_encode($request->specialties),
            'availability' => $request->availability,
            'location' => $request->location,
            'bio' => $request->bio,
            'verified' => false,
            'rating' => 0,
            'review_count' => 0
        ]);

        return response()->json([
            'message' => 'Caregiver profile created successfully',
            'profile' => $profile
        ], 201);
    }

    public function createCareSeekerProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'careNeeds' => 'required|array',
            'location' => 'required|string',
            'budget' => 'sometimes|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = $request->user();

        // Check if profile already exists
        if (CareSeekerProfile::where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Care seeker profile already exists'], 400);
        }

        $profile = CareSeekerProfile::create([
            'user_id' => $user->id,
            'care_needs' => json_encode($request->careNeeds),
            'location' => $request->location,
            'budget' => $request->budget ?? null,
            'preferences' => json_encode($request->preferences ?? [])
        ]);

        return response()->json([
            'message' => 'Care seeker profile created successfully',
            'profile' => $profile
        ], 201);
    }

    public function getUser($id)
    {
        $user = User::with(['caregiverProfile', 'careSeekerProfile'])->find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'email' => $user->email,
                'userType' => $user->user_type,
                'phone' => $user->phone,
                'createdAt' => $user->created_at,
                'caregiverProfile' => $user->caregiverProfile,
                'careSeekerProfile' => $user->careSeekerProfile
            ]
        ], 200);
    }
}


    
    /**
     * Create admin user if it doesn't exist
     */
    public function createAdminUser()
    {
        $adminEmail = 'admin@hometownhands.com';
        
        // Check if admin user already exists
        $existingAdmin = User::where('email', $adminEmail)->first();
        
        if ($existingAdmin) {
            return response()->json([
                'message' => 'Admin user already exists',
                'admin' => [
                    'email' => $existingAdmin->email,
                    'role' => $existingAdmin->role
                ]
            ], 200);
        }
        
        // Create admin user
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $adminEmail,
            'phone' => '+1234567890',
            'password' => Hash::make('admin123'),
            'user_type' => 'admin',
            'role' => 'admin',
            'email_verified_at' => now()
        ]);
        
        return response()->json([
            'message' => 'Admin user created successfully',
            'admin' => [
                'id' => $admin->id,
                'email' => $admin->email,
                'role' => $admin->role
            ]
        ], 201);
    }
}

