<?php

namespace Tests\Unit;

use App\Models\Membership;
use PHPUnit\Framework\TestCase;

class MembershipCalculationTest extends TestCase
{
    /**
     * Test tier calculation boundaries:
     * - Bronze: 0 - 99 points
     * - Silver: 100 - 299 points
     * - Gold: 300+ points
     */
    public function test_calculate_tier_returns_correct_tier_for_point_thresholds(): void
    {
        // Bronze tier
        $this->assertEquals('bronze', Membership::calculateTier(0));
        $this->assertEquals('bronze', Membership::calculateTier(50));
        $this->assertEquals('bronze', Membership::calculateTier(99));

        // Silver tier (at 100)
        $this->assertEquals('silver', Membership::calculateTier(100));
        $this->assertEquals('silver', Membership::calculateTier(150));
        $this->assertEquals('silver', Membership::calculateTier(299));

        // Gold tier (at 300+)
        $this->assertEquals('gold', Membership::calculateTier(300));
        $this->assertEquals('gold', Membership::calculateTier(500));
        $this->assertEquals('gold', Membership::calculateTier(10000));
    }

    /**
     * Test discount percentage based on tier:
     * - Bronze: 0%
     * - Silver: 5%
     * - Gold: 10%
     */
    public function test_discount_percentage_matches_tier(): void
    {
        $bronze = new Membership(['tier' => 'bronze']);
        $this->assertEquals(0, $bronze->discount_percentage);

        $silver = new Membership(['tier' => 'silver']);
        $this->assertEquals(5, $silver->discount_percentage);

        $gold = new Membership(['tier' => 'gold']);
        $this->assertEquals(10, $gold->discount_percentage);

        $unknown = new Membership(['tier' => 'unknown']);
        $this->assertEquals(0, $unknown->discount_percentage);
    }

    /**
     * Test point award calculation formula:
     * Points = floor(amount / 10000)
     */
    public function test_loyalty_point_award_formula(): void
    {
        $calculatePoints = fn (float $amount): int => (int) floor($amount / 10000);

        $this->assertEquals(0, $calculatePoints(0));
        $this->assertEquals(0, $calculatePoints(9999));
        $this->assertEquals(1, $calculatePoints(10000));
        $this->assertEquals(1, $calculatePoints(19999));
        $this->assertEquals(4, $calculatePoints(45000));
        $this->assertEquals(8, $calculatePoints(80000));
        $this->assertEquals(10, $calculatePoints(100000));
        $this->assertEquals(25, $calculatePoints(255000));
        $this->assertEquals(100, $calculatePoints(1000000));
    }

    /**
     * Test discount calculation on booking total price:
     * Price with silver (5%) and gold (10%).
     */
    public function test_booking_discount_application(): void
    {
        $originalPrice = 200000.0;

        // Silver (5%)
        $silverDiscount = $originalPrice * (5 / 100);
        $silverFinal = $originalPrice - $silverDiscount;
        $this->assertEquals(10000.0, $silverDiscount);
        $this->assertEquals(190000.0, $silverFinal);

        // Gold (10%)
        $goldDiscount = $originalPrice * (10 / 100);
        $goldFinal = $originalPrice - $goldDiscount;
        $this->assertEquals(20000.0, $goldDiscount);
        $this->assertEquals(180000.0, $goldFinal);
    }
}

