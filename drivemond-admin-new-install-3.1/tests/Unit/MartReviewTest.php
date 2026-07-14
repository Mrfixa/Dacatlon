<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\MartReview;
use PHPUnit\Framework\TestCase;

class MartReviewTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $review = new MartReview([
            'order_id' => 'order-uuid-123',
            'customer_id' => 'customer-uuid-456',
            'driver_id' => 'driver-uuid-789',
            'rating' => 5,
            'comment' => 'Great service!',
        ]);

        $this->assertEquals('order-uuid-123', $review->order_id);
        $this->assertEquals('customer-uuid-456', $review->customer_id);
        $this->assertEquals('driver-uuid-789', $review->driver_id);
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Great service!', $review->comment);
    }

    public function test_rating_cast_to_integer(): void
    {
        $review = new MartReview([
            'rating' => '4',
        ]);

        $this->assertIsInt($review->rating);
        $this->assertEquals(4, $review->rating);
    }

    public function test_minimum_rating(): void
    {
        $review = new MartReview([
            'rating' => 1,
        ]);

        $this->assertEquals(1, $review->rating);
    }

    public function test_maximum_rating(): void
    {
        $review = new MartReview([
            'rating' => 5,
        ]);

        $this->assertEquals(5, $review->rating);
    }

    public function test_null_comment(): void
    {
        $review = new MartReview([
            'rating' => 4,
            'comment' => null,
        ]);

        $this->assertNull($review->comment);
    }

    public function test_empty_comment(): void
    {
        $review = new MartReview([
            'rating' => 3,
            'comment' => '',
        ]);

        $this->assertEquals('', $review->comment);
    }

    public function test_order_method_exists(): void
    {
        $review = new MartReview();
        $this->assertTrue(method_exists($review, 'order'));
    }

    public function test_customer_method_exists(): void
    {
        $review = new MartReview();
        $this->assertTrue(method_exists($review, 'customer'));
    }

    public function test_driver_method_exists(): void
    {
        $review = new MartReview();
        $this->assertTrue(method_exists($review, 'driver'));
    }

    public function test_uses_has_uuids_trait(): void
    {
        $traits = class_uses(MartReview::class);

        $this->assertContains(\Illuminate\Database\Eloquent\Concerns\HasUuids::class, $traits);
    }

    public function test_long_comment(): void
    {
        $longComment = str_repeat('This is a detailed review comment. ', 50);
        $review = new MartReview([
            'rating' => 5,
            'comment' => $longComment,
        ]);

        $this->assertEquals($longComment, $review->comment);
        $this->assertGreaterThan(100, strlen($review->comment));
    }

    public function test_special_characters_in_comment(): void
    {
        $review = new MartReview([
            'rating' => 5,
            'comment' => 'Great service! ⭐️ 🎉 "Loved it"',
        ]);

        $this->assertEquals('Great service! ⭐️ 🎉 "Loved it"', $review->comment);
    }
}
