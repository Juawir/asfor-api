<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Report;
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
            'title' => 'required|string|max:255',
            'division' => ['required', Rule::in(['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
            'date' => 'required|date',
            'budget' => 'required|numeric',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:2048',
            'status' => 'nullable|string',
        ]);

        if (!$request->user()->isAdmin() && $validated['division'] !== $request->user()->division) {
            return $this->errorResponse('Anda hanya bisa membuat report untuk divisi Anda sendiri.', 403);
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        $report = Report::create($validated);

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
            'title' => 'sometimes|required|string|max:255',
            'division' => ['sometimes', 'required', Rule::in(['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
            'date' => 'sometimes|required|date',
            'budget' => 'sometimes|required|numeric',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:2048',
            'status' => 'sometimes|required|string',
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

    public function destroy(Request $request, Report $report)
    {
        $this->filterByDivisionAccess($request->user(), $report);
        $report->delete();
        return response()->json(null, 204);
    }
}
