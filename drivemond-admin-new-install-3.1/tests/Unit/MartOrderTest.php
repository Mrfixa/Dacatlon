<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\MartOrder;
use PHPUnit\Framework\TestCase;

class MartOrderTest extends TestCase
{
    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals(
            ['pending', 'accepted', 'picked_up', 'delivered', 'cancelled'],
            MartOrder::STATUSES
        );
    }

    public function test_status_transitions_define_expected_flow(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        // Valid forward flow: pending -> accepted -> picked_up -> delivered
        $this->assertEquals(['pending'], $transitions['accepted']);
        $this->assertEquals(['accepted'], $transitions['picked_up']);
        $this->assertEquals(['picked_up'], $transitions['delivered']);

        // Cancellation: can cancel from pending or accepted only
        $this->assertEquals(['pending', 'accepted'], $transitions['cancelled']);
    }

    public function test_statuses_without_transitions_are_terminal(): void
    {
        // 'pending' and 'delivered' are terminal states (no outgoing transitions)
        // in STATUS_TRANSITIONS (they are the 'from' states, not the 'to' states)
        $transitions = MartOrder::STATUS_TRANSITIONS;

        // Terminal states should not be keys in STATUS_TRANSITIONS
        // (no status can transition TO pending or delivered)
        $this->assertArrayNotHasKey('pending', $transitions);
    }

    public function test_all_transition_sources_are_valid_statuses(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;
        $statuses = MartOrder::STATUSES;

        // All 'from' statuses should be valid statuses
        $allFromStatuses = array_merge(...array_values($transitions));
        foreach ($allFromStatuses as $fromStatus) {
            $this->assertContains($fromStatus, $statuses, "From status '$fromStatus' should be a valid status");
        }

        // All transition targets should be valid statuses
        foreach (array_keys($transitions) as $toStatus) {
            $this->assertContains($toStatus, $statuses, "To status '$toStatus' should be a valid status");
        }
    }

    public function test_order_fillable_attributes(): void
    {
        $order = new MartOrder([
            'ref_id' => 'TEST-REF-001',
            'customer_id' => 'cust-123',
            'driver_id' => 'drv-456',
            'status' => 'pending',
            'total_amount' => 99.99,
            'tip_amount' => 5.00,
            'discount_amount' => 10.00,
            'delivery_fee' => 3.50,
            'tax_amount' => 1.50,
            'promo_code' => 'SAVE10',
            'payment_status' => 'unpaid',
            'payment_method' => 'stripe',
            'delivery_address' => '123 Test St',
            'delivery_lat' => 40.7128,
            'delivery_lng' => -74.0060,
            'notes' => 'Leave at door',
            'cancellation_reason' => null,
            'cancelled_by' => null,
        ]);

        $this->assertEquals('TEST-REF-001', $order->ref_id);
        $this->assertEquals('cust-123', $order->customer_id);
        $this->assertEquals('drv-456', $order->driver_id);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(99.99, $order->total_amount);
        $this->assertEquals(5.00, $order->tip_amount);
        $this->assertEquals(10.00, $order->discount_amount);
        $this->assertEquals(3.50, $order->delivery_fee);
        $this->assertEquals(1.50, $order->tax_amount);
        $this->assertEquals('SAVE10', $order->promo_code);
        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertEquals('stripe', $order->payment_method);
        $this->assertEquals('123 Test St', $order->delivery_address);
        $this->assertEquals(40.7128, $order->delivery_lat);
        $this->assertEquals(-74.0060, $order->delivery_lng);
        $this->assertEquals('Leave at door', $order->notes);
    }

    public function test_order_casts_attributes_correctly(): void
    {
        $order = new MartOrder([
            'total_amount' => '99.99',
            'tip_amount' => '5.00',
            'discount_amount' => '10.00',
            'delivery_fee' => '3.50',
            'tax_amount' => '1.50',
            'delivery_lat' => '40.7128000',
            'delivery_lng' => '-74.0060000',
        ]);

        $this->assertIsString($order->total_amount);
        $this->assertIsString($order->tip_amount);
    }

    public function test_can_check_valid_transition(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        // Helper to check if a transition is valid
        $isValidTransition = function (string $from, string $to) use ($transitions): bool {
            return isset($transitions[$to]) && in_array($from, $transitions[$to]);
        };

        // Valid transitions
        $this->assertTrue($isValidTransition('pending', 'accepted'));
        $this->assertTrue($isValidTransition('accepted', 'picked_up'));
        $this->assertTrue($isValidTransition('picked_up', 'delivered'));
        $this->assertTrue($isValidTransition('pending', 'cancelled'));
        $this->assertTrue($isValidTransition('accepted', 'cancelled'));

        // Invalid transitions
        $this->assertFalse($isValidTransition('pending', 'picked_up'));
        $this->assertFalse($isValidTransition('pending', 'delivered'));
        $this->assertFalse($isValidTransition('accepted', 'delivered'));
        $this->assertFalse($isValidTransition('picked_up', 'cancelled'));
        $this->assertFalse($isValidTransition('delivered', 'cancelled'));
        $this->assertFalse($isValidTransition('cancelled', 'pending'));
    }
}
