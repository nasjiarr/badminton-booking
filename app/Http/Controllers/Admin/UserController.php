<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\PointHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of registered users / members.
     */
    public function index(Request $request): Response
    {
        $search = trim($request->input('search', ''));
        $role = $request->input('role', 'all');
        $tier = $request->input('tier', 'all');
        $status = $request->input('status', 'all');

        $query = User::with(['roles:id,name', 'membership'])
            ->withCount('bookings')
            ->withSum(['bookings as total_spent' => function ($q) {
                $q->where('status', 'confirmed');
            }], 'total_price');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role !== 'all' && ! empty($role)) {
            $query->role($role);
        }

        if ($tier !== 'all' && ! empty($tier)) {
            $query->whereHas('membership', function ($q) use ($tier) {
                $q->where('tier', $tier);
            });
        }

        if ($status !== 'all' && ! empty($status)) {
            $query->where('is_active', $status === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(function (User $u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone ?? '-',
                    'is_active' => (bool) $u->is_active,
                    'created_at' => $u->created_at->format('d M Y H:i'),
                    'created_at_relative' => $u->created_at->diffForHumans(),
                    'role' => $u->roles->first()?->name ?? 'user',
                    'tier' => $u->membership?->tier ?? 'bronze',
                    'points' => (int) ($u->membership?->points ?? 0),
                    'bookings_count' => (int) ($u->bookings_count ?? 0),
                    'total_spent' => (float) ($u->total_spent ?? 0),
                ];
            });

        $summary = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'admin_count' => User::role('admin')->count(),
            'gold_members' => Membership::where('tier', 'gold')->count(),
            'silver_members' => Membership::where('tier', 'silver')->count(),
            'bronze_members' => Membership::where('tier', 'bronze')->count(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'tier' => $tier,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Return detailed data of a user for modal / inspection.
     */
    public function show(User $user): JsonResponse
    {
        $user->load([
            'roles:id,name',
            'membership',
            'bookings' => function ($q) {
                $q->with('court:id,name')->orderBy('created_at', 'desc')->take(10);
            },
            'pointHistories' => function ($q) {
                $q->orderBy('created_at', 'desc')->take(10);
            },
        ]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '-',
                'is_active' => (bool) $user->is_active,
                'role' => $user->roles->first()?->name ?? 'user',
                'tier' => $user->membership?->tier ?? 'bronze',
                'points' => (int) ($user->membership?->points ?? 0),
                'discount_percentage' => (int) ($user->membership?->discount_percentage ?? 0),
                'created_at' => $user->created_at->format('d M Y H:i'),
                'bookings_count' => $user->bookings()->count(),
                'total_spent' => (float) $user->bookings()->where('status', 'confirmed')->sum('total_price'),
                'recent_bookings' => $user->bookings->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'court_name' => $b->court?->name ?? 'Lapangan',
                        'booking_date' => $b->booking_date->format('d M Y'),
                        'start_time' => substr($b->start_time, 0, 5),
                        'end_time' => substr($b->end_time, 0, 5),
                        'status' => $b->status,
                        'total_price' => (float) $b->total_price,
                        'is_recurring' => (bool) $b->is_recurring,
                    ];
                }),
                'recent_point_histories' => $user->pointHistories->map(function ($ph) {
                    return [
                        'id' => $ph->id,
                        'points_earned' => (int) $ph->points_earned,
                        'points_used' => (int) $ph->points_used,
                        'description' => $ph->description,
                        'created_at' => $ph->created_at->format('d M Y H:i'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the role of a user (promote/demote admin or user).
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,user'],
        ]);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat mengubah hak akses akun Anda sendiri.');
        }

        $user->syncRoles([$validated['role']]);

        return back()->with('success', "Hak akses untuk {$user->name} berhasil diperbarui menjadi {$validated['role']}.");
    }

    /**
     * Toggle the active/inactive status of a user.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $newStatus = ! $user->is_active;
        $user->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'diaktifkan kembali' : 'dinonaktifkan';

        return back()->with('success', "Akun {$user->name} berhasil {$statusLabel}.");
    }

    /**
     * Adjust loyalty points manually for a user.
     */
    public function adjustPoints(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:add,subtract'],
            'points' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $membership = $user->membership()->firstOrCreate(
                ['user_id' => $user->id],
                ['points' => 0, 'tier' => 'bronze']
            );

            $points = (int) $validated['points'];
            $isAdd = $validated['type'] === 'add';

            if ($isAdd) {
                $newPoints = $membership->points + $points;
                PointHistory::create([
                    'user_id' => $user->id,
                    'booking_id' => null,
                    'points_earned' => $points,
                    'points_used' => 0,
                    'description' => 'Penyesuaian Admin (+): ' . $validated['description'],
                ]);
            } else {
                $newPoints = max(0, $membership->points - $points);
                $actualUsed = $membership->points - $newPoints;
                PointHistory::create([
                    'user_id' => $user->id,
                    'booking_id' => null,
                    'points_earned' => 0,
                    'points_used' => $actualUsed,
                    'description' => 'Penyesuaian Admin (-): ' . $validated['description'],
                ]);
            }

            $newTier = Membership::calculateTier($newPoints);
            $membership->update([
                'points' => $newPoints,
                'tier' => $newTier,
            ]);
        });

        return back()->with('success', "Poin loyalti {$user->name} berhasil disesuaikan.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $hasActiveBookings = $user->bookings()
            ->where('booking_date', '>=', now()->format('Y-m-d'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($hasActiveBookings) {
            return back()->with('error', "Tidak dapat menghapus {$user->name} karena masih memiliki jadwal booking aktif mendatang. Silakan batalkan booking tersebut terlebih dahulu atau nonaktifkan akun.");
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna '{$name}' telah dihapus dari sistem.");
    }
}
