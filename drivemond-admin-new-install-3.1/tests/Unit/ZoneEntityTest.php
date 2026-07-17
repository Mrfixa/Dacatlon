<?php

namespace Tests\Unit;

use Modules\ZoneManagement\Entities\Zone;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Zone entity.
 */
class ZoneEntityTest extends TestCase
{
    public function test_fillable_attributes_exist(): void
    {
        $zone = new Zone();
        $fillable = $zone->getFillable();

        // Core fields
        $this->assertContains('readable_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('coordinates', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('extra_fare_status', $fillable);
    }

    public function test_model_uses_uuid_trait(): void
    {
        $zone = new Zone();
        $traits = class_uses_recursive($zone);

        $this->assertContains('App\Traits\HasUuid', $traits);
    }

    public function test_model_uses_soft_deletes(): void
    {
        $zone = new Zone();
        $traits = class_uses_recursive($zone);

        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', $traits);
    }

    public function test_model_uses_factory_trait(): void
    {
        $zone = new Zone();
        $traits = class_uses_recursive($zone);

        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
    }

    public function test_relationships_are_defined(): void
    {
        $zone = new Zone();

        // Test that relationship methods exist
        $this->assertTrue(method_exists($zone, 'tripFares'));
        $this->assertTrue(method_exists($zone, 'defaultFare'));
        $this->assertTrue(method_exists($zone, 'tripRequest'));
        $this->assertTrue(method_exists($zone, 'logs'));
        $this->assertTrue(method_exists($zone, 'customers'));
        $this->assertTrue(method_exists($zone, 'drivers'));
        $this->assertTrue(method_exists($zone, 'zoneTripFares'));
    }

    public function test_scopes_are_defined(): void
    {
        $zone = new Zone();

        // Scope method - 'scopeOfStatus' exists
        $this->assertTrue(method_exists($zone, 'scopeOfStatus'));
    }

    public function test_get_table_returns_correct_name(): void
    {
        $zone = new Zone();
        $tableName = $zone->getTable();

        $this->assertEquals('zones', $tableName);
    }

    public function test_casts_are_defined(): void
    {
        $zone = new Zone();
        $casts = $zone->getCasts();

        $this->assertArrayHasKey('readable_id', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('extra_fare_status', $casts);
    }
}
