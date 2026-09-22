<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourtRequest;
use App\Http\Requests\UpdateCourtRequest;
use App\Models\Court;
use App\Models\CourtSchedule;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CourtController extends Controller
{
    /**
     * Display a listing of courts.
     */
    public function index()
    {
        $courts = Court::orderBy('name')->get()->map(function ($court) {
            return [
                'id' => $court->id,
                'name' => $court->name,
                'description' => $court->description,
                'price_per_hour' => $court->price_per_hour,
                'image_url' => $court->image_path ? Storage::url($court->image_path) : null,
                'is_active' => $court->is_active,
                'created_at' => $court->created_at->format('d M Y'),
            ];
        });

        return Inertia::render('Admin/Courts/Index', [
            'courts' => $courts,
        ]);
    }

    /**
     * Show the form for creating a new court.
     */
    public function create()
    {
        return Inertia::render('Admin/Courts/Create');
    }

    /**
     * Store a newly created court in storage.
     */
    public function store(StoreCourtRequest $request)
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('courts', 'public');
        }

        // Remove the 'image' key (we use 'image_path' in the model)
        unset($data['image']);

        $court = Court::create($data);

        // Create default schedule (every day 06:00-22:00)
        for ($day = 0; $day <= 6; $day++) {
            CourtSchedule::create([
                'court_id' => $court->id,
                'day_of_week' => $day,
                'open_time' => '06:00',
                'close_time' => '22:00',
            ]);
        }

        return redirect()->route('admin.courts.index')
            ->with('success', 'Lapangan berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified court.
     */
    public function edit(Court $court)
    {
        return Inertia::render('Admin/Courts/Edit', [
            'court' => [
                'id' => $court->id,
                'name' => $court->name,
                'description' => $court->description,
                'price_per_hour' => $court->price_per_hour,
                'image_url' => $court->image_path ? Storage::url($court->image_path) : null,
                'image_path' => $court->image_path,
                'is_active' => $court->is_active,
            ],
        ]);
    }

    /**
     * Update the specified court in storage.
     */
    public function update(UpdateCourtRequest $request, Court $court)
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($court->image_path) {
                Storage::disk('public')->delete($court->image_path);
            }
            $data['image_path'] = $request->file('image')->store('courts', 'public');
        }

        // Remove the 'image' key
        unset($data['image']);

        $court->update($data);

        return redirect()->route('admin.courts.index')
            ->with('success', 'Lapangan berhasil diperbarui.');
    }

    /**
     * Remove the specified court from storage.
     */
    public function destroy(Court $court)
    {
        // Delete image from storage if exists
        if ($court->image_path) {
            Storage::disk('public')->delete($court->image_path);
        }

        $court->delete();

        return redirect()->route('admin.courts.index')
            ->with('success', 'Lapangan berhasil dihapus.');
    }
}

