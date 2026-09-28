<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // 1. Kuhanin ang numeric role_id
        $roleId = null;
        if (isset($user->role_id) && is_numeric($user->role_id)) {
            $roleId = (int) $user->role_id;
        } elseif (isset($user->role) && is_object($user->role) && isset($user->role->id)) {
            $roleId = (int) $user->role->id;
        } elseif (is_numeric($user->role ?? null)) {
            $roleId = (int) $user->role;
        }

        // 2. Kuhanin ang string role_name
        $roleName = '';
        if (isset($user->role)) {
            if (is_string($user->role)) {
                $roleName = strtolower(trim($user->role));
            } elseif (is_object($user->role) && isset($user->role->name)) {
                $roleName = strtolower(trim($user->role->name));
            }
        }

        $target = strtolower(trim($role));

        // 3. Eksaktong mapping para sa apat na roles
        $targetMap = [
            'admin'      => [1, 'admin'],
            'director'   => [4, 'director', 'management'],
            'management' => [4, 'management', 'director'],
            'faculty'    => [2, 'faculty', 'teacher'],
            'teacher'    => [2, 'faculty', 'teacher'],
            'student'    => [3, 'student'],
        ];

        $allowedItems = $targetMap[$target] ?? [$target];

        // 4. I-verify ang access
        if (
            ($roleId !== null && in_array($roleId, array_filter($allowedItems, 'is_int'), true)) ||
            ($roleName !== '' && in_array($roleName, array_filter($allowedItems, 'is_string'), true))
        ) {
            return $next($request);
        }

        // 5. Redirect kapag walang access sa ruta
        return match ($roleId) {
            1       => redirect()->route('admin.dashboard'),
            2       => redirect()->route('teacher.schedules'),
            3       => redirect()->route('student.dashboard'),
            4       => redirect()->route('management.dashboard'), 
            default => redirect()->route('login'),
        };
    }
}
