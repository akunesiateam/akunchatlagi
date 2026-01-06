<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. add feature for ecommerce webhooks
        if (! DB::table('features')->where('slug', 'ecommerce_webhooks')->exists()) {
            DB::table('features')->insert([
                'name' => 'Ecommerce Webhooks',
                'slug' => 'ecommerce_webhooks',
                'description' => 'Allow users to create and manage number of webhooks for ecommerce events',
                'type' => 'limit',
                'display_order' => 50,
                'default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. add permissions for ecommerce webhooks
        $permissions = [
            'tenant.ecommerce_webhook.create',
            'tenant.ecommerce_webhook.edit',
            'tenant.ecommerce_webhook.view',
            'tenant.ecommerce_webhook.delete',
        ];

        foreach ($permissions as $permission) {
            if (! DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists()) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'scope' => 'tenant',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
