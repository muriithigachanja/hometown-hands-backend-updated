<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CaregiverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'age',
        'gender',
        'languages',
        'location',
        'place_id',
        'latitude',
        'longitude',
        'formatted_address',
        'radius',
        'services_offered',
        'availability',
        'certifications',
        'experience',
        'bio',
        'specialties',
        'hourly_rate',
        'flat_rate',
        'verification_status',
        'background_check',
        'verified',
        'profile_image',
        'work_history',
        'education',
        'active',
        'rating',
        'review_count'
    ];

    protected $casts = [
        'languages' => 'array',
        'services_offered' => 'array',
        'availability' => 'array',
        'certifications' => 'array',
        'specialties' => 'array',
        'work_history' => 'array',
        'education' => 'array',
        'hourly_rate' => 'decimal:2',
        'flat_rate' => 'decimal:2',
        'rating' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'background_check' => 'boolean',
        'verified' => 'boolean',
        'active' => 'boolean'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'caregiver_id', 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewed_user_id', 'user_id');
    }

    // Scopes for performance
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeAvailable($query)
    {
        return $query->whereNotNull('availability');
    }

    public function scopeInLocation($query, $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    public function scopeWithService($query, $service)
    {
        return $query->whereJsonContains('services_offered', $service);
    }

    public function scopeMinRating($query, $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    // Cache frequently accessed data
    public function getCachedReviews()
    {
        return Cache::remember("caregiver_reviews_{$this->id}", 3600, function () {
            return $this->reviews()->with('reviewer')->latest()->get();
        });
    }

    public function getAverageRatingAttribute()
    {
        return Cache::remember("caregiver_rating_{$this->id}", 3600, function () {
            return $this->reviews()->avg('rating') ?? 0;
        });
    }
}

