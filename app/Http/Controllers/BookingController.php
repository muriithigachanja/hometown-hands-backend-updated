<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\CaregiverProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function createBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'care_seeker_id' => 'required|exists:users,id',
            'caregiver_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Calculate total amount
        $startTime = Carbon::createFromFormat('H:i', $request->start_time);
        $endTime = Carbon::createFromFormat('H:i', $request->end_time);
        $durationHours = $endTime->diffInHours($startTime);

        // Get caregiver's hourly rate
        $caregiver = CaregiverProfile::where('user_id', $request->caregiver_id)->first();
        if (!$caregiver) {
            return response()->json(['error' => 'Caregiver not found'], 404);
        }

        $totalAmount = $durationHours * ($caregiver->hourly_rate ?? 0);

        $booking = Booking::create([
            'care_seeker_id' => $request->care_seeker_id,
            'caregiver_id' => $request->caregiver_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_amount' => $totalAmount,
            'special_instructions' => $request->special_instructions,
            'emergency_contact' => $request->emergency_contact,
            'emergency_phone' => $request->emergency_phone
        ]);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking->load(['careSeeker', 'caregiver'])
        ], 201);
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        $booking->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Booking updated successfully',
            'booking' => $booking->load(['careSeeker', 'caregiver'])
        ], 200);
    }

    public function getUserBookings($userId, Request $request)
    {
        $role = $request->query('role', 'care_seeker');
        
        $query = Booking::with(['careSeeker', 'caregiver']);
        
        if ($role === 'caregiver') {
            $query->where('caregiver_id', $userId);
        } else {
            $query->where('care_seeker_id', $userId);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        return response()->json($bookings, 200);
    }

    public function createReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reviewer_id' => 'required|exists:users,id',
            'reviewed_user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $review = Review::create([
                'reviewer_id' => $request->reviewer_id,
                'reviewed_user_id' => $request->reviewed_user_id,
                'booking_id' => $request->booking_id,
                'rating' => $request->rating,
                'comment' => $request->comment
            ]);

            // Update caregiver's average rating if reviewing a caregiver
            $caregiver = CaregiverProfile::where('user_id', $request->reviewed_user_id)->first();
            if ($caregiver) {
                $avgRating = Review::where('reviewed_user_id', $request->reviewed_user_id)
                    ->avg('rating');
                $reviewCount = Review::where('reviewed_user_id', $request->reviewed_user_id)
                    ->count();

                $caregiver->update([
                    'rating' => round($avgRating, 2),
                    'review_count' => $reviewCount
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Review created successfully',
                'review' => $review
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create review'], 500);
        }
    }

    public function getUserReviews($userId)
    {
        $reviews = Review::where('reviewed_user_id', $userId)
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reviews, 200);
    }

    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'payment_method' => 'required|string',
            'amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $booking = Booking::find($request->booking_id);
        
        // In a real implementation, you would integrate with Stripe, PayPal, etc.
        // For now, we'll simulate payment processing
        
        DB::beginTransaction();
        try {
            // Create payment record (you would add a payments table)
            $paymentData = [
                'booking_id' => $booking->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'status' => 'completed',
                'transaction_id' => 'txn_' . uniqid(),
                'processed_at' => now()
            ];

            // Update booking status to confirmed after payment
            $booking->update(['status' => 'confirmed']);

            DB::commit();

            return response()->json([
                'message' => 'Payment processed successfully',
                'payment' => $paymentData,
                'booking' => $booking
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Payment processing failed'], 500);
        }
    }
}

