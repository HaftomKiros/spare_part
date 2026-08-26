<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form for the currently authenticated user.
     */
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the authenticated user's profile.
     * Role and status are intentionally excluded — only the user's own
     * personal details, avatar, and password can be changed here.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'  => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        // Avatar upload — saved to users.avatar, stored under storage/app/public/avatars
        if ($request->hasFile('avatar')) {
            $request->validate(['avatar' => 'image|max:2048']);

            // Delete old avatar if it exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Password change is optional but requires the current password first
        if ($request->filled('password')) {
            $request->validate([
                'current_password' => ['required', 'string', function ($attr, $value, $fail) use ($user) {
                    if (! Hash::check($value, $user->password)) {
                        $fail('The current password is incorrect.');
                    }
                }],
                'password' => ['required', 'string', Password::min(8), 'confirmed'],
            ]);

            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }
}
