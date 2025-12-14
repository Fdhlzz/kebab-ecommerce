<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            $role = $user->getRoleNames()->first() ?? 'customer';

            $token = $user->createToken('dashboard-token')->plainTextToken;
            $token = $user->createToken('mobile-app-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'accessToken' => $token,
                'role' => $role,
            ]);
        }

        return response()->json([
            'message' => 'The provided credentials do not match our records.',
            'errors' => ['email' => ['Invalid credentials']]
        ], 401);
    }

    public function register(Request $request)
    {
        // 1. Validate Input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed', // expects 'password_confirmation'
        ]);

        // 2. Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 3. Assign Role (Ensure 'customer' role exists in DB!)
        $user->assignRole('customer');

        // 4. Generate Token
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Return Response
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }
    public function updateAddress(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:500',
            'district_id' => 'required|exists:indonesia_regions,code',
        ]);

        $user = $request->user();

        $user->update([
            'address' => $request->address,
            'district_id' => $request->district_id,
        ]);

        return response()->json([
            'message' => 'Address updated successfully',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Auth::guard('web')->logout();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
