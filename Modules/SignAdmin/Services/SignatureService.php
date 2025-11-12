<?php

namespace Modules\SignAdmin\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SignatureService
{
    /**
     * Get user signature based on their role.
     *
     * @param int $userId
     * @param int $tenantId
     * @return string|null
     */
    public function getUserSignature($userId, $tenantId): ?string
    {
        $user = User::where('id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$user || !$user->role_id) {
            return null;
        }

        // Query direct ke table roles
        $role = DB::table('roles')
            ->where('id', $user->role_id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$role) {
            return null;
        }

        // --- KODE BARU (Langkah Terbaik) ---
        $firstName = trim($user->firstname); // Kita hanya ambil nama depan
        $roleName = $role->name;
        
        // Gunakan $firstName, bukan $fullName
        return "*{$firstName} ({$roleName})*:";
    }
}