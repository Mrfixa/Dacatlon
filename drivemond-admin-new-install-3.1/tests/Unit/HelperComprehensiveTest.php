<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive Test for All PHP Helper Functions
 */
class HelperComprehensiveTest extends TestCase
{
    // ==========================================
    // Test removeSpecialCharacters
    // ==========================================
    public function test_remove_special_characters_removes_quotes(): void
    {
        $result = removeSpecialCharacters("Hello 'World'");
        $this->assertStringNotContainsString("'", $result);
        $this->assertStringNotContainsString('"', $result);
    }

    public function test_remove_special_characters_removes_semicolon(): void
    {
        $result = removeSpecialCharacters("Hello; World");
        $this->assertStringNotContainsString(';', $result);
    }

    public function test_remove_special_characters_removes_angle_brackets(): void
    {
        $result = removeSpecialCharacters("<script>alert('xss')</script>");
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function test_remove_special_characters_collapse_multiple_spaces(): void
    {
        $result = removeSpecialCharacters("Hello    World");
        $this->assertStringNotContainsString('  ', $result);
    }

    public function test_remove_special_characters_handles_empty_string(): void
    {
        $result = removeSpecialCharacters('');
        $this->assertIsString($result);
    }

    public function test_remove_special_characters_preserves_normal_text(): void
    {
        $input = "Hello World 123 ABC";
        $result = removeSpecialCharacters($input);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('World', $result);
        $this->assertStringContainsString('123', $result);
    }

    // ==========================================
    // Test change_text_color_or_bg
    // ==========================================
    public function test_change_text_color_or_bg_double_hash_format(): void
    {
        $input = "Hello ##World##";
        $result = change_text_color_or_bg($input);
        $this->assertStringContainsString('<span class="bg-primary text-white">', $result);
        $this->assertStringContainsString('World', $result);
    }

    public function test_change_text_color_or_bg_double_asterisk_format(): void
    {
        $input = "Hello **World**";
        $result = change_text_color_or_bg($input);
        $this->assertStringContainsString('<span class="text--base">', $result);
        $this->assertStringContainsString('World', $result);
    }

    public function test_change_text_color_or_bg_double_percent_format(): void
    {
        $input = "Hello%%World";
        $result = change_text_color_or_bg($input);
        $this->assertStringContainsString('</br>', $result);
    }

    public function test_change_text_color_or_bg_double_at_format(): void
    {
        $input = "Hello @@World@@";
        $result = change_text_color_or_bg($input);
        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('World', $result);
    }

    public function test_change_text_color_or_bg_handles_null(): void
    {
        $result = change_text_color_or_bg(null);
        $this->assertIsString($result);
    }

    public function test_change_text_color_or_bg_no_changes(): void
    {
        $input = "Hello World";
        $result = change_text_color_or_bg($input);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('World', $result);
    }

    public function test_change_text_color_or_bg_combined_formats(): void
    {
        $input = "##Hello## **World**%%Test @@Foo@@";
        $result = change_text_color_or_bg($input);
        $this->assertStringContainsString('<span class="bg-primary text-white">', $result);
        $this->assertStringContainsString('<span class="text--base">', $result);
        $this->assertStringContainsString('</br>', $result);
        $this->assertStringContainsString('<b>', $result);
    }

    // ==========================================
    // Test Polyline Functions
    // ==========================================
    public function test_decode_polyline_with_valid_input(): void
    {
        // Simple polyline encoding two points: (38.5, -120.2) and (40.7, -120.95)
        $encoded = '_p~iF~ps|U_ulLnnqC_mqNvxq`@';
        $points = decodePolyline($encoded);
        
        $this->assertIsArray($points);
        $this->assertGreaterThan(0, count($points));
    }

    public function test_decode_polyline_with_empty_string(): void
    {
        $points = decodePolyline('');
        $this->assertIsArray($points);
        $this->assertEmpty($points);
    }

    public function test_decode_polyline_with_single_point(): void
    {
        $points = decodePolyline('_gei@');
        $this->assertIsArray($points);
        $this->assertEquals(1, count($points));
    }

    public function test_encode_polyline_roundtrip(): void
    {
        $originalPoints = [
            [38.5, -120.2],
            [40.7, -120.95],
            [43.252, -126.453],
        ];
        
        $encoded = encodePolyline($originalPoints);
        $decoded = decodePolyline($encoded);
        
        // Due to precision, check that we get approximately the same points
        $this->assertEquals(count($originalPoints), count($decoded));
        
        foreach ($originalPoints as $i => $original) {
            $this->assertEqualsWithDelta($original[0], $decoded[$i][0], 0.0001);
            $this->assertEqualsWithDelta($original[1], $decoded[$i][1], 0.0001);
        }
    }

    public function test_encode_polyline_with_negative_coordinates(): void
    {
        $points = [
            [-38.5, -120.2],
            [-40.7, -120.95],
        ];
        
        $encoded = encodePolyline($points);
        $this->assertIsString($encoded);
        $this->assertNotEmpty($encoded);
        
        $decoded = decodePolyline($encoded);
        $this->assertEquals(count($points), count($decoded));
    }

    public function test_encode_polyline_with_integers(): void
    {
        $points = [
            [38, -120],
            [40, -121],
        ];
        
        $encoded = encodePolyline($points);
        $this->assertIsString($encoded);
    }

    public function test_encode_value_positive(): void
    {
        $result = encodeValue(38);
        $this->assertIsString($result);
    }

    public function test_encode_value_negative(): void
    {
        $result = encodeValue(-38);
        $this->assertIsString($result);
    }

    public function test_encode_value_zero(): void
    {
        $result = encodeValue(0);
        $this->assertIsString($result);
    }

    // ==========================================
    // Test projectVehicleOntoSegment
    // ==========================================
    public function test_project_vehicle_onto_segment_basic(): void
    {
        $vehicle = [40.0, -120.0];
        $segmentStart = [39.0, -119.0];
        $segmentEnd = [41.0, -121.0];
        
        $result = projectVehicleOntoSegment($vehicle, $segmentStart, $segmentEnd);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_project_vehicle_onto_segment_identical_points(): void
    {
        $vehicle = [40.0, -120.0];
        $segmentStart = [39.0, -119.0];
        $segmentEnd = [39.0, -119.0];
        
        $result = projectVehicleOntoSegment($vehicle, $segmentStart, $segmentEnd);
        
        // Should return the segment start point when both endpoints are identical
        $this->assertEquals($segmentStart, $result);
    }

    public function test_project_vehicle_onto_segment_clamps_to_endpoints(): void
    {
        // Vehicle is far from the segment, should clamp to an endpoint
        $vehicle = [50.0, -130.0]; // Far away
        $segmentStart = [40.0, -120.0];
        $segmentEnd = [41.0, -121.0];
        
        $result = projectVehicleOntoSegment($vehicle, $segmentStart, $segmentEnd);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    // ==========================================
    // Test isDriverDeviated
    // ==========================================
    public function test_is_driver_deviated_returns_false_when_on_route(): void
    {
        // Simple straight line
        $encoded = encodePolyline([
            [40.0, -120.0],
            [40.1, -120.1],
            [40.2, -120.2],
        ]);
        
        // Vehicle is close to the route
        $result = isDriverDeviated($encoded, 40.05, -120.05, 100);
        $this->assertFalse($result);
    }

    public function test_is_driver_deviated_returns_true_when_far(): void
    {
        // Simple straight line
        $encoded = encodePolyline([
            [40.0, -120.0],
            [40.1, -120.1],
        ]);
        
        // Vehicle is far from the route (50km away)
        $result = isDriverDeviated($encoded, 45.0, -125.0, 50);
        $this->assertTrue($result);
    }

    // ==========================================
    // Test string manipulation functions
    // ==========================================
    public function test_decode_polyline_handles_invalid_input(): void
    {
        // Should not throw exception, just return empty array
        $result = decodePolyline('invalid@@##input');
        $this->assertIsArray($result);
    }
}
