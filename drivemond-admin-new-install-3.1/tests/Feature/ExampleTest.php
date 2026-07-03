<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test the health endpoint is accessible.
     */
    public function test_health_endpoint(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(200);
    }
}
