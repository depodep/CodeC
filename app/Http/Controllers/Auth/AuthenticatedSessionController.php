<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Kung ang unified login page mo ay welcome.blade.php, palitan ng: return view('welcome');
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // 1 = Admin, 2 = Teacher/Faculty, 3 = Student
        // (Palitan ang numbers kung iba ang IDs sa roles table mo)
        if ($user->role_id == 1) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role_id == 2) {
            return redirect()->route('teacher.dashboard');
        } elseif ($user->role_id == 3) {
            return redirect()->route('student.dashboard');
        }

        // Kung sakaling may role string accessor ka sa User model:
        if (isset($user->role)) {
            if ($user->role === 'admin') return redirect()->route('admin.dashboard');
            if ($user->role === 'teacher') return redirect()->route('teacher.schedules');
            if ($user->role === 'student') return redirect()->route('student.dashboard');
        }

        // Safety fallback kapag walang valid role
        Auth::logout();
        return redirect()->route('login')->withErrors(['username' => 'This account does not have a valid role assigned.']);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}