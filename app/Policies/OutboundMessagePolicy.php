<?php

namespace App\Policies;

use App\Models\OutboundMessage;
use App\Models\User;

class OutboundMessagePolicy
{
    /**
     * Perform pre-authorization checks. Admins bypass all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        $roleId = (int) ($user->role_id ?? ($user->role->id ?? 0));
        $roleName = strtolower(is_object($user->role) ? ($user->role->name ?? '') : ($user->role ?? ''));

        if ($roleId === 1 || $roleName === 'admin') {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any outbound messages.
     */
    public function viewAny(User $user): bool
    {
        $roleId = (int) ($user->role_id ?? ($user->role->id ?? 0));
        $roleName = strtolower(is_object($user->role) ? ($user->role->name ?? '') : ($user->role ?? ''));

        return in_array($roleId, [1, 2, 4], true) || in_array($roleName, ['admin', 'teacher', 'faculty', 'management', 'director'], true);
    }

    /**
     * Determine whether the user can view the specific outbound message.
     */
    public function view(User $user, OutboundMessage $message): bool
    {
        $roleId = (int) ($user->role_id ?? ($user->role->id ?? 0));
        $roleName = strtolower(is_object($user->role) ? ($user->role->name ?? '') : ($user->role ?? ''));

        // Admin can view any
        if ($roleId === 1 || $roleName === 'admin') {
            return true;
        }

        // Teachers can view messages they sent
        if ($message->sender_user_id === $user->id) {
            return true;
        }

        // Teachers can view messages regarding students in their section
        if ($message->student && !empty($user->section) && $message->student->section === $user->section) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create/send outbound messages.
     */
    public function create(User $user): bool
    {
        $roleId = (int) ($user->role_id ?? ($user->role->id ?? 0));
        $roleName = strtolower(is_object($user->role) ? ($user->role->name ?? '') : ($user->role ?? ''));

        return in_array($roleId, [1, 2], true) || in_array($roleName, ['admin', 'teacher', 'faculty'], true);
    }

    /**
     * Determine whether the user can update the message record.
     */
    public function update(User $user, OutboundMessage $message): bool
    {
        return false; // Only Admin (handled in before)
    }

    /**
     * Determine whether the user can delete the message record.
     */
    public function delete(User $user, OutboundMessage $message): bool
    {
        return false; // Only Admin (handled in before)
    }
}
