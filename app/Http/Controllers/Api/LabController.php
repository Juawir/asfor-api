<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Lab;
use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $isITSupportOrAdmin = $user->isAdmin() || $user->division === 'IT Support';

        // Load labs with PIC count and items count
        $query = Lab::with(['pics' => function ($q) {
            $q->select('users.id', 'users.name', 'users.division');
        }])->withCount('inventoryItems');

        if (!$isITSupportOrAdmin) {
            // Regular user only sees labs they are PIC of
            $query->whereHas('pics', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        return $this->successResponse($query->get(), 'Labs retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $user->division !== 'IT Support') {
            abort(403, 'Unauthorized action. Only IT Support or Admin can create labs.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $lab = Lab::create($validated);
        $lab->loadCount('inventoryItems');

        return $this->successResponse($lab, 'Lab created successfully', 201);
    }

    public function show(Request $request, Lab $lab)
    {
        $user = $request->user();
        $isITSupportOrAdmin = $user->isAdmin() || $user->division === 'IT Support';
        $isPic = $lab->pics()->where('users.id', $user->id)->exists();

        if (!$isITSupportOrAdmin && !$isPic) {
            abort(403, 'Unauthorized access to this lab.');
        }

        $lab->load(['pics' => function ($q) {
            $q->select('users.id', 'users.name', 'users.division');
        }, 'inventoryItems']);

        return $this->successResponse($lab, 'Lab retrieved successfully');
    }

    public function assignPics(Request $request, Lab $lab)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $user->division !== 'IT Support') {
            abort(403, 'Unauthorized action. Only IT Support or Admin can assign PICs.');
        }

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $oldUserIds = $lab->pics()->pluck('users.id')->toArray();
        $newUserIds = $request->user_ids;
        $addedUserIds = array_diff($newUserIds, $oldUserIds);

        $lab->pics()->sync($newUserIds);

        // Generate Task for newly assigned users
        $adminUserId = $user->id;
        foreach ($addedUserIds as $userId) {
            $assignedUser = User::find($userId);
            if ($assignedUser) {
                \App\Models\Task::create([
                    'title' => 'Inventarisasi Lab ' . $lab->name,
                    'description' => 'Tugas melaksanakan inventaris barang di ' . $lab->name . '. Mohon laporkan hasilnya kepada tim IT Support.',
                    'assigned_to' => $userId,
                    'assigned_by' => $adminUserId,
                    'division' => $assignedUser->division,
                    'priority' => 'high',
                    'status' => 'pending'
                ]);

                // Notify assigned PIC
                AppNotification::notify(
                    $userId,
                    'lab_assignment',
                    '🔬 Penugasan Lab Baru',
                    'Anda ditugaskan sebagai Penanggung Jawab di ' . $lab->name . '. Silakan mulai menginventaris barang.',
                    ['lab_id' => $lab->id, 'lab_name' => $lab->name]
                );
            }
        }

        return $this->successResponse(null, 'PICs assigned and tasks created successfully');
    }

    public function getInventoryUsers(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $user->division !== 'IT Support') {
            abort(403, 'Unauthorized action.');
        }

        $users = User::select('id', 'name', 'division', 'role')->get();
        return $this->successResponse($users, 'Users retrieved successfully');
    }
    public function destroy(Request $request, Lab $lab)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $user->division !== 'IT Support') {
            abort(403, 'Unauthorized action. Only IT Support or Admin can delete labs.');
        }

        // Hapus barang inventaris dan relasi PIC terlebih dahulu
        $lab->inventoryItems()->delete();
        $lab->pics()->detach();
        $lab->delete();

        return response()->json(null, 204);
    }
}
