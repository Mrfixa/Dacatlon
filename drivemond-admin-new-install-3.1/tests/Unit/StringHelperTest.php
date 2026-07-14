<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for string helper functions.
 */
class StringHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Load the helpers file to make functions available
        require_once base_path('app/Lib/Helpers.php');
    }

    public function test_remove_special_characters_removes_quotes(): void
    {
        $input = "Hello 'World' \"Test\"";
        $result = removeSpecialCharacters($input);

        $this->assertStringNotContainsString("'", $result);
        $this->assertStringNotContainsString('"', $result);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('World', $result);
        $this->assertStringContainsString('Test', $result);
    }

    public function test_remove_special_characters_removes_semicolon(): void
    {
        $input = "Test;Injection";
        $result = removeSpecialCharacters($input);

        $this->assertStringNotContainsString(';', $result);
    }

    public function test_remove_special_characters_removes_angle_brackets(): void
    {
        $input = "<script>alert('xss')</script>";
        $result = removeSpecialCharacters($input);

        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function test_remove_special_characters_collapse_multiple_spaces(): void
    {
        $input = "Hello    World";
        $result = removeSpecialCharacters($input);

        $this->assertStringNotContainsString('  ', $result);
    }

    public function test_remove_special_characters_handles_null(): void
    {
        $result = removeSpecialCharacters(null);
        // The function returns a string even for null input (converts to ' ')
        $this->assertIsString($result);
    }

    public function test_remove_special_characters_preserves_normal_text(): void
    {
        $input = "Hello World 123 ABC";
        $result = removeSpecialCharacters($input);

        $this->assertEquals('Hello World 123 ABC', $result);
    }

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
        $input = "Line1%%Line2";
        $result = change_text_color_or_bg($input);

        $this->assertStringContainsString('</br>', $result);
    }

    public function test_change_text_color_or_bg_double_at_format(): void
    {
        $input = "Hello @@World@@";
        $result = change_text_color_or_bg($input);

        $this->assertStringContainsString('<b>World</b>', $result);
    }

    public function test_change_text_color_or_bg_handles_null(): void
    {
        $result = change_text_color_or_bg(null);
        $this->assertEquals('', $result);
    }

    public function test_change_text_color_or_bg_no_changes(): void
    {
        $input = "Plain text without any formatting";
        $result = change_text_color_or_bg($input);

        $this->assertEquals($input, $result);
    }

    public function test_change_text_color_or_bg_combined_formats(): void
    {
        $input = "Hello **Bold** and ##Highlight## and @@Bold@@ with %%linebreak%%";
        $result = change_text_color_or_bg($input);

        $this->assertStringContainsString('<span class="text--base">', $result);
        $this->assertStringContainsString('<span class="bg-primary text-white">', $result);
        $this->assertStringContainsString('<b>', $result);
        $this->assertStringContainsString('</br>', $result);
    }
}
