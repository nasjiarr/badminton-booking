<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourtClosureRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtClosure;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourtClosureController extends Controller
{
    /**
     * Display a listing of scheduled court closures and tournaments.
     */
    public function index(Request $request): Response
    {
        $today = now()->format('Y-m-d');

        $closures = CourtClosure::with(['court:id,name', 'creator:id,name'])
            ->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function (CourtClosure $closure) use ($today) {
                $startDate = Carbon::parse($closure->start_date)->format('Y-m-d');
                $endDate = Carbon::parse($closure->end_date)->format('Y-m-d');

                $status = 'upcoming';
                if ($endDate < $today) {
                    $status = 'past';
                } elseif ($startDate <= $today && $endDate >= $today) {
                    $status = 'active';
                }

                return [
                    'id' => $closure->id,
                    'name' => $closure->name,
                    'type' => $closure->type,
                    'type_label' => $closure->type_label,
                    'court_id' => $closure->court_id,
                    'court_name' => $closure->court ? $closure->court->name : 'Semua Lapangan (4 Lapangan)',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'start_time' => substr($closure->start_time, 0, 5),
                    'end_time' => substr($closure->end_time, 0, 5),
                    'days_count' => $closure->days_count,
                    'is_full_day' => substr($closure->start_time, 0, 5) === '06:00' && substr($closure->end_time, 0, 5) === '22:00',
                    'notes' => $closure->notes,
                    'created_by_name' => $closure->creator?->name ?? 'Admin',
                    'created_at' => $closure->created_at->format('d M Y H:i'),
                    'status' => $status,
                ];
            });

        $courts = Court::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $summary = [
            'total_closures' => $closures->count(),
            'active_count' => $closures->where('status', 'active')->count(),
            'upcoming_count' => $closures->where('status', 'upcoming')->count(),
            'tournaments_count' => $closures->where('type', 'tournament')->where('status', '!=', 'past')->count(),
        ];

        return Inertia::render('Admin/Closures/Index', [
            'closures' => $closures,
            'courts' => $courts,
            'summary' => $summary,
        ]);
    }

    /**
     * Store a newly created court closure or tournament schedule.
     */
    public function store(StoreCourtClosureRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        // Count any existing bookings that conflict with this closure
        $courtId = $data['court_id'] ?? null;
        $conflictsQuery = Booking::whereBetween('booking_date', [$data['start_date'], $data['end_date']])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($data) {
                $q->where('start_time', '<', $data['end_time'])
                  ->where('end_time', '>', $data['start_time']);
            });

        if ($courtId) {
            $conflictsQuery->where('court_id', $courtId);
        }

        $conflictsCount = $conflictsQuery->count();

        CourtClosure::create($data);

        $message = "Jadwal penutupan / turnamen '{$data['name']}' berhasil disimpan.";
        if ($conflictsCount > 0) {
            $message .= " Perhatian: Terdapat {$conflictsCount} booking aktif yang jadwalnya bertepatan dengan periode ini.";
        }

        return redirect()->route('admin.closures.index')->with('success', $message);
    }

    /**
     * Remove the specified court closure.
     */
    public function destroy(CourtClosure $closure): RedirectResponse
    {
        $name = $closure->name;
        $closure->delete();

        return redirect()->route('admin.closures.index')
            ->with('success', "Jadwal penutupan '{$name}' telah dihapus. Lapangan dapat dipesan kembali.");
    }
}
