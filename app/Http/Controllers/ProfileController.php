<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        if ((int) $request->user()->role_id === 3) {
            return view('profile.student-edit', [
                'user' => $request->user(),
            ]);
        }

        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information and password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isStudent = (int) $user->role_id === 3;

        if ($isStudent) {
            $validated = $request->validate([
                'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique(User::class)->ignore($user->id)],
                'current_password' => ['nullable', 'required_with:password'],
                'password' => ['nullable', 'min:8', 'confirmed'],
            ]);

            if ($request->filled('password')) {
                if ($user->password !== $request->current_password) {
                    return Redirect::back()->withErrors(['current_password' => 'The current password does not match our records.'])->withInput();
                }
                $user->password = $request->password;
            }

            $user->username = strtolower($validated['username']);
            $user->save();

            return Redirect::back()->with('success', 'Student account credentials updated successfully.');
        }

        // 1. Validation rules para sa Profile Details
        $rules = [
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'phone_number'     => ['nullable', 'string', 'max:20'],
            'username'         => $isStudent
                ? ['required', 'string', 'max:100', 'alpha_dash', Rule::unique(User::class)->ignore($user->id)]
                : ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique(User::class)->ignore($user->id)],
            'email'            => $isStudent
                ? ['nullable', 'string', 'max:255']
                : ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password'],
            'password'         => ['nullable', 'min:8', 'confirmed'],
        ];

        $validated = $request->validate($rules);

        // 2. Plain Text Verification para sa Current Password
        if ($request->filled('password')) {
            if ($user->password !== $request->current_password) {
                return Redirect::back()->withErrors([
                    'current_password' => 'The current password does not match our records.'
                ])->withInput();
            }

            // Plain text password assignment
            $user->password = $request->password;
        }

        // 3. I-assign ang updated fields (Natural Casing & Lowercase Email)
        $user->first_name   = $validated['first_name'];
        $user->last_name    = $validated['last_name'];
        $user->username     = $isStudent ? strtolower($validated['username']) : ($validated['username'] ?? $user->username);
        $user->email        = $isStudent ? null : strtolower($validated['email']);
        $user->phone_number = $validated['phone_number'] ?? null;

        // I-reset ang email verification kung nabago ang email
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Proteksyon: Panatilihing admin ang role
        if ($user->id === 1 || $user->role_id === 1 || ($user->role ?? '') === 'admin') {
            $user->role_id = 1;
            if (isset($user->role)) {
                $user->role = 'admin';
            }
        }

        $user->save();

        return Redirect::back()->with('success', 'Profile and credentials updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Proteksyon: Bawal burahin ang permanent admin account
        if ($user->id === 1 || $user->role_id === 1 || ($user->role ?? '') === 'admin' || str_contains(strtolower($user->email), 'admin')) {
            return Redirect::back()->withErrors([
                'error' => 'The permanent system administrator account cannot be deleted.'
            ]);
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required'],
        ]);

        // Plain text check bago burahin
        if ($user->password !== $request->password) {
            return Redirect::back()->withErrors([
                'password' => 'Incorrect password.'
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}