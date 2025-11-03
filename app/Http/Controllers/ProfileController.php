<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Rules\CurrentPassword;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Profile/Show', [
            'user' => $request->user()
        ]);
    }

    public function edit(Request $request)
    {
        return Inertia::render('Profile/Edit', [
            'user' => $request->user()
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('profile.edit')->with('message', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', new CurrentPassword],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.edit')->with('message', 'Password updated successfully!');
    }
}



