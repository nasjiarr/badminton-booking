<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembershipController extends Controller
{
    /**
     * Display the user's membership and loyalty points page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ensure membership record exists
        $membership = $user->membership()->firstOrCreate(
            ['user_id' => $user->id],
            ['points' => 0, 'tier' => 'bronze']
        );

        $currentTier = $membership->tier;
        $points = $membership->points;

        $nextTier = match ($currentTier) {
            'bronze' => 'silver',
            'silver' => 'gold',
            default => null,
        };

        $targetPoints = match ($currentTier) {
            'bronze' => 100,
            'silver' => 300,
            default => 300,
        };

        $pointsNeeded = match ($currentTier) {
            'bronze' => max(0, 100 - $points),
            'silver' => max(0, 300 - $points),
            default => 0,
        };

        $progressPercentage = match ($currentTier) {
            'bronze' => min(100, (int) round(($points / 100) * 100)),
            'silver' => min(100, (int) round((max(0, $points - 100) / 200) * 100)),
            default => 100,
        };

        $pointHistories = $user->pointHistories()
            ->with(['booking.court'])
            ->latest()
            ->get();

        return Inertia::render('Membership/Index', [
            'membership' => [
                'tier' => $membership->tier,
                'points' => $membership->points,
                'discount_percentage' => $membership->discount_percentage,
                'next_tier' => $nextTier,
                'target_points' => $targetPoints,
                'points_needed' => $pointsNeeded,
                'progress_percentage' => $progressPercentage,
            ],
            'pointHistories' => $pointHistories,
            'tierBenefits' => [
                [
                    'tier' => 'bronze',
                    'name' => 'Bronze Rookie',
                    'min_points' => 0,
                    'max_points' => 99,
                    'discount' => 0,
                    'perks' => [
                        'Akses booking seluruh lapangan',
                        'Dapatkan 1 poin setiap kelipatan Rp 10.000',
                        'Akses booking rutin & riwayat lengkap',
                    ],
                ],
                [
                    'tier' => 'silver',
                    'name' => 'Silver Pro',
                    'min_points' => 100,
                    'max_points' => 299,
                    'discount' => 5,
                    'perks' => [
                        'Diskon otomatis 5% setiap booking lapangan',
                        'Dapatkan 1 poin setiap kelipatan Rp 10.000',
                        'Badge Silver Pro di profil & arena',
                    ],
                ],
                [
                    'tier' => 'gold',
                    'name' => 'Gold Champion',
                    'min_points' => 300,
                    'max_points' => null,
                    'discount' => 10,
                    'perks' => [
                        'Diskon otomatis 10% setiap booking lapangan',
                        'Maksimal hemat untuk booking rutin mingguan',
                        'Badge Gold Champion eksklusif di profil & arena',
                        'Prioritas reservasi slot favorit',
                    ],
                ],
            ],
        ]);
    }
}

