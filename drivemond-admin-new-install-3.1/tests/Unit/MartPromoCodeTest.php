<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\TripManagement\Entities\MartPromoCode;
use Tests\TestCase;

class MartPromoCodeTest extends TestCase
{
    private function createPromoCode(array $attributes): MartPromoCode
    {
        $promo = new MartPromoCode();
        // Use forceFill to set attributes
        $promo->forceFill($attributes);
        return $promo;
    }

    public function test_is_valid_returns_true_for_active_non_expired_promo(): void
    {
        $promo = $this->createPromoCode([
            'is_active' => true,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 5,
        ]);

        $this->assertTrue($promo->isValid());
    }

    public function test_is_valid_returns_false_when_inactive(): void
    {
        $promo = $this->createPromoCode([
            'is_active' => false,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => null,
            'used_count' => 0,
        ]);

        $this->assertFalse($promo->isValid());
    }

    public function test_is_valid_returns_false_when_expired(): void
    {
        $promo = $this->createPromoCode([
            'is_active' => true,
            'expires_at' => Carbon::now()->subDay(),
            'usage_limit' => null,
            'used_count' => 0,
        ]);

        $this->assertFalse($promo->isValid());
    }

    public function test_is_valid_returns_false_when_usage_limit_reached(): void
    {
        $promo = $this->createPromoCode([
            'is_active' => true,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 10,
        ]);

        $this->assertFalse($promo->isValid());
    }

    public function test_is_valid_returns_true_when_no_usage_limit_set(): void
    {
        $promo = $this->createPromoCode([
            'is_active' => true,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => null,
            'used_count' => 100,
        ]);

        $this->assertTrue($promo->isValid());
    }

    public function test_is_valid_returns_true_when_no_expiry_set(): void
    {
        $promo = $this->createPromoCode([
            'is_active' => true,
            'expires_at' => null,
            'usage_limit' => null,
            'used_count' => 0,
        ]);

        $this->assertTrue($promo->isValid());
    }

    public function test_compute_discount_returns_zero_when_subtotal_below_min_order(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_amount' => 50,
            'max_discount' => null,
        ]);

        $this->assertEquals(0.0, $promo->computeDiscount(30));
    }

    public function test_compute_discount_returns_zero_when_discount_value_is_zero(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 0,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $this->assertEquals(0.0, $promo->computeDiscount(100));
    }

    public function test_compute_discount_returns_zero_when_discount_value_is_negative(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => -10,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $this->assertEquals(0.0, $promo->computeDiscount(100));
    }

    public function test_compute_discount_percentage_type(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 20,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $this->assertEquals(20.0, $promo->computeDiscount(100));
    }

    public function test_compute_discount_fixed_type(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'fixed',
            'discount_value' => 15,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        $this->assertEquals(15.0, $promo->computeDiscount(100));
    }

    public function test_compute_discount_respects_max_discount_cap(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 50,
            'min_order_amount' => 0,
            'max_discount' => 20,
        ]);

        // 50% of 100 = 50, but capped at max_discount = 20
        $this->assertEquals(20.0, $promo->computeDiscount(100));
    }

    public function test_compute_discount_does_not_exceed_subtotal(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'fixed',
            'discount_value' => 150,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        // Discount (150) cannot exceed subtotal (100)
        $this->assertEquals(100.0, $promo->computeDiscount(100));
    }

    public function test_compute_discount_rounds_to_two_decimals(): void
    {
        $promo = new MartPromoCode([
            'discount_type' => 'percent',
            'discount_value' => 33,
            'min_order_amount' => 0,
            'max_discount' => null,
        ]);

        // 33% of 100 = 33, should be 33.0
        $this->assertEquals(33.0, $promo->computeDiscount(100));
    }
}
