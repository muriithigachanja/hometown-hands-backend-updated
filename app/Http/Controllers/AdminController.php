<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\CaregiverProfile;
use App\Models\CareSeekerProfile;
use App\Models\Booking;
use App\Models\Review;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    // Dashboard Overview
    public function dashboard()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'total_caregivers' => CaregiverProfile::count(),
                'total_care_seekers' => CareSeekerProfile::count(),
                'pending_caregiver_approvals' => CaregiverProfile::where('verification_status', 'pending')->count(),
                'active_bookings' => Booking::where('status', 'confirmed')->count(),
                'total_bookings' => Booking::count(),
                'total_revenue' => Booking::where('payment_status', 'completed')->sum('total_amount'),
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
            ];

            $recent_activities = [
                'recent_users' => User::with(['caregiverProfile', 'careSeekerProfile'])
                    ->latest()
                    ->take(5)
                    ->get(),
                'recent_bookings' => Booking::with(['caregiver.user', 'careSeeker.user'])
                    ->latest()
                    ->take(5)
                    ->get(),
                'pending_approvals' => CaregiverProfile::with('user')
                    ->where('verification_status', 'pending')
                    ->latest()
                    ->take(5)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'activities' => $recent_activities
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // User Management
    public function getUsers(Request $request)
    {
        try {
            $query = User::with(['caregiverProfile', 'careSeekerProfile']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Filter by role
            if ($request->has('role') && !empty($request->role)) {
                $query->where('role', $request->role);
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserDetails($id)
    {
        try {
            $user = User::with([
                'caregiverProfile.reviews',
                'careSeekerProfile.careRequests',
                'sentMessages',
                'receivedMessages'
            ])->findOrFail($id);

            // Get user's booking history
            if ($user->role === 'caregiver') {
                $bookings = Booking::with('careSeeker.user')
                    ->where('caregiver_id', $user->caregiverProfile->id ?? null)
                    ->latest()
                    ->get();
            } else {
                $bookings = Booking::with('caregiver.user')
                    ->where('care_seeker_id', $user->careSeekerProfile->id ?? null)
                    ->latest()
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'bookings' => $bookings
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function updateUser(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'phone' => 'sometimes|string|max:20',
                'role' => 'sometimes|in:admin,caregiver,care_seeker',
                'status' => 'sometimes|in:active,inactive,suspended',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::findOrFail($id);
            $user->update($request->only(['name', 'email', 'phone', 'role', 'status']));

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deletion of current admin
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Caregiver Management
    public function getCaregivers(Request $request)
    {
        try {
            $query = CaregiverProfile::with(['user', 'reviews']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filter by verification status
            if ($request->has('verification_status') && !empty($request->verification_status)) {
                $query->where('verification_status', $request->verification_status);
            }

            // Filter by specialties
            if ($request->has('specialty') && !empty($request->specialty)) {
                $query->where('specialties', 'like', "%{$request->specialty}%");
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $caregivers = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $caregivers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch caregivers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approveCaregiverProfile(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'verification_status' => 'required|in:approved,rejected',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $caregiver = CaregiverProfile::findOrFail($id);
            $caregiver->update([
                'verification_status' => $request->verification_status,
                'admin_notes' => $request->admin_notes,
                'verified_at' => $request->verification_status === 'approved' ? now() : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Caregiver profile updated successfully',
                'data' => $caregiver
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update caregiver profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Booking Management
    public function getBookings(Request $request)
    {
        try {
            $query = Booking::with(['caregiver.user', 'careSeeker.user']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('caregiver.user', function($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    })->orWhereHas('careSeeker.user', function($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });
                });
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->where('booking_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->where('booking_date', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $bookings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $bookings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bookings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateBookingStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $booking = Booking::findOrFail($id);
            $booking->update([
                'status' => $request->status,
                'admin_notes' => $request->admin_notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking status updated successfully',
                'data' => $booking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update booking status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Analytics and Reports
    public function getAnalytics(Request $request)
    {
        try {
            $period = $request->get('period', '30'); // days
            $startDate = now()->subDays($period);

            $analytics = [
                'user_growth' => [
                    'total' => User::where('created_at', '>=', $startDate)->count(),
                    'caregivers' => User::where('role', 'caregiver')
                        ->where('created_at', '>=', $startDate)->count(),
                    'care_seekers' => User::where('role', 'care_seeker')
                        ->where('created_at', '>=', $startDate)->count(),
                ],
                'booking_stats' => [
                    'total_bookings' => Booking::where('created_at', '>=', $startDate)->count(),
                    'completed_bookings' => Booking::where('status', 'completed')
                        ->where('created_at', '>=', $startDate)->count(),
                    'cancelled_bookings' => Booking::where('status', 'cancelled')
                        ->where('created_at', '>=', $startDate)->count(),
                    'total_revenue' => Booking::where('payment_status', 'completed')
                        ->where('created_at', '>=', $startDate)->sum('total_amount'),
                ],
                'top_caregivers' => CaregiverProfile::with('user')
                    ->withCount('bookings')
                    ->orderBy('bookings_count', 'desc')
                    ->take(10)
                    ->get(),
                'service_popularity' => Booking::selectRaw('service_type, COUNT(*) as count')
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('service_type')
                    ->orderBy('count', 'desc')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // System Settings
    public function getSettings()
    {
        try {
            // This would typically fetch from a settings table
            $settings = [
                'platform_commission' => 10, // percentage
                'minimum_booking_duration' => 2, // hours
                'maximum_booking_duration' => 12, // hours
                'auto_approve_caregivers' => false,
                'email_notifications_enabled' => true,
                'sms_notifications_enabled' => false,
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform_commission' => 'sometimes|numeric|min:0|max:50',
                'minimum_booking_duration' => 'sometimes|integer|min:1|max:24',
                'maximum_booking_duration' => 'sometimes|integer|min:1|max:24',
                'auto_approve_caregivers' => 'sometimes|boolean',
                'email_notifications_enabled' => 'sometimes|boolean',
                'sms_notifications_enabled' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // In a real application, you would save these to a settings table
            // For now, we'll just return success
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $request->all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

