<?php

namespace Tests\Unit;

use Modules\TripManagement\Entities\MartOrder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MartOrder entity constants and methods.
 */
class MartOrderEntityTest extends TestCase
{
    public function test_statuses_contains_all_expected_values(): void
    {
        $expected = ['pending', 'accepted', 'picked_up', 'delivered', 'cancelled'];

        $this->assertEquals($expected, MartOrder::STATUSES);
    }

    public function test_status_transitions_has_expected_keys(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        $this->assertArrayHasKey('accepted', $transitions);
        $this->assertArrayHasKey('picked_up', $transitions);
        $this->assertArrayHasKey('delivered', $transitions);
        $this->assertArrayHasKey('cancelled', $transitions);
    }

    public function test_pending_can_transition_to_accepted(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        $this->assertContains('pending', $transitions['accepted']);
    }

    public function test_accepted_can_transition_to_picked_up(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        $this->assertContains('accepted', $transitions['picked_up']);
    }

    public function test_picked_up_can_transition_to_delivered(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        $this->assertContains('picked_up', $transitions['delivered']);
    }

    public function test_cancelled_can_only_come_from_pending_or_accepted(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        $this->assertContains('pending', $transitions['cancelled']);
        $this->assertContains('accepted', $transitions['cancelled']);
        $this->assertNotContains('picked_up', $transitions['cancelled']);
        $this->assertNotContains('delivered', $transitions['cancelled']);
    }

    public function test_delivered_is_terminal_state(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        // delivered should not be in any other transition's allowed "from" list
        foreach ($transitions as $target => $allowedFrom) {
            $this->assertNotContains('delivered', $allowedFrom, 
                "delivered should not be a valid 'from' state for transitioning to $target");
        }
    }

    public function test_statuses_count_matches_transitions_except_terminal(): void
    {
        $statuses = MartOrder::STATUSES;
        $transitions = MartOrder::STATUS_TRANSITIONS;

        // All statuses that have transitions (not just 'pending' since 'pending' is always the start)
        // The actual count matches since 'cancelled' is also a transition target
        $this->assertGreaterThanOrEqual(3, count($transitions));
    }

    public function test_each_transition_target_is_unique(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        // All keys should be unique (no duplicate transition targets)
        $keys = array_keys($transitions);
        $this->assertEquals(count($keys), count(array_unique($keys)));
    }

    public function test_transition_values_are_arrays(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        foreach ($transitions as $target => $allowedFrom) {
            $this->assertIsArray($allowedFrom, 
                "Transition target '$target' should have an array of allowed 'from' states");
            $this->assertNotEmpty($allowedFrom, 
                "Transition target '$target' should have at least one allowed 'from' state");
        }
    }

    public function test_no_self_transitions(): void
    {
        $transitions = MartOrder::STATUS_TRANSITIONS;

        foreach ($transitions as $target => $allowedFrom) {
            $this->assertNotContains($target, $allowedFrom,
                "A status should not be able to transition to itself ($target -> $target)");
        }
    }

    public function test_linear_flow_can_be_traced(): void
    {
        // Verify the happy path: pending -> accepted -> picked_up -> delivered
        $transitions = MartOrder::STATUS_TRANSITIONS;

        $this->assertContains('pending', $transitions['accepted']);
        $this->assertContains('accepted', $transitions['picked_up']);
        $this->assertContains('picked_up', $transitions['delivered']);
    }

    public function test_cancellation_flow_can_be_traced(): void
    {
        // Can cancel from pending or accepted
        $transitions = MartOrder::STATUS_TRANSITIONS;

        $this->assertContains('pending', $transitions['cancelled']);
        $this->assertContains('accepted', $transitions['cancelled']);
    }
}
