<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PerformanceOptimization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Log slow requests (over 1 second)
        if ($executionTime > 1000) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => $executionTime . 'ms',
                'memory_usage' => memory_get_peak_usage(true) / 1024 / 1024 . 'MB'
            ]);
        }
        
        // Add performance headers
        $response->headers->set('X-Response-Time', $executionTime . 'ms');
        $response->headers->set('X-Memory-Usage', memory_get_peak_usage(true) / 1024 / 1024 . 'MB');
        
        return $response;
    }
}

// Cache Helper Class
class CacheHelper
{
    public static function rememberCaregivers($location = null, $serviceType = null, $minRating = null)
    {
        $cacheKey = 'caregivers_' . md5($location . $serviceType . $minRating);
        
        return Cache::remember($cacheKey, 300, function () use ($location, $serviceType, $minRating) {
            $query = \App\Models\CaregiverProfile::with('user');
            
            if ($location) {
                $query->inLocation($location);
            }
            
            if ($serviceType) {
                $query->withService($serviceType);
            }
            
            if ($minRating) {
                $query->minRating($minRating);
            }
            
            return $query->verified()->get();
        });
    }
    
    public static function rememberCareRequests($location = null, $serviceType = null)
    {
        $cacheKey = 'care_requests_' . md5($location . $serviceType);
        
        return Cache::remember($cacheKey, 180, function () use ($location, $serviceType) {
            $query = \App\Models\CareRequest::with('careSeeker');
            
            if ($location) {
                $query->inLocation($location);
            }
            
            if ($serviceType) {
                $query->where('service_type', $serviceType);
            }
            
            return $query->active()->latest()->get();
        });
    }
    
    public static function clearUserCache($userId)
    {
        $patterns = [
            "user_bookings_{$userId}",
            "user_reviews_{$userId}",
            "user_conversations_{$userId}",
            "caregiver_reviews_{$userId}",
            "caregiver_rating_{$userId}"
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}

