<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('is_admin_context')) {
    /**
     * Check if the current request is in admin context.
     *
     * @return bool Returns true if request is to an admin route or user is admin
     */
    function is_admin_context(): bool
    {
        // Check if we're in admin routes
        if (request()->is('admin*')) {
            return true;
        }

        // Check if user is admin (alternative check)
        $user = Auth::user();
        if ($user && $user->user_type === 'admin') {
            return true;
        }

        return false;
    }
}

if (! function_exists('is_admin')) {
    /**
     * Check if the current authenticated user is the main super admin.
     *
     * @return bool Returns true if user is the main super admin (is_admin = 1)
     */
    function is_admin(): bool
    {
        $user = Auth::user();

        // Check if user exists and has is_admin flag set to true
        if ($user && isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        return false;
    }
}
