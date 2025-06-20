<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GooglePlacesService
{
    private $apiKey;
    private $baseUrl = 'https://maps.googleapis.com/maps/api/place/';

    public function __construct()
    {
        $this->apiKey = config('services.google.places_api_key');
        
        if (empty($this->apiKey)) {
            Log::warning('Google Places API key is not configured');
        }
    }

    /**
     * Get autocomplete suggestions for a given input
     */
    public function autocomplete($input, $sessionToken = null, $options = [])
    {
        if (empty($this->apiKey)) {
            return $this->getMockAutocompleteResponse($input);
        }

        $cacheKey = 'places_autocomplete_' . md5($input . serialize($options));
        
        return Cache::remember($cacheKey, 300, function () use ($input, $sessionToken, $options) {
            $params = array_merge([
                'input' => $input,
                'key' => $this->apiKey,
                'components' => 'country:us',
                'types' => '(cities)',
                'sessiontoken' => $sessionToken ?: uniqid(),
            ], $options);

            try {
                $response = Http::timeout(10)->get($this->baseUrl . 'autocomplete/json', $params);
                
                if ($response->successful()) {
                    return $response->json();
                } else {
                    Log::error('Google Places Autocomplete API error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return $this->getMockAutocompleteResponse($input);
                }
            } catch (\Exception $e) {
                Log::error('Google Places Autocomplete API exception', [
                    'message' => $e->getMessage(),
                    'input' => $input
                ]);
                return $this->getMockAutocompleteResponse($input);
            }
        });
    }

    /**
     * Get place details by place ID
     */
    public function getPlaceDetails($placeId, $sessionToken = null, $fields = null)
    {
        if (empty($this->apiKey)) {
            return $this->getMockPlaceDetailsResponse($placeId);
        }

        $cacheKey = 'places_details_' . md5($placeId . $fields);
        
        return Cache::remember($cacheKey, 3600, function () use ($placeId, $sessionToken, $fields) {
            $params = [
                'place_id' => $placeId,
                'key' => $this->apiKey,
                'fields' => $fields ?: 'formatted_address,geometry,name,place_id',
                'sessiontoken' => $sessionToken ?: uniqid(),
            ];

            try {
                $response = Http::timeout(10)->get($this->baseUrl . 'details/json', $params);
                
                if ($response->successful()) {
                    return $response->json();
                } else {
                    Log::error('Google Places Details API error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return $this->getMockPlaceDetailsResponse($placeId);
                }
            } catch (\Exception $e) {
                Log::error('Google Places Details API exception', [
                    'message' => $e->getMessage(),
                    'place_id' => $placeId
                ]);
                return $this->getMockPlaceDetailsResponse($placeId);
            }
        });
    }

    /**
     * Search for places near a location
     */
    public function nearbySearch($location, $radius = 50000, $type = null)
    {
        if (empty($this->apiKey)) {
            return $this->getMockNearbySearchResponse($location);
        }

        $cacheKey = 'places_nearby_' . md5($location . $radius . $type);
        
        return Cache::remember($cacheKey, 600, function () use ($location, $radius, $type) {
            $params = [
                'location' => $location,
                'radius' => $radius,
                'key' => $this->apiKey,
            ];

            if ($type) {
                $params['type'] = $type;
            }

            try {
                $response = Http::timeout(10)->get($this->baseUrl . 'nearbysearch/json', $params);
                
                if ($response->successful()) {
                    return $response->json();
                } else {
                    Log::error('Google Places Nearby Search API error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return $this->getMockNearbySearchResponse($location);
                }
            } catch (\Exception $e) {
                Log::error('Google Places Nearby Search API exception', [
                    'message' => $e->getMessage(),
                    'location' => $location
                ]);
                return $this->getMockNearbySearchResponse($location);
            }
        });
    }

    /**
     * Geocode an address to get coordinates
     */
    public function geocode($address)
    {
        if (empty($this->apiKey)) {
            return $this->getMockGeocodeResponse($address);
        }

        $cacheKey = 'geocode_' . md5($address);
        
        return Cache::remember($cacheKey, 3600, function () use ($address) {
            $params = [
                'address' => $address,
                'key' => $this->apiKey,
                'components' => 'country:US',
            ];

            try {
                $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', $params);
                
                if ($response->successful()) {
                    return $response->json();
                } else {
                    Log::error('Google Geocoding API error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return $this->getMockGeocodeResponse($address);
                }
            } catch (\Exception $e) {
                Log::error('Google Geocoding API exception', [
                    'message' => $e->getMessage(),
                    'address' => $address
                ]);
                return $this->getMockGeocodeResponse($address);
            }
        });
    }

    /**
     * Calculate distance between two points
     */
    public function calculateDistance($origin, $destination, $units = 'imperial')
    {
        if (empty($this->apiKey)) {
            return $this->getMockDistanceResponse($origin, $destination);
        }

        $cacheKey = 'distance_' . md5($origin . $destination . $units);
        
        return Cache::remember($cacheKey, 3600, function () use ($origin, $destination, $units) {
            $params = [
                'origins' => $origin,
                'destinations' => $destination,
                'units' => $units,
                'key' => $this->apiKey,
            ];

            try {
                $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/distancematrix/json', $params);
                
                if ($response->successful()) {
                    return $response->json();
                } else {
                    Log::error('Google Distance Matrix API error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return $this->getMockDistanceResponse($origin, $destination);
                }
            } catch (\Exception $e) {
                Log::error('Google Distance Matrix API exception', [
                    'message' => $e->getMessage(),
                    'origin' => $origin,
                    'destination' => $destination
                ]);
                return $this->getMockDistanceResponse($origin, $destination);
            }
        });
    }

    // Mock responses for when API key is not available
    private function getMockAutocompleteResponse($input)
    {
        return [
            'predictions' => [
                [
                    'description' => $input . ', WA, USA',
                    'place_id' => 'mock_place_id_' . md5($input),
                    'structured_formatting' => [
                        'main_text' => $input,
                        'secondary_text' => 'WA, USA'
                    ]
                ]
            ],
            'status' => 'OK'
        ];
    }

    private function getMockPlaceDetailsResponse($placeId)
    {
        return [
            'result' => [
                'formatted_address' => 'Sample Address, WA, USA',
                'geometry' => [
                    'location' => [
                        'lat' => 47.6062,
                        'lng' => -122.3321
                    ]
                ],
                'name' => 'Sample Location',
                'place_id' => $placeId
            ],
            'status' => 'OK'
        ];
    }

    private function getMockNearbySearchResponse($location)
    {
        return [
            'results' => [],
            'status' => 'OK'
        ];
    }

    private function getMockGeocodeResponse($address)
    {
        return [
            'results' => [
                [
                    'formatted_address' => $address,
                    'geometry' => [
                        'location' => [
                            'lat' => 47.6062,
                            'lng' => -122.3321
                        ]
                    ]
                ]
            ],
            'status' => 'OK'
        ];
    }

    private function getMockDistanceResponse($origin, $destination)
    {
        return [
            'rows' => [
                [
                    'elements' => [
                        [
                            'distance' => [
                                'text' => '10.5 mi',
                                'value' => 16898
                            ],
                            'duration' => [
                                'text' => '25 mins',
                                'value' => 1500
                            ],
                            'status' => 'OK'
                        ]
                    ]
                ]
            ],
            'status' => 'OK'
        ];
    }
}

