<?php

namespace Modules\ThemeBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ThemeBuilder\Models\ThemeAsset;

class AssetUploadController extends Controller
{
    /**
     * Handle asset uploads for GrapesJS editor
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request, Theme $theme)
    {
        $request->validate([
            'files.*' => 'required|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
        ]);

        $uploadedAssets = [];

        if ($request->hasFile('files')) {
            $files = $request->file('files');

            // Ensure it's an array even for single file uploads
            if (! is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                // Generate unique filename
                $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

                // Store in theme-specific folder
                $path = $file->storeAs(
                    "themes/{$theme->id}/assets",
                    $filename,
                    'public'
                );

                $url = Storage::url($path);

                // Get image dimensions if it's an image
                $width = null;
                $height = null;
                try {
                    $fullPath = Storage::disk('public')->path($path);
                    if (function_exists('getimagesize')) {
                        $imageInfo = getimagesize($fullPath);
                        if ($imageInfo !== false) {
                            $width = $imageInfo[0];
                            $height = $imageInfo[1];
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore errors getting image size
                }

                // Store asset info in database
                $themeAsset = ThemeAsset::create([
                    'theme_id' => $theme->id,
                    'name' => $file->getClientOriginalName(),
                    'filename' => $filename,
                    'path' => $path,
                    'url' => $url,
                    'type' => 'image',
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'width' => $width,
                    'height' => $height,
                ]);

                // Create asset object for GrapesJS
                $asset = [
                    'id' => $themeAsset->id,
                    'src' => $url,
                    'type' => 'image',
                    'height' => $height,
                    'width' => $width,
                    'name' => $file->getClientOriginalName(),
                ];

                $uploadedAssets[] = $asset;
            }
        }

        // Return response in format expected by GrapesJS
        return response()->json([
            'data' => $uploadedAssets,
        ]);
    }

    /**
     * Get all assets for a theme
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Theme $theme)
    {
        $themeAssets = ThemeAsset::where('theme_id', $theme->id)->get();

        $assets = $themeAssets->map(function ($asset) {
            return [
                'id' => $asset->id,
                'src' => $asset->url,
                'type' => $asset->type,
                'name' => $asset->name,
                'width' => $asset->width,
                'height' => $asset->height,
            ];
        });

        return response()->json([
            'data' => $assets,
        ]);
    }

    /**
     * Delete an asset
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, Theme $theme)
    {
        $request->validate([
            'src' => 'required|string',
        ]);

        $src = $request->input('src');

        // Find the asset by URL
        $asset = ThemeAsset::where('theme_id', $theme->id)
            ->where('url', $src)
            ->first();

        if ($asset) {
            $asset->delete(); // This will also delete the file due to the boot method

            return response()->json([
                'success' => true,
                'message' => 'Asset deleted successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Asset not found or unauthorized',
        ], 404);
    }
}
