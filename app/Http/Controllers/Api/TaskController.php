<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private function filterByDivisionAccess($user, $task)
    {
        if (!$user->isAdmin() && $user->division !== $task->division) {
            abort(403, 'Unauthorized action. Invalid division.');
        }
    }

    public function index(Request $request)
    {
        $query = Task::with(['assignedTo', 'assignedBy'])->forDivision($request->user());

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->has('division') && $request->user()->isAdmin()) {
            $query->where('division', $request->division);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'division' => ['required', Rule::in(['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
            'priority' => 'required|string',
            'status' => 'nullable|string',
        ]);

        if (!$request->user()->isAdmin() && $validated['division'] !== $request->user()->division) {
            return response()->json(['message' => 'Anda hanya bisa membuat task untuk divisi Anda sendiri.'], 403);
        }

        $validated['assigned_by'] = $request->user()->id;

        $task = Task::create($validated);

        return response()->json($task->load(['assignedTo', 'assignedBy']), 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->filterByDivisionAccess($request->user(), $task);
        return response()->json($task->load(['assignedTo', 'assignedBy']));
    }

    public function update(Request $request, Task $task)
    {
        $this->filterByDivisionAccess($request->user(), $task);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'sometimes|required|exists:users,id',
            'division' => ['sometimes', 'required', Rule::in(['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
            'priority' => 'sometimes|required|string',
            'status' => 'sometimes|required|string',
        ]);

        if (array_key_exists('division', $validated) && !$request->user()->isAdmin() && $validated['division'] !== $request->user()->division) {
            return response()->json(['message' => 'Anda tidak bisa mengubah task ke divisi lain.'], 403);
        }

        $task->update($validated);

        return response()->json($task->load(['assignedTo', 'assignedBy']));
    }

    public function destroy(Request $request, Task $task)
    {
        $this->filterByDivisionAccess($request->user(), $task);
        $task->delete();
        return response()->json(null, 204);
    }
}
