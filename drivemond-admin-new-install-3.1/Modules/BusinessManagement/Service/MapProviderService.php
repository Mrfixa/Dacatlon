<?php

namespace Modules\BusinessManagement\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Provider-aware facade for every server-side map operation (place autocomplete, place
 * details, reverse geocode, distance matrix, route computation).
 *
 * The admin panel stores `map_provider` ('google'|'mapbox') and `mapbox_access_token`
 * inside the `google_map_api` business setting. When the provider is mapbox AND a token
 * is present, calls go to the Mapbox APIs and each response is transformed into the
 * exact JSON shape the Google branch returns — both Flutter apps parse Google shapes
 * (Places v1 `suggestions[].placePrediction`, geocode `results[].formatted_address`,
 * the normalized getRoutes() array), so no app change is needed when switching.
 *
 * Fallback rule: mapbox selected but token empty -> Google branch (a half-configured
 * save must never break search/geocode/routing in production).
 *
 * The transformMapbox* methods are pure array->array and covered by unit tests
 * (tests/Unit/MapProviderTransformTest.php).
 */
class MapProviderService
{
    public function provider(): string
    {
        $value = businessConfig(GOOGLE_MAP_API)?->value ?? [];
        $provider = $value['map_provider'] ?? 'google';
        if ($provider === 'mapbox' && !empty($value['mapbox_access_token'])) {
            return 'mapbox';
        }
        return 'google';
    }

    protected function googleKey(): ?string
    {
        return businessConfig(GOOGLE_MAP_API)?->value['map_api_key_server'] ?? null;
    }

    protected function mapboxToken(): ?string
    {
        return businessConfig(GOOGLE_MAP_API)?->value['mapbox_access_token'] ?? null;
    }

    // ---------------------------------------------------------------- autocomplete

    public function autocomplete(string $searchText): array
    {
        // Provider outages / timeouts must degrade to the same empty shape the
        // apps already parse — never a 500 from a geocode hiccup.
        try {
            return $this->doAutocomplete($searchText);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Map provider autocomplete failed: ' . $e->getMessage());
            return ['suggestions' => []];
        }
    }

