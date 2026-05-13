<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Event;
use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Event::with('creator:id,name,division');

        // Filter by month/year if provided
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('event_date', $request->month)
                  ->whereYear('event_date', $request->year);
        }

        // Show events visible to user's division or 'Semua'
        if (!$user->isAdmin()) {
            $query->where(function($q) use ($user) {
                $q->where('division', 'Semua')
                  ->orWhere('division', $user->division);
            });
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $events = $query->orderBy('event_date')->get();

        return $this->successResponse($events, 'Events retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'description'=> 'nullable|string',
            'event_date' => 'required|date',
            'event_time' => 'nullable|date_format:H:i',
            'location'   => 'nullable|string|max:255',
            'division'   => ['required', Rule::in(['Semua', 'Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
        ]);

        // Non-admin can only create events for their own division
        if (!$request->user()->isAdmin() && $validated['division'] !== $request->user()->division) {
            return $this->errorResponse('Anda hanya bisa menambahkan kegiatan untuk divisi Anda sendiri.', 403);
        }

        $validated['created_by'] = $request->user()->id;
        $event = Event::create($validated);
        $event->load('creator:id,name,division');

        $creator   = $request->user();
        $dateLabel = $event->event_date->format('d/m/Y');
        $timeLabel = $event->event_time ? ' pukul ' . $event->event_time : '';

        if ($event->division === 'Semua') {
            // Kegiatan untuk semua → notify SEMUA user kecuali creator
            $recipients = User::where('id', '!=', $creator->id)->pluck('id');
        } else {
            // Kegiatan divisi spesifik → notify anggota divisi + semua admin (kecuali creator)
            $recipients = User::where('id', '!=', $creator->id)
                ->where(function ($q) use ($event) {
                    $q->where('division', $event->division)
                      ->orWhere('role', 'admin');
                })
                ->pluck('id');
        }

        foreach ($recipients as $uid) {
            AppNotification::notify(
                $uid,
                'event',
                '📅 Kegiatan Baru: ' . $event->title,
                'Ada kegiatan baru' . ($event->division !== 'Semua' ? ' untuk divisi ' . $event->division : '') . ' pada ' . $dateLabel . $timeLabel . '. Dibuat oleh ' . $creator->name . '.',
                ['event_id' => $event->id, 'event_date' => $event->event_date->format('Y-m-d'), 'division' => $event->division]
            );
        }

        return $this->successResponse($event, 'Event created successfully', 201);
    }

    public function update(Request $request, Event $event)
    {
        $user = $request->user();

        if (!$user->isAdmin() && $event->created_by !== $user->id) {
            return $this->errorResponse('Anda tidak bisa mengedit kegiatan ini.', 403);
        }

        $validated = $request->validate([
            'title'      => 'sometimes|required|string|max:255',
            'description'=> 'nullable|string',
            'event_date' => 'sometimes|required|date',
            'event_time' => 'nullable|date_format:H:i',
            'location'   => 'nullable|string|max:255',
            'division'   => ['sometimes', 'required', Rule::in(['Semua', 'Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
        ]);

        $event->update($validated);
        $event->load('creator:id,name,division');

        return $this->successResponse($event, 'Event updated successfully');
    }

    public function destroy(Request $request, Event $event)
    {
        $user = $request->user();

        if (!$user->isAdmin() && $event->created_by !== $user->id) {
            return $this->errorResponse('Anda tidak bisa menghapus kegiatan ini.', 403);
        }

        $event->delete();
        return response()->json(null, 204);
    }
}
