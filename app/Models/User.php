<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUlids;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'user_type',
        'role',
        'is_active',
        'last_login_at',
        'profile_image',
        'notes',
        'email_verified_at',
        'id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Boot function to automatically generate UUID
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::id();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    // Relationships
    public function caregiverProfile()
    {
        return $this->hasOne(CaregiverProfile::class);
    }

    public function careSeekerProfile()
    {
        return $this->hasOne(CareSeekerProfile::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function careRequests()
    {
        return $this->hasMany(CareRequest::class, 'care_seeker_id');
    }

    public function caregiverBookings()
    {
        return $this->hasMany(Booking::class, 'caregiver_id');
    }

    public function careSeekerBookings()
    {
        return $this->hasMany(Booking::class, 'care_seeker_id');
    }

    public function givenReviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'reviewed_user_id');
    }

    // Scopes for performance
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCaregivers($query)
    {
        return $query->where('user_type', 'caregiver');
    }

    public function scopeCareSeekers($query)
    {
        return $query->where('user_type', 'care_seeker');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isCaregiver()
    {
        return $this->user_type === 'caregiver';
    }

    public function isCareSeeker()
    {
        return $this->user_type === 'care_seeker';
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }
}

