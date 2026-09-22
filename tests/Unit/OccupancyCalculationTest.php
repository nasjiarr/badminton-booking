<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OccupancyCalculationTest extends TestCase
{
    protected const DAILY_HOURS_PER_COURT = 16;

    /**
     * Helper to compute occupancy rate percentage.
     */
    protected function computeOccupancy(float $occupiedHours, int $activeCourts): float
    {
        $totalSlotCapacity = max(1, $activeCourts * self::DAILY_HOURS_PER_COURT);

        return round(($occupiedHours / $totalSlotCapacity) * 100, 1);
    }

    public function test_zero_occupied_hours_yields_zero_percent_occupancy(): void
    {
        $rate = $this->computeOccupancy(0.0, 4);
        $this->assertEquals(0.0, $rate);
    }

    public function test_full_capacity_yields_one_hundred_percent_occupancy(): void
    {
        // 4 courts * 16 hours = 64 total capacity hours
        $rate = $this->computeOccupancy(64.0, 4);
        $this->assertEquals(100.0, $rate);
    }

    public function test_quarter_capacity_yields_twenty_five_percent(): void
    {
        // 16 hours booked on 4 courts = 16 / 64 = 25.0%
        $rate = $this->computeOccupancy(16.0, 4);
        $this->assertEquals(25.0, $rate);
    }

    public function test_fractional_occupancy_rounding_precision(): void
    {
        // 10 hours booked on 4 courts = 10 / 64 = 15.625% -> rounded to 15.6%
        $rate = $this->computeOccupancy(10.0, 4);
        $this->assertEquals(15.6, $rate);

        // 35 hours booked on 4 courts = 35 / 64 = 54.6875% -> rounded to 54.7%
        $rate = $this->computeOccupancy(35.0, 4);
        $this->assertEquals(54.7, $rate);
    }

    public function test_prevent_division_by_zero_when_no_active_courts(): void
    {
        $rate = $this->computeOccupancy(0.0, 0);
        $this->assertEquals(0.0, $rate);
    }

    public function test_single_court_occupancy_calculation(): void
    {
        // 1 court * 16 hours = 16 total capacity
        // 8 hours booked = 8 / 16 = 50.0%
        $rate = $this->computeOccupancy(8.0, 1);
        $this->assertEquals(50.0, $rate);
    }
}

