<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'rating',
        'comment',
        'user_type',
        'featured',
        'active',
        'image'
    ];

    protected $casts = [
        'rating' => 'integer',
        'featured' => 'boolean',
        'active' => 'boolean'
    ];

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByUserType($query, $userType)
    {
        return $query->where('user_type', $userType);
    }
}

