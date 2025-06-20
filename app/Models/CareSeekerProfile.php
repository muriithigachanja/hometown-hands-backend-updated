<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareSeekerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipient_name',
        'recipient_age',
        'recipient_condition',
        'care_needs',
        'care_description',
        'schedule',
        'preferred_schedule',
        'location',
        'place_id',
        'latitude',
        'longitude',
        'formatted_address',
        'budget',
        'max_budget',
        'preferences',
        'special_requirements',
        'emergency_contact_name',
        'emergency_contact_phone',
        'additional_info',
        'active'
    ];

    protected $casts = [
        'care_needs' => 'array',
        'preferred_schedule' => 'array',
        'preferences' => 'array',
        'special_requirements' => 'array',
        'budget' => 'decimal:2',
        'max_budget' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function careRequests()
    {
        return $this->hasMany(CareRequest::class, 'care_seeker_id', 'user_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'care_seeker_id', 'user_id');
    }
}

// CareRequest Model
class CareRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'care_seeker_id',
        'title',
        'description',
        'service_type',
        'schedule',
        'location',
        'budget',
        'status'
    ];

    protected $casts = [
        'schedule' => 'array'
    ];

    public function careSeeker()
    {
        return $this->belongsTo(User::class, 'care_seeker_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInLocation($query, $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }
}

// Message Model
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'read'
    ];

    protected $casts = [
        'read' => 'boolean'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    public function scopeBetweenUsers($query, $user1, $user2)
    {
        return $query->where(function ($q) use ($user1, $user2) {
            $q->where('sender_id', $user1)->where('receiver_id', $user2);
        })->orWhere(function ($q) use ($user1, $user2) {
            $q->where('sender_id', $user2)->where('receiver_id', $user1);
        });
    }
}

// Booking Model
class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'care_seeker_id',
        'caregiver_id',
        'date',
        'start_time',
        'end_time',
        'total_amount',
        'special_instructions',
        'emergency_contact',
        'emergency_phone',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'total_amount' => 'decimal:2'
    ];

    public function careSeeker()
    {
        return $this->belongsTo(User::class, 'care_seeker_id');
    }

    public function caregiver()
    {
        return $this->belongsTo(User::class, 'caregiver_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}

// Review Model
class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_id',
        'reviewed_user_id',
        'booking_id',
        'rating',
        'comment'
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewedUser()
    {
        return $this->belongsTo(User::class, 'reviewed_user_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopeHighRating($query, $rating = 4)
    {
        return $query->where('rating', '>=', $rating);
    }
}

