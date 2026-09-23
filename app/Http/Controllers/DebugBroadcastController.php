<?php

namespace App\Http\Controllers;

use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Court;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DebugBroadcastController extends Controller
{
    /**
     * Display the debug broadcasting test UI.
     * Accessible ONLY in local environment.
     */
    public function index(): Response
    {
        if (! app()->environment('local', 'testing')) {
            abort(404);
        }

        $courts = Court::where('is_active', true)->get(['id', 'name', 'price_per_hour']);

        return Inertia::render('Debug/BroadcastTest', [
            'courts' => $courts,
            'today' => now()->format('Y-m-d'),
            'reverbConfig' => [
                'app_key' => config('reverb.apps.apps.0.key'),
                'host' => config('reverb.servers.reverb.hostname') ?: 'localhost',
                'port' => config('reverb.servers.reverb.port') ?: 8080,
                'scheme' => config('reverb.apps.apps.0.options.scheme') ?: 'http',
                'broadcast_driver' => config('broadcasting.default'),
            ],
        ]);
    }

    /**
     * Trigger a mock broadcast event to test WebSocket delivery.
     * Accessible ONLY in local environment.
     */
    public function trigger(Request $request): JsonResponse
    {
        if (! app()->environment('local', 'testing')) {
            abort(404);
        }

        $validated = $request->validate([
            'court_id' => 'required|exists:courts,id',
            'type' => 'required|in:created,cancelled',
            'booking_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ]);

        $mockBooking = new Booking([
            'user_id' => auth()->id() ?? 1,
            'court_id' => (int) $validated['court_id'],
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['start_time'] . ':00',
            'end_time' => $validated['end_time'] . ':00',
            'status' => $validated['type'] === 'created' ? 'confirmed' : 'cancelled',
            'total_price' => 40000,
            'notes' => '[DEBUG DUMMY BROADCAST EVENT]',
            'is_recurring' => false,
        ]);
        $mockBooking->id = rand(90000, 99999);

        if ($validated['type'] === 'created') {
            event(new BookingCreated($mockBooking));
            $eventClass = 'BookingCreated';
        } else {
            event(new BookingCancelled($mockBooking));
            $eventClass = 'BookingCancelled';
        }

        return response()->json([
            'success' => true,
            'event' => $eventClass,
            'channel' => 'court.' . $validated['court_id'],
            'payload' => [
                'booking_id' => $mockBooking->id,
                'court_id' => (int) $validated['court_id'],
                'booking_date' => $validated['booking_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'user_id' => $mockBooking->user_id,
                'status' => $mockBooking->status,
            ],
            'dispatched_at' => now()->toIso8601String(),
        ]);
    }
}
