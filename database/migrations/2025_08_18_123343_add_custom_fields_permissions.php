<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Check if permissions table exists before trying to create permissions
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            'tenant.custom_fields.view',
            'tenant.custom_fields.create',
            'tenant.custom_fields.edit',
            'tenant.custom_fields.delete',
        ];

        foreach ($permissions as $permission) {
            try {
                Permission::updateOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'scope' => 'tenant',
                ]);
            } catch (\Exception $e) {
                // Log error but don't fail the migration
                error_log("Failed to create permission {$permission}: ".$e->getMessage());
            }
        }
    }

    public function down(): void
    {
        $permissions = [
            'tenant.custom_fields.view',
            'tenant.custom_fields.create',
            'tenant.custom_fields.edit',
            'tenant.custom_fields.delete',
        ];

        Permission::whereIn('name', $permissions)
            ->where('scope', 'tenant')
            ->delete();
    }
};
