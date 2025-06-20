<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GooglePlacesService;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    private $googlePlacesService;

    public function __construct(GooglePlacesService $googlePlacesService)
    {
        $this->googlePlacesService = $googlePlacesService;
    }

    public function autocomplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'input' => 'required|string|min:2|max:100',
            'sessiontoken' => 'sometimes|string',
            'types' => 'sometimes|string',
            'components' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        $input = $request->get('input');
        $sessionToken = $request->get('sessiontoken');
        
        $options = [];
        if ($request->has('types')) {
            $options['types'] = $request->get('types');
        }
        if ($request->has('components')) {
            $options['components'] = $request->get('components');
        }

        try {
            $result = $this->googlePlacesService->autocomplete($input, $sessionToken, $options);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not fetch autocomplete suggestions',
                'message' => 'Service temporarily unavailable'
            ], 500);
        }
    }

    public function details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'place_id' => 'required|string',
            'sessiontoken' => 'sometimes|string',
            'fields' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        $placeId = $request->get('place_id');
        $sessionToken = $request->get('sessiontoken');
        $fields = $request->get('fields');

        try {
            $result = $this->googlePlacesService->getPlaceDetails($placeId, $sessionToken, $fields);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not fetch place details',
                'message' => 'Service temporarily unavailable'
            ], 500);
        }
    }

    public function nearbySearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required|string',
            'radius' => 'sometimes|integer|min:1|max:50000',
            'type' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        $location = $request->get('location');
        $radius = $request->get('radius', 10000);
        $type = $request->get('type');

        try {
            $result = $this->googlePlacesService->nearbySearch($location, $radius, $type);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not fetch nearby places',
                'message' => 'Service temporarily unavailable'
            ], 500);
        }
    }

    public function geocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|min:3|max:200'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        $address = $request->get('address');

        try {
            $result = $this->googlePlacesService->geocode($address);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not geocode address',
                'message' => 'Service temporarily unavailable'
            ], 500);
        }
    }

    public function calculateDistance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin' => 'required|string',
            'destination' => 'required|string',
            'units' => 'sometimes|in:metric,imperial'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 400);
        }

        $origin = $request->get('origin');
        $destination = $request->get('destination');
        $units = $request->get('units', 'imperial');

        try {
            $result = $this->googlePlacesService->calculateDistance($origin, $destination, $units);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not calculate distance',
                'message' => 'Service temporarily unavailable'
            ], 500);
        }
    }
}


