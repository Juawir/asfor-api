<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Report;
use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    use ApiResponse;

    private function filterByDivisionAccess($user, $report)
    {
        if (!$user->isAdmin() && $user->division !== $report->division) {
            abort(403, 'Unauthorized action. Invalid division.');
        }
    }

    public function index(Request $request)
    {
        $query = Report::forDivision($request->user());

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->has('division') && $request->user()->isAdmin()) {
            $query->where('division', $request->division);
        }

        return $this->successResponse($query->get(), 'Reports retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'division'   => ['required', Rule::in(['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
            'date'       => 'required|date',
            'budget'     => 'required|numeric',
            'description'=> 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:2048',
            'status'     => 'nullable|string',
        ]);

        if (!$request->user()->isAdmin() && $validated['division'] !== $request->user()->division) {
            return $this->errorResponse('Anda hanya bisa membuat report untuk divisi Anda sendiri.', 403);
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        $report = Report::create($validated);
        $creator = $request->user();

        // 1. Notify all admins
        $admins = User::where('role', 'admin')->pluck('id');
        foreach ($admins as $adminId) {
            AppNotification::notify(
                $adminId,
                'report',
                '📄 Laporan Baru Perlu Disetujui',
                'Laporan "' . $report->title . '" dari divisi ' . $report->division . ' telah dikirimkan oleh ' . $creator->name . '. Silakan tinjau dan setujui.',
                ['report_id' => $report->id, 'division' => $report->division]
            );
        }

        // 2. Notify same-division members (except creator)
        $divisionMembers = User::where('division', $report->division)
            ->where('id', '!=', $creator->id)
            ->pluck('id');

        foreach ($divisionMembers as $uid) {
            AppNotification::notify(
                $uid,
                'report_division',
                '📋 Laporan Baru dari Divisi ' . $report->division,
                $creator->name . ' telah membuat laporan baru: "' . $report->title . '".',
                ['report_id' => $report->id, 'division' => $report->division]
            );
        }

        return $this->successResponse($report, 'Report created successfully', 201);
    }

    public function show(Request $request, Report $report)
    {
        $this->filterByDivisionAccess($request->user(), $report);
        return $this->successResponse($report, 'Report retrieved successfully');
    }

    public function update(Request $request, Report $report)
    {
        $this->filterByDivisionAccess($request->user(), $report);

        $validated = $request->validate([
            'title'      => 'sometimes|required|string|max:255',
            'division'   => ['sometimes', 'required', Rule::in(['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
            'date'       => 'sometimes|required|date',
            'budget'     => 'sometimes|required|numeric',
            'description'=> 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:2048',
            'status'     => 'sometimes|required|string',
        ]);

        if (array_key_exists('division', $validated) && !$request->user()->isAdmin() && $validated['division'] !== $request->user()->division) {
            return $this->errorResponse('Anda tidak bisa mengubah ke divisi lain.', 403);
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        $report->update($validated);

        return $this->successResponse($report, 'Report updated successfully');
    }

    // PATCH /reports/{id}/approve  (admin only)
    public function approve(Request $request, Report $report)
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Hanya admin yang dapat menyetujui laporan.', 403);
        }

        $report->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->name,
            'approved_at' => now(),
            'rejection_note' => null,
        ]);

        // Notify division members that report is approved
        $divisionMembers = User::where('division', $report->division)->pluck('id');
        foreach ($divisionMembers as $uid) {
            AppNotification::notify(
                $uid,
                'report_approved',
                '✅ Laporan Disetujui!',
                'Laporan "' . $report->title . '" dari divisi ' . $report->division . ' telah disetujui oleh ' . $request->user()->name . '.',
                ['report_id' => $report->id]
            );
        }

        return $this->successResponse($report->fresh(), 'Report approved successfully');
    }

    // PATCH /reports/{id}/reject  (admin only)
    public function reject(Request $request, Report $report)
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Hanya admin yang dapat menolak laporan.', 403);
        }

        $request->validate([
            'rejection_note' => 'required|string|max:500',
        ]);

        $report->update([
            'status'         => 'rejected',
            'rejection_note' => $request->rejection_note,
            'approved_by'    => null,
            'approved_at'    => null,
        ]);

        // Notify division members that report is rejected
        $divisionMembers = User::where('division', $report->division)->pluck('id');
        foreach ($divisionMembers as $uid) {
            AppNotification::notify(
                $uid,
                'report_rejected',
                '❌ Laporan Ditolak',
                'Laporan "' . $report->title . '" dari divisi ' . $report->division . ' ditolak. Alasan: ' . $request->rejection_note,
                ['report_id' => $report->id]
            );
        }

        return $this->successResponse($report->fresh(), 'Report rejected');
    }

    public function destroy(Request $request, Report $report)
    {
        $this->filterByDivisionAccess($request->user(), $report);
        $report->delete();
        return response()->json(null, 204);
    }
}
