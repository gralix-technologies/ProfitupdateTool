<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;
            
            return redirect()->intended('/dashboard')->with([
                'token' => $token,
                'user' => $user->load('roles.permissions')
            ]);
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    
    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('Viewer');

        Auth::login($user);
        $token = $user->createToken('auth-token')->plainTextToken;

        return redirect('/dashboard')->with([
            'token' => $token,
            'user' => $user->load('roles.permissions')
        ]);
    }

    
    public function logout(Request $request)
    {
        try {
            // Delete API tokens if user exists
            if ($request->user()) {
                $request->user()->tokens()->delete();
            }
            
            // Logout user
            Auth::logout();
            
            // Invalidate and regenerate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Return redirect response
            return redirect('/login')->with('message', 'Logged out successfully');
        } catch (\Exception $e) {
            // Log error but still redirect
            \Log::error('Logout error: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Logout completed with errors');
        }
    }

    
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('roles.permissions')
        ]);
    }
}


