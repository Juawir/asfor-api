<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function authorizeAdmin($user)
    {
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized action. Only admins can manage users.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request->user());
        return response()->json(User::all());
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request->user());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'user'])],
            'division' => ['nullable', Rule::in(['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        // Only default users need to join a division
        if ($validated['role'] === 'user' && empty($validated['division'])) {
            return response()->json(['message' => 'Role user harus memiliki divisi.'], 422);
        }

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function show(Request $request, User $user)
    {
        $this->authorizeAdmin($request->user());
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin($request->user());

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|required|string|min:8',
            'role' => ['sometimes', 'required', Rule::in(['admin', 'user'])],
            'division' => ['nullable', Rule::in(['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'])],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $role = $validated['role'] ?? $user->role;
        $division = array_key_exists('division', $validated) ? $validated['division'] : $user->division;

        if ($role === 'user' && empty($division)) {
            return response()->json(['message' => 'Role user harus memiliki divisi.'], 422);
        }

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeAdmin($request->user());
        $user->delete();
        return response()->json(null, 204);
    }
}
