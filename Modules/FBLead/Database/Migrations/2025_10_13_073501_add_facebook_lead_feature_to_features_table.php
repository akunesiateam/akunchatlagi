<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // add feature for facebook lead integration
        if (! DB::table('features')->where('slug', 'facebook_lead')->exists()) {
            DB::table('features')->insert([
                'name' => 'Facebook Lead Integration',
                'slug' => 'facebook_lead',
                'description' => 'Allow users to connect Facebook pages and collect leads from Facebook Lead Ads',
                'type' => 'boolean',
                'display_order' => 55,
                'default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('features')->where('slug', 'facebook_lead')->delete();
    }
};
