<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for polyline encoding/decoding helper functions.
 * These are pure functions with no external dependencies.
 */
class PolylineHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Load the helpers file to make functions available
        require_once base_path('app/Lib/Helpers.php');
    }

    public function test_decode_polyline_with_valid_input(): void
    {
        // Google-encoded string "_p~iF~ps|U_ulLnnqC_mqNvxq`@" decodes to roughly:
        // (38.5, -120.2), (40.7, -120.95), (43.252, -126.453)
        $encoded = '_p~iF~ps|U_ulLnnqC_mqNvxq`@';
        $result = decodePolyline($encoded);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));

        // First point should be around (38.5, -120.2)
        $this->assertEqualsWithDelta(38.5, $result[0][0], 0.01);
        $this->assertEqualsWithDelta(-120.2, $result[0][1], 0.01);
    }

    public function test_decode_polyline_with_empty_string(): void
    {
        $result = decodePolyline('');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_decode_polyline_with_single_point(): void
    {
        // Simple single point: (0, 0)
        $encoded = encodePolyline([[0, 0]]);
        $result = decodePolyline($encoded);

        $this->assertCount(1, $result);
        $this->assertEqualsWithDelta(0, $result[0][0], 0.00001);
        $this->assertEqualsWithDelta(0, $result[0][1], 0.00001);
    }

    public function test_encode_polyline_roundtrip(): void
    {
        $points = [
            [38.5, -120.2],
            [40.7, -120.95],
            [43.252, -126.453],
        ];

        $encoded = encodePolyline($points);
        $decoded = decodePolyline($encoded);

        $this->assertCount(count($points), $decoded);

        foreach ($points as $i => $original) {
            $this->assertEqualsWithDelta($original[0], $decoded[$i][0], 0.00001, "Latitude mismatch at index $i");
            $this->assertEqualsWithDelta($original[1], $decoded[$i][1], 0.00001, "Longitude mismatch at index $i");
        }
    }

    public function test_encode_polyline_with_negative_coordinates(): void
    {
        $points = [
            [-33.8688, 151.2093], // Sydney, Australia
            [-37.8136, 144.9631], // Melbourne, Australia
        ];

        $encoded = encodePolyline($points);
        $decoded = decodePolyline($encoded);

        $this->assertCount(2, $decoded);
        $this->assertEqualsWithDelta($points[0][0], $decoded[0][0], 0.00001);
        $this->assertEqualsWithDelta($points[0][1], $decoded[0][1], 0.00001);
    }

    public function test_encode_polyline_with_integers(): void
    {
        $points = [
            [0, 0],
            [1, 1],
            [-1, -1],
        ];

        $encoded = encodePolyline($points);
        $decoded = decodePolyline($encoded);

        $this->assertCount(3, $decoded);
    }

    public function test_encode_value_positive(): void
    {
        // Simple test for positive value encoding
        $encoded = encodeValue(10);
        $this->assertIsString($encoded);
        $this->assertNotEmpty($encoded);
    }

    public function test_encode_value_negative(): void
    {
        // Test negative value encoding
        $encoded = encodeValue(-10);
        $this->assertIsString($encoded);
        $this->assertNotEmpty($encoded);
    }

    public function test_encode_value_zero(): void
    {
        $encoded = encodeValue(0);
        // Zero should encode to a single character
        $this->assertEquals('?', $encoded);
    }

    public function test_project_vehicle_onto_segment_basic(): void
    {
        $vehicle = [40.0, -74.0];
        $segmentStart = [40.0, -75.0];
        $segmentEnd = [40.0, -73.0];

        $result = projectVehicleOntoSegment($vehicle, $segmentStart, $segmentEnd);

        // Vehicle is directly above the segment, should project to midpoint
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_project_vehicle_onto_segment_identical_points(): void
    {
        $vehicle = [40.0, -74.0];
        $identicalPoint = [40.0, -74.0];

        $result = projectVehicleOntoSegment($vehicle, $identicalPoint, $identicalPoint);

        // When segment points are identical, should return that point
        $this->assertEquals($identicalPoint, $result);
    }

    public function test_project_vehicle_onto_segment_clamps_to_endpoints(): void
    {
        // Vehicle beyond the segment end
        $vehicle = [40.0, -72.0]; // Beyond the segment
        $segmentStart = [40.0, -75.0];
        $segmentEnd = [40.0, -73.0];

        $result = projectVehicleOntoSegment($vehicle, $segmentStart, $segmentEnd);

        // Should be clamped to segment end
        $this->assertIsArray($result);
        $this->assertEqualsWithDelta($segmentEnd[0], $result[0], 0.00001);
        $this->assertEqualsWithDelta($segmentEnd[1], $result[1], 0.00001);
    }

    public function test_is_driver_deviated_returns_false_when_on_route(): void
    {
        // Create a simple straight route
        $points = [
            [40.0, -75.0],
            [40.0, -73.0],
        ];
        $encodedPolyline = encodePolyline($points);

        // Vehicle is exactly on the route
        $vehicleLat = 40.0;
        $vehicleLng = -74.0;

        $result = isDriverDeviated($encodedPolyline, $vehicleLat, $vehicleLng, 50);

        $this->assertFalse($result);
    }

    public function test_is_driver_deviated_returns_true_when_far(): void
    {
        // Create a simple straight route
        $points = [
            [40.0, -75.0],
            [40.0, -73.0],
        ];
        $encodedPolyline = encodePolyline($points);

        // Vehicle is far from the route (100km away)
        $vehicleLat = 41.0; // About 111km north
        $vehicleLng = -74.0;

        $result = isDriverDeviated($encodedPolyline, $vehicleLat, $vehicleLng, 50);

        $this->assertTrue($result);
    }
}