    private function doAutocomplete(string $searchText): array
    {
        if ($this->provider() === 'mapbox') {
            $response = Http::timeout(6)->connectTimeout(3)->get('https://api.mapbox.com/search/searchbox/v1/suggest', [
                'q' => $searchText,
                'access_token' => $this->mapboxToken(),
                'session_token' => (string) Str::uuid(),
                'limit' => 10,
            ]);
            if ($response->successful()) {
                return self::transformMapboxSuggest($response->json() ?? []);
            }
            return ['suggestions' => []];
        }

        $response = Http::timeout(6)->connectTimeout(3)->withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $this->googleKey(),
            'X-Goog-FieldMask' => '*',
        ])->post('https://places.googleapis.com/v1/places:autocomplete', [
            'input' => $searchText,
        ]);

        return $response->json() ?? [];
    }

    /** Mapbox Search Box `suggest` -> Google Places v1 `:autocomplete` shape. */
    public static function transformMapboxSuggest(array $mapbox): array
    {
        $suggestions = [];
        foreach ($mapbox['suggestions'] ?? [] as $item) {
            $main = $item['name'] ?? '';
            $secondary = $item['place_formatted'] ?? ($item['full_address'] ?? '');
            $full = trim($main . ($secondary !== '' ? ', ' . $secondary : ''));
            $suggestions[] = [
                'placePrediction' => [
                    'placeId' => $item['mapbox_id'] ?? '',
                    'text' => ['text' => $full],
                    'structuredFormat' => [
                        'mainText' => ['text' => $main],
                        'secondaryText' => ['text' => $secondary],
                    ],
                ],
            ];
        }
        return ['suggestions' => $suggestions];
    }

    // ---------------------------------------------------------------- place details

    public function placeDetails(string $placeId): array
    {
        // Provider outages / timeouts must degrade to the same empty shape the
        // apps already parse — never a 500 from a geocode hiccup.
        try {
            return $this->doPlaceDetails($placeId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Map provider placeDetails failed: ' . $e->getMessage());
            return ['id' => $placeId, 'types' => []];
        }
    }

    private function doPlaceDetails(string $placeId): array
    {
        if ($this->provider() === 'mapbox') {
            $response = Http::timeout(6)->connectTimeout(3)->get('https://api.mapbox.com/search/searchbox/v1/retrieve/' . urlencode($placeId), [
                'access_token' => $this->mapboxToken(),
                'session_token' => (string) Str::uuid(),
            ]);
            if ($response->successful()) {
                return self::transformMapboxRetrieve($response->json() ?? []);
            }
            return ['id' => $placeId, 'types' => []];
        }

        $response = Http::timeout(6)->connectTimeout(3)->withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $this->googleKey(),
            'X-Goog-FieldMask' => '*',
        ])->get('https://places.googleapis.com/v1/places/' . $placeId);

        return $response->json() ?? [];
    }

    /**
     * Mapbox Search Box `retrieve` -> Google Places v1 place shape.
     * `types` must ALWAYS be present: the apps call json['types'].cast<String>() unguarded.
     */
    public static function transformMapboxRetrieve(array $mapbox): array
    {
        $feature = $mapbox['features'][0] ?? [];
        $props = $feature['properties'] ?? [];
        $coords = $props['coordinates'] ?? [];
        $name = $props['name'] ?? '';
        $address = $props['full_address'] ?? ($props['place_formatted'] ?? $name);

        return [
            'id' => $props['mapbox_id'] ?? ($feature['id'] ?? ''),
            'name' => $name,
            'types' => array_values(array_filter([(string) ($props['feature_type'] ?? '')])) ?: ['point_of_interest'],
            'formattedAddress' => $address,
            'shortFormattedAddress' => $props['place_formatted'] ?? $address,
            'displayName' => ['text' => $name, 'languageCode' => 'en'],
            'location' => [
                'latitude' => $coords['latitude'] ?? null,
                'longitude' => $coords['longitude'] ?? null,
            ],
        ];
    }

    // ---------------------------------------------------------------- reverse geocode

    public function reverseGeocode(string|float $lat, string|float $lng): array
    {
        // Provider outages / timeouts must degrade to the same empty shape the
        // apps already parse — never a 500 from a geocode hiccup.
        try {
            return $this->doReverseGeocode($lat, $lng);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Map provider reverseGeocode failed: ' . $e->getMessage());
            return ['results' => [], 'status' => 'ZERO_RESULTS'];
        }
    }

    private function doReverseGeocode(string|float $lat, string|float $lng): array
    {
        if ($this->provider() === 'mapbox') {
            $response = Http::timeout(6)->connectTimeout(3)->get('https://api.mapbox.com/search/geocode/v6/reverse', [
                'latitude' => $lat,
                'longitude' => $lng,
                'access_token' => $this->mapboxToken(),
            ]);
            if ($response->successful()) {
                return self::transformMapboxReverse($response->json() ?? []);
            }
            return ['results' => [], 'status' => 'ZERO_RESULTS'];
        }

        $response = Http::timeout(6)->connectTimeout(3)->get(MAP_API_BASE_URI . '/geocode/json?latlng=' . $lat . ',' . $lng . '&key=' . $this->googleKey());
        return $response->json() ?? [];
    }

    /** Mapbox Geocoding v6 reverse -> Google geocode/json shape (results[].formatted_address). */
    public static function transformMapboxReverse(array $mapbox): array
    {
        $results = [];
        foreach ($mapbox['features'] ?? [] as $feature) {
            $props = $feature['properties'] ?? [];
            $coords = $props['coordinates'] ?? [];
            $results[] = [
                'formatted_address' => $props['full_address'] ?? ($props['name'] ?? ''),
                'place_id' => $props['mapbox_id'] ?? ($feature['id'] ?? ''),
                'geometry' => [
                    'location' => [
                        'lat' => $coords['latitude'] ?? null,
                        'lng' => $coords['longitude'] ?? null,
                    ],
                ],
                'address_components' => [],
                'types' => [],
            ];
        }
        return ['results' => $results, 'status' => $results === [] ? 'ZERO_RESULTS' : 'OK'];
    }

    // ---------------------------------------------------------------- distance matrix

    public function distanceMatrix(string|float $originLat, string|float $originLng, string|float $destLat, string|float $destLng, string $mode = 'driving'): array
    {
        // Provider outages / timeouts must degrade to the same empty shape the
        // apps already parse — never a 500 from a geocode hiccup.
        try {
            return $this->doDistanceMatrix($originLat, $originLng, $destLat, $destLng, $mode);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Map provider distanceMatrix failed: ' . $e->getMessage());
            return ['rows' => [], 'status' => 'ZERO_RESULTS'];
        }
    }

    private function doDistanceMatrix(string|float $originLat, string|float $originLng, string|float $destLat, string|float $destLng, string $mode = 'driving'): array
    {
        if ($this->provider() === 'mapbox') {
            $profile = $mode === 'walking' ? 'walking' : ($mode === 'bicycling' ? 'cycling' : 'driving');
            $coordinates = $originLng . ',' . $originLat . ';' . $destLng . ',' . $destLat;
            $response = Http::timeout(6)->connectTimeout(3)->get('https://api.mapbox.com/directions-matrix/v1/mapbox/' . $profile . '/' . $coordinates, [
                'annotations' => 'distance,duration',
                'sources' => 0,
                'destinations' => 1,
                'access_token' => $this->mapboxToken(),
            ]);
            if ($response->successful()) {
                return self::transformMapboxMatrix($response->json() ?? []);
            }
            return ['rows' => [], 'status' => 'ZERO_RESULTS'];
        }

        $response = Http::timeout(6)->connectTimeout(3)->get(MAP_API_BASE_URI . '/distancematrix/json?origins=' . $originLat . ',' . $originLng . '&destinations=' . $destLat . ',' . $destLng . '&travelmode=' . $mode . '&key=' . $this->googleKey());
        return $response->json() ?? [];
    }

    /** Mapbox Matrix -> Google distancematrix/json shape (rows[].elements[].distance/duration). */
    public static function transformMapboxMatrix(array $mapbox): array
    {
        $distance = $mapbox['distances'][0][0] ?? null;   // meters
        $duration = $mapbox['durations'][0][0] ?? null;   // seconds
        if ($distance === null && $duration === null) {
            return ['rows' => [], 'status' => 'ZERO_RESULTS'];
        }

        return [
            'destination_addresses' => [''],
            'origin_addresses' => [''],
            'rows' => [[
                'elements' => [[
                    'distance' => [
                        'text' => number_format(($distance ?? 0) / 1000, 2) . ' km',
                        'value' => (int) ($distance ?? 0),
                    ],
                    'duration' => [
                        'text' => number_format(($duration ?? 0) / 60, 2) . ' min',
                        'value' => (int) ($duration ?? 0),
                    ],
                    'status' => 'OK',
                ]],
            ]],
            'status' => 'OK',
        ];
    }

    // ---------------------------------------------------------------- routes

    /**
     * Same output contract as the legacy getRoutes() helper: two entries (TWO_WHEELER
     * with a 1.2 duration adjustment, then DRIVE), each carrying distance/duration
     * fields and `encoded_polyline` (precision-5 encoded — Google and Mapbox
     * `geometries=polyline` match, so the apps' PolylinePoints.decodePolyline works
     * unchanged).
     */
    public function routes(array $originCoordinates, array $destinationCoordinates, array $intermediateCoordinates = [], array $drivingMode = ['DRIVE']): array
    {
        // Provider outages / timeouts must degrade to the same empty shape the
        // apps already parse — never a 500 from a geocode hiccup.
        try {
            return $this->doRoutes($originCoordinates, $destinationCoordinates, $intermediateCoordinates, $drivingMode);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Map provider routes failed: ' . $e->getMessage());
            return ['error' => 'API request failed'];
        }
    }

    private function doRoutes(array $originCoordinates, array $destinationCoordinates, array $intermediateCoordinates = [], array $drivingMode = ['DRIVE']): array
    {
        if ($this->provider() === 'mapbox') {
            $points = [[$originCoordinates[1], $originCoordinates[0]]]; // lng,lat
            if (!empty($intermediateCoordinates) && !is_null($intermediateCoordinates[0][0] ?? null)) {
                foreach ($intermediateCoordinates as $wp) {
                    $points[] = [$wp[1], $wp[0]];
                }
            }
            $points[] = [$destinationCoordinates[1], $destinationCoordinates[0]];
            $coordinates = implode(';', array_map(fn ($p) => $p[0] . ',' . $p[1], $points));

            $response = Http::timeout(6)->connectTimeout(3)->get('https://api.mapbox.com/directions/v5/mapbox/driving-traffic/' . $coordinates, [
                'geometries' => 'polyline',
                'overview' => 'full',
                'access_token' => $this->mapboxToken(),
            ]);
            if (!$response->successful()) {
                return ['error' => 'API request failed', 'status' => $response->status(), 'details' => $response->json()];
            }
            return self::transformMapboxDirections($response->json() ?? []);
        }

        return $this->googleRoutes($originCoordinates, $destinationCoordinates, $intermediateCoordinates, $drivingMode);
    }

    /** Mapbox Directions v5 -> legacy getRoutes() response shape. */
    public static function transformMapboxDirections(array $mapbox): array
    {
        $route = $mapbox['routes'][0] ?? null;
        if (!$route) {
            return ['error' => 'No route found'];
        }

        $encodedPolyline = $route['geometry'] ?? null;
        $distance = (float) ($route['distance'] ?? 0);            // meters
        $durationSec = (int) round($route['duration'] ?? 0);      // seconds
        $durationInTrafficSec = (int) round($route['duration_typical'] ?? $route['duration'] ?? 0);
        $convertToBike = 1.2;

        return [
            [
                'distance' => (float) number_format($distance / 1000, 2, '.', ''),
                'distance_text' => number_format($distance / 1000, 2) . ' km',
                'duration' => number_format(($durationSec / 60) / $convertToBike, 2) . ' min',
                'duration_sec' => (int) ($durationSec / $convertToBike),
                'duration_in_traffic' => number_format(($durationInTrafficSec / 60) / $convertToBike, 2) . ' min',
                'duration_in_traffic_sec' => (int) ($durationInTrafficSec / $convertToBike),
                'status' => 'OK',
                'drive_mode' => 'TWO_WHEELER',
                'encoded_polyline' => $encodedPolyline,
            ],
            [
                'distance' => (float) number_format($distance / 1000, 2, '.', ''),
                'distance_text' => number_format($distance / 1000, 2) . ' km',
                'duration' => number_format($durationSec / 60, 2) . ' min',
                'duration_sec' => $durationSec,
                'duration_in_traffic' => number_format($durationInTrafficSec / 60, 2) . ' min',
                'duration_in_traffic_sec' => $durationInTrafficSec,
                'status' => 'OK',
                'drive_mode' => 'DRIVE',
                'encoded_polyline' => $encodedPolyline,
            ],
        ];
    }

    /** Verbatim port of the legacy Google computeRoutes call from the getRoutes() helper. */
    protected function googleRoutes(array $originCoordinates, array $destinationCoordinates, array $intermediateCoordinates = [], array $drivingMode = ['DRIVE']): array
    {
        $mapApiKey = $this->googleKey() ?? '';
        $url = 'https://routes.googleapis.com/directions/v2:computeRoutes';

        $origin = ['location' => ['latLng' => ['latitude' => $originCoordinates[0], 'longitude' => $originCoordinates[1]]]];
        $destination = ['location' => ['latLng' => ['latitude' => $destinationCoordinates[0], 'longitude' => $destinationCoordinates[1]]]];

        $waypoints = [];
        if (!empty($intermediateCoordinates) && !is_null($intermediateCoordinates[0][0] ?? null)) {
            foreach ($intermediateCoordinates as $wp) {
                $waypoints[] = ['location' => ['latLng' => ['latitude' => $wp[0], 'longitude' => $wp[1]]]];
            }
        }

        $data = [
            'origin' => $origin,
            'destination' => $destination,
            'intermediates' => $waypoints,
            'travelMode' => $drivingMode[0],
            'routingPreference' => 'TRAFFIC_AWARE',
            'computeAlternativeRoutes' => false,
            'languageCode' => 'en-US',
            'units' => 'METRIC',
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $mapApiKey,
            'X-Goog-FieldMask' => '*',
        ];

        $response = Http::timeout(6)->connectTimeout(3)->withHeaders($headers)->post($url, $data);

        if (!isset($response['routes'][0])) {
            $data['travelMode'] = 'DRIVE';
            $response = Http::timeout(6)->connectTimeout(3)->withHeaders($headers)->post($url, $data);
        }

        if (!$response->successful()) {
            return ['error' => 'API request failed', 'status' => $response->status(), 'details' => $response];
        }

        $result = $response->json();
        if (!isset($result['routes'][0])) {
            return ['error' => 'No route found'];
        }

        $route = $result['routes'][0];
        $encodedPolyline = $route['polyline']['encodedPolyline'] ?? null;
        $distance = $route['distanceMeters'] ?? 0;
        $duration = $route['duration'] ?? '0s';
        $durationInTraffic = $route['staticDuration'] ?? $duration;

        preg_match('/(\d+)s/i', $duration, $matches);
        $durationSec = isset($matches[1]) ? (int) $matches[1] : 0;

        preg_match('/(\d+)s/i', $durationInTraffic, $trafficMatches);
        $durationInTrafficSec = isset($trafficMatches[1]) ? (int) $trafficMatches[1] : 0;

        $convertToBike = 1.2;

        return [
            [
                'distance' => (float) number_format($distance / 1000, 2, '.', ''),
                'distance_text' => number_format($distance / 1000, 2) . ' km',
                'duration' => number_format(($durationSec / 60) / $convertToBike, 2) . ' min',
                'duration_sec' => (int) ($durationSec / $convertToBike),
                'duration_in_traffic' => number_format(($durationInTrafficSec / 60) / $convertToBike, 2) . ' min',
                'duration_in_traffic_sec' => (int) ($durationInTrafficSec / $convertToBike),
                'status' => 'OK',
                'drive_mode' => 'TWO_WHEELER',
                'encoded_polyline' => $encodedPolyline,
            ],
            [
                'distance' => (float) number_format($distance / 1000, 2, '.', ''),
                'distance_text' => number_format($distance / 1000, 2) . ' km',
                'duration' => number_format($durationSec / 60, 2) . ' min',
                'duration_sec' => $durationSec,
                'duration_in_traffic' => number_format($durationInTrafficSec / 60, 2) . ' min',
                'duration_in_traffic_sec' => $durationInTrafficSec,
                'status' => 'OK',
                'drive_mode' => 'DRIVE',
                'encoded_polyline' => $encodedPolyline,
            ],
        ];
    }
}
