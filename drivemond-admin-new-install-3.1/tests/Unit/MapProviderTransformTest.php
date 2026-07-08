<?php

namespace Tests\Unit;

use Modules\BusinessManagement\Service\MapProviderService;
use PHPUnit\Framework\TestCase;

/**
 * Pure transform tests: given canned Mapbox API responses, the service must emit the
 * exact Google-shaped JSON the Flutter apps parse (Places v1 suggestions, geocode
 * results[].formatted_address, distancematrix rows[].elements[], and the normalized
 * getRoutes() two-entry array). No HTTP, no DB.
 */
class MapProviderTransformTest extends TestCase
{
    public function test_suggest_transform_produces_places_v1_shape(): void
    {
        $mapbox = [
            'suggestions' => [
                [
                    'name' => 'Golden Gate Bridge',
                    'mapbox_id' => 'dXJuOm1ieHBvaTox',
                    'place_formatted' => 'San Francisco, California, United States',
                ],
                [
                    'name' => 'Golden Gate Park',
                    'mapbox_id' => 'dXJuOm1ieHBvaToy',
                    'full_address' => 'San Francisco, CA 94121, United States',
                ],
            ],
        ];

        $out = MapProviderService::transformMapboxSuggest($mapbox);

        $this->assertCount(2, $out['suggestions']);
        $first = $out['suggestions'][0]['placePrediction'];
        $this->assertSame('dXJuOm1ieHBvaTox', $first['placeId']);
        $this->assertSame('Golden Gate Bridge, San Francisco, California, United States', $first['text']['text']);
        $this->assertSame('Golden Gate Bridge', $first['structuredFormat']['mainText']['text']);
        // second entry falls back to full_address for the secondary line
        $second = $out['suggestions'][1]['placePrediction'];
        $this->assertSame('San Francisco, CA 94121, United States', $second['structuredFormat']['secondaryText']['text']);
    }

    public function test_suggest_transform_empty_input(): void
    {
        $this->assertSame(['suggestions' => []], MapProviderService::transformMapboxSuggest([]));
    }

    public function test_retrieve_transform_produces_place_details_shape(): void
    {
        $mapbox = [
            'features' => [[
                'id' => 'feat-1',
                'properties' => [
                    'name' => 'Ferry Building',
                    'mapbox_id' => 'dXJuOm1ieHBvaToz',
                    'feature_type' => 'poi',
                    'full_address' => '1 Ferry Building, San Francisco, CA 94111',
                    'place_formatted' => 'San Francisco, CA 94111',
                    'coordinates' => ['latitude' => 37.7955, 'longitude' => -122.3937],
                ],
            ]],
        ];

        $out = MapProviderService::transformMapboxRetrieve($mapbox);

        $this->assertSame('dXJuOm1ieHBvaToz', $out['id']);
        $this->assertSame('1 Ferry Building, San Francisco, CA 94111', $out['formattedAddress']);
        $this->assertSame('Ferry Building', $out['displayName']['text']);
        $this->assertSame(37.7955, $out['location']['latitude']);
        $this->assertSame(-122.3937, $out['location']['longitude']);
        // The apps call json['types'].cast<String>() unguarded — types must ALWAYS exist.
        $this->assertIsArray($out['types']);
        $this->assertNotEmpty($out['types']);
    }

    public function test_retrieve_transform_always_emits_types_even_when_empty(): void
    {
        $out = MapProviderService::transformMapboxRetrieve([]);
        $this->assertIsArray($out['types']);
        $this->assertNotEmpty($out['types']);
    }

    public function test_reverse_transform_produces_google_geocode_shape(): void
    {
        $mapbox = [
            'features' => [[
                'id' => 'addr-1',
                'properties' => [
                    'full_address' => '600 Montgomery St, San Francisco, CA 94111',
                    'mapbox_id' => 'dXJuOm1ieGFkcjox',
                    'coordinates' => ['latitude' => 37.7952, 'longitude' => -122.4028],
                ],
            ]],
        ];

        $out = MapProviderService::transformMapboxReverse($mapbox);

        $this->assertSame('OK', $out['status']);
        $this->assertSame('600 Montgomery St, San Francisco, CA 94111', $out['results'][0]['formatted_address']);
        $this->assertSame(37.7952, $out['results'][0]['geometry']['location']['lat']);
    }

    public function test_reverse_transform_zero_results(): void
    {
        $out = MapProviderService::transformMapboxReverse(['features' => []]);
        $this->assertSame('ZERO_RESULTS', $out['status']);
        $this->assertSame([], $out['results']);
    }

    public function test_matrix_transform_produces_distancematrix_shape(): void
    {
        $mapbox = [
            'distances' => [[12345.6]],
            'durations' => [[1800.4]],
        ];

        $out = MapProviderService::transformMapboxMatrix($mapbox);

        $element = $out['rows'][0]['elements'][0];
        $this->assertSame('OK', $out['status']);
        $this->assertSame(12345, $element['distance']['value']);
        $this->assertSame(1800, $element['duration']['value']);
        $this->assertSame('12.35 km', $element['distance']['text']);
        $this->assertSame('OK', $element['status']);
    }

    public function test_directions_transform_matches_get_routes_contract(): void
    {
        $mapbox = [
            'routes' => [[
                'geometry' => '_p~iF~ps|U_ulLnnqC_mqNvxq`@',
                'distance' => 5000.0,
                'duration' => 600.0,
                'duration_typical' => 720.0,
            ]],
        ];

        $out = MapProviderService::transformMapboxDirections($mapbox);

        $this->assertCount(2, $out);
        [$bike, $drive] = $out;
        $this->assertSame('TWO_WHEELER', $bike['drive_mode']);
        $this->assertSame('DRIVE', $drive['drive_mode']);
        foreach ($out as $entry) {
            $this->assertSame('_p~iF~ps|U_ulLnnqC_mqNvxq`@', $entry['encoded_polyline']);
            $this->assertSame('OK', $entry['status']);
            $this->assertSame(5.0, $entry['distance']);
        }
        $this->assertSame(600, $drive['duration_sec']);
        $this->assertSame(720, $drive['duration_in_traffic_sec']);
        $this->assertSame(500, $bike['duration_sec']); // 600 / 1.2
    }

    public function test_directions_transform_no_route(): void
    {
        $this->assertSame(['error' => 'No route found'], MapProviderService::transformMapboxDirections(['routes' => []]));
    }
}
