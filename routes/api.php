<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaregiverController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'message' => 'Hometown Hands Laravel API is running',
        'timestamp' => now()->toISOString(),
        'version' => '2.0.0'
    ]);
});

// Authentication routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/create-admin', [AuthController::class, 'createAdminUser']); // For initial setup
    
    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);        Route::post('/profile/caregiver', [AuthController::class, 'createCaregiverProfile']);
        Route::post('/profile/care-seeker', [AuthController::class, 'createCareSeekerProfile']);
    });
});

// Public caregiver routes (for browsing)
Route::prefix('caregivers')->group(function () {
    Route::get('/', [CaregiverController::class, 'getCaregivers']);
    Route::get('/{id}', [CaregiverController::class, 'getCaregiver']);
});

// Protected caregiver profile creation (from frontend)
Route::prefix('caregivers')->middleware('auth:sanctum')->group(function () {
    Route::post('/profile', [CaregiverController::class, 'createCaregiverProfile']);
});

// Care request routes
Route::prefix('care-requests')->group(function () {
    Route::get('/', [CaregiverController::class, 'getCareRequests']);
    
    // Protected care request routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [CaregiverController::class, 'createCareRequest']);
        Route::put('/{id}', [CaregiverController::class, 'updateCareRequest']);
    });
});

// Protected messaging routes
Route::prefix('messages')->middleware('auth:sanctum')->group(function () {
    Route::get('/conversations', [MessagingController::class, 'getConversations']);
    Route::get('/conversations/{conversationId}', [MessagingController::class, 'getMessages']);
    Route::post('/conversations/{conversationId}', [MessagingController::class, 'sendMessage']);
    Route::post('/conversations', [MessagingController::class, 'createConversation']);
});

// Protected booking routes
Route::prefix('bookings')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [BookingController::class, 'getBookings']);
    Route::post('/', [BookingController::class, 'createBooking']);
    Route::put('/{id}', [BookingController::class, 'updateBooking']);
    Route::post('/{id}/cancel', [BookingController::class, 'cancelBooking']);
    Route::post('/payment', [BookingController::class, 'processPayment']);
});

// Protected review routes
Route::prefix('reviews')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [BookingController::class, 'createReview']);
    Route::get('/caregiver/{caregiverId}', [BookingController::class, 'getCaregiverReviews']);
});

// User routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
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
        ]);
    });
});

// Rate limiting for sensitive endpoints
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/auth/register');
    Route::post('/auth/login');
});

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/messages/conversations/{conversationId}');
    Route::post('/bookings');
});

// Admin routes - Protected with admin middleware
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard']);
    
    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminController::class, 'getUsers']);
        Route::get('/{id}', [App\Http\Controllers\AdminController::class, 'getUserDetails']);
        Route::put('/{id}', [App\Http\Controllers\AdminController::class, 'updateUser']);
        Route::delete('/{id}', [App\Http\Controllers\AdminController::class, 'deleteUser']);
        Route::post('/{id}/suspend', [App\Http\Controllers\AdminController::class, 'suspendUser']);
        Route::post('/{id}/activate', [App\Http\Controllers\AdminController::class, 'activateUser']);
    });
    
    // Caregiver Management
    Route::prefix('caregivers')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminController::class, 'getCaregivers']);
        Route::put('/{id}/approve', [App\Http\Controllers\AdminController::class, 'approveCaregiverProfile']);
        Route::put('/{id}/reject', [App\Http\Controllers\AdminController::class, 'rejectCaregiverProfile']);
        Route::put('/{id}/verify', [App\Http\Controllers\AdminController::class, 'verifyCaregiverProfile']);
    });
    
    // Booking Management
    Route::prefix('bookings')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminController::class, 'getBookings']);
        Route::put('/{id}/status', [App\Http\Controllers\AdminController::class, 'updateBookingStatus']);
        Route::get('/analytics', [App\Http\Controllers\AdminController::class, 'getBookingAnalytics']);
    });
    
    // Analytics and Reports
    Route::get('/analytics', [App\Http\Controllers\AdminController::class, 'getAnalytics']);
    Route::get('/reports/users', [App\Http\Controllers\AdminController::class, 'getUsersReport']);
    Route::get('/reports/revenue', [App\Http\Controllers\AdminController::class, 'getRevenueReport']);
    
    // System Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminController::class, 'getSettings']);
        Route::put('/', [App\Http\Controllers\AdminController::class, 'updateSettings']);
    });
    
    // Content Management
    Route::prefix('content')->group(function () {
        Route::get('/testimonials', [App\Http\Controllers\AdminController::class, 'getTestimonials']);
        Route::post('/testimonials', [App\Http\Controllers\AdminController::class, 'createTestimonial']);
        Route::put('/testimonials/{id}', [App\Http\Controllers\AdminController::class, 'updateTestimonial']);
        Route::delete('/testimonials/{id}', [App\Http\Controllers\AdminController::class, 'deleteTestimonial']);
    });
});

// Public API endpoints for landing page
Route::prefix('public')->group(function () {
    Route::get('/stats', [PublicController::class, 'getStats']);
    Route::get('/testimonials', [PublicController::class, 'getTestimonials']);
    Route::get('/search-caregivers', [PublicController::class, 'searchCaregivers']);
    Route::get('/service-types', [PublicController::class, 'getServiceTypes']);
});

// Location services (Google Places API integration)
Route::prefix('location')->group(function () {
    Route::get('/autocomplete', [LocationController::class, 'autocomplete']);
    Route::get('/details', [LocationController::class, 'details']);
    Route::get('/nearby', [LocationController::class, 'nearbySearch']);
    Route::get('/geocode', [LocationController::class, 'geocode']);
    Route::get('/distance', [LocationController::class, 'calculateDistance']);
});

