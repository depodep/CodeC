<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Show the unified login / welcome page.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Unified login handler: ID Number or Email with hashed or plain-text password for all roles.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = trim($request->username);

        // Students use usernames; admin and faculty may continue using email.
        $user = User::where(function ($query) use ($loginInput) {
                        $query->whereRaw('LOWER(username) = ?', [strtolower($loginInput)])
                            ->orWhereRaw('LOWER(email) = ?', [strtolower($loginInput)]);
                    })
                    ->first();

        // Older seeded/imported accounts may contain plaintext passwords. Only call
        // Hash::check for recognized password hashes because Laravel throws for
        // unsupported formats instead of returning false.
        $passwordMatches = false;
        $passwordWasPlaintext = false;
        if ($user) {
            $storedPassword = (string) $user->password;
            $passwordInfo = password_get_info($storedPassword);

            if (($passwordInfo['algo'] ?? 0) !== 0) {
                $passwordMatches = Hash::check($request->password, $storedPassword);
            } else {
                $passwordMatches = hash_equals($storedPassword, (string) $request->password);
                $passwordWasPlaintext = $passwordMatches;
            }
        }

        if ($user && $passwordMatches) {
            if ($passwordWasPlaintext) {
                $user->forceFill(['password' => Hash::make($request->password)])->save();
            }

            // Check kung active ang account
            if (isset($user->is_active) && ! $user->is_active) {
                return back()->withErrors([
                    'username' => 'Your account is deactivated. Please contact the administrator.'
                ])->onlyInput('username');
            }

            // Manu-manong i-authenticate ang session
            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();

            // Tukuyin ang role base sa role_id (1 = Admin, 2 = Faculty, 3 = Student, 4 = Super Admin Viewer)
            $actualRole = 'student';

            if (
                $user->role_id == 1 || 
                $user->role_id == 4 || 
                (is_object($user->role) && in_array(strtolower($user->role->name), ['admin', 'superadmin_viewer', 'director', 'management'])) || 
                (isset($user->role) && in_array(strtolower($user->role), ['admin', 'superadmin_viewer', 'director', 'management']))
            ) {
                $actualRole = 'admin';
            } elseif (
                $user->role_id == 2 || 
                (is_object($user->role) && in_array(strtolower($user->role->name), ['teacher', 'faculty'])) || 
                (isset($user->role) && in_array(strtolower($user->role), ['teacher', 'faculty']))
            ) {
                $actualRole = 'teacher';
            } elseif (
                $user->role_id == 3 || 
                (is_object($user->role) && strtolower($user->role->name) === 'student') || 
                (isset($user->role) && strtolower($user->role) === 'student')
            ) {
                $actualRole = 'student';
            }

            // Redirect sa kani-kanilang dashboard
            return match ($actualRole) {
                'admin'   => redirect()->route('admin.dashboard'),
                'teacher' => redirect()->route('teacher.dashboard'),
                'student' => redirect()->route('student.dashboard'),
                default   => redirect('/'),
            };
        }

        // Kapag hindi nagtugma
        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    /**
     * Logout handler.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}