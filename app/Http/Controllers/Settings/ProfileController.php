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

        // ── 1. Validate ALL fields up-front before touching the filesystem ──
        $rules = [
            'name'   => 'required|string|max:150',
            'email'  => 'required|email|unique:users,email,' . $user->id,
            'phone'  => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ];

        if ($request->filled('password')) {
            $rules['current_password'] = ['required', 'string', function ($attr, $value, $fail) use ($user) {
                if (! Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }];
            $rules['password'] = ['required', 'string', Password::min(8), 'confirmed'];
        }

        $validated = $request->validate($rules);

        // ── 2. Build the data array from validated fields only ──
        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        // ── 3. All validation passed — now safe to touch the filesystem ──
        if ($request->hasFile('avatar')) {
            // Store new file first so we never lose both files on a disk error
            $newPath = $request->file('avatar')->store('avatars', 'public');

            // Delete old avatar only after the new one is safely stored
            if ($user->avatar && $user->avatar !== $newPath) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $newPath;
        }

        // ── 4. Persist and refresh session ──
        $user->update($data);

        // Re-login so the session reflects the new avatar / name immediately
        auth()->setUser($user->fresh());

        return back()->with('success', 'Profile updated successfully.');
    }
}
