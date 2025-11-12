<?php

namespace Modules\ThemeBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themeName = 'dragify';

        // Path to theme files inside the module
        $themeFolder = module_path('ThemeBuilder', "resources/themes/{$themeName}");

        // File paths
        $payloadPath = "{$themeFolder}/theme_payload.txt";
        $htmlPath = "{$themeFolder}/theme_html.txt";
        $cssPath = "{$themeFolder}/theme_css.txt";

        // Read contents safely
        $payload = File::exists($payloadPath) ? File::get($payloadPath) : null;
        $themeHtml = File::exists($htmlPath) ? File::get($htmlPath) : null;
        $themeCss = File::exists($cssPath) ? File::get($cssPath) : null;

        $exists = DB::table('themes')
            ->where('name', 'dragify')
            ->where('folder', 'dragify')
            ->exists();

        if (! $exists) {
            DB::table('themes')->insert([
                'name' => 'Dragify',
                'payload' => $payload,
                'theme_html' => $themeHtml,
                'theme_css' => $themeCss,
                'type' => 'custom',
                'folder' => 'dragify',
                'active' => 0,
                'version' => '1.0',
                'theme_url' => 'themes/dragify/theme.png',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
