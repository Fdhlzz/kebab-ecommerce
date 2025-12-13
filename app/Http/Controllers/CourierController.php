<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CourierController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'kurir')->latest();

        if ($request->has('status') && $request->status === 'available') {
            $query->where('courier_status', 'available');
        }
        $couriers = $query->select('id', 'name', 'email', 'courier_status', 'created_at')->get();

        return response()->json($couriers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'kurir',
        ]);

        return response()->json([
            'message' => 'Akun kurir berhasil dibuat',
            'data' => $user
        ]);
    }

    public function update(Request $request, User $user)
    {

        if ($user->role !== 'kurir') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json(['message' => 'Data kurir diperbarui']);
    }

    public function destroy(User $user)
    {
        if ($user->role !== 'kurir') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Akun kurir dihapus']);
    }
}
