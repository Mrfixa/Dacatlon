<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\MartCategory;
use PHPUnit\Framework\TestCase;

class MartCategoryTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $category = new MartCategory([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'image' => 'category-electronics.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertEquals('Electronics', $category->name);
        $this->assertEquals('electronics', $category->slug);
        $this->assertEquals('category-electronics.jpg', $category->image);
        $this->assertTrue($category->is_active);
        $this->assertEquals(1, $category->sort_order);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $category = new MartCategory([
            'is_active' => 1,
        ]);

        $this->assertTrue($category->is_active);
        $this->assertIsBool($category->is_active);
    }

    public function test_is_active_false(): void
    {
        $category = new MartCategory([
            'is_active' => false,
        ]);

        $this->assertFalse($category->is_active);
    }

    public function test_sort_order_cast_to_integer(): void
    {
        $category = new MartCategory([
            'sort_order' => '10',
        ]);

        $this->assertIsInt($category->sort_order);
        $this->assertEquals(10, $category->sort_order);
    }

    public function test_sort_order_zero(): void
    {
        $category = new MartCategory([
            'sort_order' => 0,
        ]);

        $this->assertEquals(0, $category->sort_order);
    }

    public function test_null_image(): void
    {
        $category = new MartCategory([
            'name' => 'Uncategorized',
            'image' => null,
        ]);

        $this->assertNull($category->image);
    }

    public function test_null_slug(): void
    {
        $category = new MartCategory([
            'name' => 'New Category',
            'slug' => null,
        ]);

        $this->assertNull($category->slug);
    }

    public function test_products_method_exists(): void
    {
        $category = new MartCategory();
        $this->assertTrue(method_exists($category, 'products'));
    }

    public function test_uses_has_uuids_trait(): void
    {
        $traits = class_uses(MartCategory::class);

        $this->assertContains(\Illuminate\Database\Eloquent\Concerns\HasUuids::class, $traits);
    }

    public function test_uses_soft_deletes_trait(): void
    {
        $traits = class_uses(MartCategory::class);

        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, $traits);
    }

    public function test_multiple_categories_with_sort_order(): void
    {
        $categories = [
            new MartCategory(['name' => 'First', 'sort_order' => 3]),
            new MartCategory(['name' => 'Second', 'sort_order' => 1]),
            new MartCategory(['name' => 'Third', 'sort_order' => 2]),
        ];

        $sortOrders = array_map(fn($c) => $c->sort_order, $categories);
        $this->assertEquals([3, 1, 2], $sortOrders);
    }

    public function test_unicode_name(): void
    {
        $category = new MartCategory([
            'name' => 'Electrónica',
            'slug' => 'electronica',
        ]);

        $this->assertEquals('Electrónica', $category->name);
        $this->assertEquals('electronica', $category->slug);
    }
}
