<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'tenant.custom_fields.view',
            'tenant.custom_fields.create',
            'tenant.custom_fields.edit',
            'tenant.custom_fields.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
                'scope' => 'tenant',
            ]);
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
