<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\InventoryItem;
use App\Models\Lab;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryItemController extends Controller
{
    use ApiResponse;

    private function authorizeEdit(Lab $lab, Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin() || $user->division === 'IT Support') {
            return;
        }

        $isPic = $lab->pics()->where('users.id', $user->id)->exists();
        if ($isPic) {
            return;
        }

        abort(403, 'Unauthorized action. You must be IT Support or assigned to this lab to modify items.');
    }

    public function store(Request $request, Lab $lab)
    {
        $this->authorizeEdit($lab, $request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'condition' => ['required', Rule::in(['Baik', 'Rusak Ringan', 'Rusak Berat'])],
            'notes' => 'nullable|string',
        ]);

        $item = $lab->inventoryItems()->create($validated);

        return $this->successResponse($item, 'Item created successfully', 201);
    }

    public function update(Request $request, Lab $lab, InventoryItem $item)
    {
        $this->authorizeEdit($lab, $request);

        if ($item->lab_id !== $lab->id) {
            abort(404, 'Item not found in this lab.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'quantity' => 'sometimes|required|integer|min:1',
            'condition' => ['sometimes', 'required', Rule::in(['Baik', 'Rusak Ringan', 'Rusak Berat'])],
            'notes' => 'nullable|string',
        ]);

        $item->update($validated);

        return $this->successResponse($item, 'Item updated successfully');
    }

    public function destroy(Request $request, Lab $lab, InventoryItem $item)
    {
        $this->authorizeEdit($lab, $request);

        if ($item->lab_id !== $lab->id) {
            abort(404, 'Item not found in this lab.');
        }

        $item->delete();
        return response()->json(null, 204);
    }
}
