<?php

namespace Tests\Feature;

use App\Events\BookingCreated;
use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DebugBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_broadcast_page_is_accessible_in_local_environment(): void
    {
        $court = Court::create([
            'name' => 'Lapangan Test',
            'description' => 'Test description',
            'price_per_hour' => 40000,
            'is_active' => true,
        ]);

        $response = $this->get(route('debug.broadcast.index'));

        $response->assertOk();
    }

    public function test_debug_broadcast_trigger_dispatches_event(): void
    {
        Event::fake([BookingCreated::class]);

        $court = Court::create([
            'name' => 'Lapangan Test',
            'description' => 'Test description',
            'price_per_hour' => 40000,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('debug.broadcast.trigger'), [
            'court_id' => $court->id,
            'type' => 'created',
            'booking_date' => now()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'event' => 'BookingCreated',
            'channel' => 'court.' . $court->id,
        ]);

        Event::assertDispatched(BookingCreated::class);
    }
}

