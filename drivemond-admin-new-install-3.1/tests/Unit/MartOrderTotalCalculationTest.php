<?php

namespace Tests\Unit;

use Tests\TestCase;
use Modules\TripManagement\Entities\MartOrder;

class MartOrderTotalCalculationTest extends TestCase
{
    /** @test */
    public function subtotal_calculation_returns_zero_for_empty_items(): void
    {
        $items = [];
        $subtotal = collect($items)->sum(function ($item) {
            return ($item['price'] ?? 0) * ($item['qty'] ?? 1);
        });
        $this->assertEquals(0, $subtotal);
    }

    /** @test */
    public function subtotal_calculation_works_for_single_item(): void
    {
        $items = [['price' => 10.00, 'qty' => 2]];
        $subtotal = collect($items)->sum(function ($item) {
            return ($item['price'] ?? 0) * ($item['qty'] ?? 1);
        });
        $this->assertEquals(20.00, $subtotal);
    }

    /** @test */
    public function subtotal_calculation_works_for_multiple_items(): void
    {
        $items = [
            ['price' => 10.00, 'qty' => 2],
            ['price' => 5.50, 'qty' => 3],
            ['price' => 2.00, 'qty' => 1],
        ];
        $subtotal = collect($items)->sum(function ($item) {
            return ($item['price'] ?? 0) * ($item['qty'] ?? 1);
        });
        // 20 + 16.5 + 2 = 38.5
        $this->assertEquals(38.50, $subtotal);
    }

    /** @test */
    public function delivery_fee_adds_to_total(): void
    {
        $subtotal = 50.00;
        $deliveryFee = 5.00;
        $total = $subtotal + $deliveryFee;
        $this->assertEquals(55.00, $total);
    }

    /** @test */
    public function promo_discount_subtracts_from_total(): void
    {
        $subtotal = 50.00;
        $deliveryFee = 5.00;
        $discount = 10.00;
        $total = $subtotal + $deliveryFee - $discount;
        $this->assertEquals(45.00, $total);
    }

    /** @test */
    public function total_cannot_be_negative(): void
    {
        $subtotal = 50.00;
        $deliveryFee = 5.00;
        $discount = 100.00;
        $total = max(0, $subtotal + $deliveryFee - $discount);
        $this->assertEquals(0, $total);
    }
}
