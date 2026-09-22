<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'is_admin' => $request->user()->hasRole('admin'),
                    'membership' => $request->user()->membership ? [
                        'tier' => $request->user()->membership->tier,
                        'points' => $request->user()->membership->points,
                        'discount_percentage' => $request->user()->membership->discount_percentage,
                    ] : [
                        'tier' => 'bronze',
                        'points' => 0,
                        'discount_percentage' => 0,
                    ],
                ]) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
