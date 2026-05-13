<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Task;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    use ApiResponse;

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

        return $this->successResponse($query->get(), 'Tasks retrieved successfully');
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
            return $this->errorResponse('Anda hanya bisa membuat task untuk divisi Anda sendiri.', 403);
        }

        $validated['assigned_by'] = $request->user()->id;

        $task = Task::create($validated);

        // Notify the assigned user
        AppNotification::notify(
            $validated['assigned_to'],
            'task',
            '📋 Tugas Baru Ditugaskan',
            'Anda mendapat tugas baru: "' . $task->title . '" dari ' . $request->user()->name,
            ['task_id' => $task->id]
        );

        return $this->successResponse($task->load(['assignedTo', 'assignedBy']), 'Task created successfully', 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->filterByDivisionAccess($request->user(), $task);
        return $this->successResponse($task->load(['assignedTo', 'assignedBy']), 'Task retrieved successfully');
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
            return $this->errorResponse('Anda tidak bisa mengubah task ke divisi lain.', 403);
        }

        $oldStatus     = $task->status;
        $oldAssignedTo = $task->assigned_to;
        $task->update($validated);
        $task->load(['assignedTo', 'assignedBy']);

        $actor = $request->user();

        // Notif 1: Perubahan status → beritahu assigner (jika bukan diri sendiri)
        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            $assignedById = $task->assigned_by;
            if ($assignedById && $assignedById !== $actor->id) {
                $statusLabel = match($validated['status']) {
                    'done'       => '✅ Selesai',
                    'inProgress' => '🔄 Sedang Dikerjakan',
                    default      => '📋 Kembali ke To Do',
                };
                AppNotification::notify(
                    $assignedById,
                    'task_status',
                    'Update Tugas: ' . $task->title,
                    $actor->name . ' memperbarui status tugas "' . $task->title . '" menjadi ' . $statusLabel . '.',
                    ['task_id' => $task->id]
                );
            }
        }

        // Notif 2: Reassignment → beritahu orang yang baru ditugaskan (jika berubah & bukan diri sendiri)
        if (isset($validated['assigned_to']) && (int)$validated['assigned_to'] !== (int)$oldAssignedTo && (int)$validated['assigned_to'] !== $actor->id) {
            AppNotification::notify(
                $validated['assigned_to'],
                'task',
                '📋 Tugas Ditugaskan Ulang',
                $actor->name . ' menugaskan tugas "' . $task->title . '" kepada Anda.',
                ['task_id' => $task->id]
            );
        }

        return $this->successResponse($task, 'Task updated successfully');
    }

    public function destroy(Request $request, Task $task)
    {
        $this->filterByDivisionAccess($request->user(), $task);
        $task->delete();
        return response()->json(null, 204);
    }
}
