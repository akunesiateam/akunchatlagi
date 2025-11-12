<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seed the embedded signup feature
        DB::table('features')->insert([
            'name' => 'Embedded SignUp',
            'slug' => 'emb_signup',
            'description' => 'Allow users to sign up via Embedded SignUp',
            'type' => 'limit',
            'display_order' => 10,
            'default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
