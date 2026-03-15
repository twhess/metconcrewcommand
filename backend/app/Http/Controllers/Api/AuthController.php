<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Load user's roles with their permissions
        $user->load('roles.permissions');

        $isSecure = app()->environment('production');
        $cookie = cookie(
            'auth_token',
            $token,
            (int) config('sanctum.expiration', 480),
            '/',
            null,
            $isSecure,  // secure flag — HTTPS only in production
            true,       // httpOnly — not accessible via JS
            false,      // raw
            $isSecure ? 'None' : 'Lax'  // sameSite
        );

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles,
            ]
        ], 200)->withCookie($cookie);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        $cookie = cookie()->forget('auth_token');

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200)->withCookie($cookie);
    }

    public function me(Request $request)
    {
        // Load user's roles with their permissions
        $request->user()->load('roles.permissions');

        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'roles' => $request->user()->roles,
            ]
        ], 200);
    }
}
