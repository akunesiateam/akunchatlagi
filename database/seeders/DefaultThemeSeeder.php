<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DefaultThemeSeeder extends Seeder
{
    protected $themes_folder;

    public function __construct()
    {
        $this->themes_folder = resource_path('views/themes');
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = $this->getThemesFromFolder();

        // Check if any theme is currently active in DB
        $hasActiveTheme = Theme::where('active', 1)->exists();

        foreach ($themes as $theme) {
            if (isset($theme->folder)) {
                $theme_exists = Theme::where('folder', $theme->folder)->first();
                $themeImagePath = 'themes/'.$theme->folder.'/theme.jpg';
                $localImagePath = resource_path('views/themes/'.$theme->folder.'/theme.jpg');

                // Copy image to public storage
                if (file_exists($localImagePath)) {
                    Storage::disk('public')->put($themeImagePath, file_get_contents($localImagePath));
                }

                $version = $theme->version ?? '1.0';
                $name = $theme->name ?? ucfirst($theme->folder);
                $type = $theme->type ?? 'core';
                $payload = $theme->payload ?? null;
                $theme_html = $theme->theme_html ?? null;
                $theme_css = $theme->theme_css ?? null;

                // Determine active status: only 1 theme can be active if none exists
                $isActive = $hasActiveTheme ? 0 : 1;

                if (! $theme_exists) {
                    Theme::create([
                        'name' => $name,
                        'folder' => $theme->folder,
                        'version' => $version,
                        'theme_url' => $themeImagePath,
                        'active' => $isActive,
                        'type' => $type,
                        'payload' => $payload,
                        'theme_html' => $theme_html,
                        'theme_css' => $theme_css,
                    ]);

                    // Once one theme becomes active, mark flag true
                    if ($isActive === 1) {
                        $hasActiveTheme = true;
                    }

                    if (config('themes.publish_assets', true) && method_exists($this, 'publishAssets')) {
                        $this->publishAssets($theme->folder);
                    }
                } else {
                    // Update existing theme, preserve its current active state
                    $theme_exists->update([
                        'name' => $name,
                        'version' => $version,
                        'theme_url' => $themeImagePath,
                        'active' => $theme_exists->active ?? 0,
                    ]);

                    if (config('themes.publish_assets', true) && method_exists($this, 'publishAssets')) {
                        $this->publishAssets($theme->folder);
                    }
                }
            }
        }
    }

    /**
     * Get all valid theme.json files from the folder.
     */
    private function getThemesFromFolder()
    {
        $themes = [];

        if (! file_exists($this->themes_folder)) {
            mkdir($this->themes_folder, 0755, true);
        }

        $folders = scandir($this->themes_folder);

        foreach ($folders as $folder) {
            if (in_array($folder, ['.', '..'])) {
                continue;
            }

            $json_file = $this->themes_folder.'/'.$folder.'/theme.json';
            if (file_exists($json_file)) {
                $data = json_decode(file_get_contents($json_file), true);
                $data['folder'] = $folder;
                $themes[$folder] = (object) $data;
            }
        }

        return (object) $themes;
    }

    /**
     * Optional: publish theme assets if needed.
     */
    private function publishAssets($folder)
    {
        $source = resource_path('views/themes/'.$folder.'/assets');
        $destination = public_path('themes/'.$folder.'/assets');

        if (File::exists($source)) {
            File::copyDirectory($source, $destination);
        }
    }
}
