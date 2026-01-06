<?php

namespace Modules\ThemeBuilder\Models;

use App\Models\BaseModel;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $theme_id
 * @property string $name
 * @property string $filename
 * @property string $path
 * @property string $url
 * @property string $type
 * @property string|null $mime_type
 * @property int|null $size
 * @property int|null $width
 * @property int|null $height
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Theme $theme
 */
class ThemeAsset extends BaseModel
{
    use HasFactory;

    protected $table = 'theme_assets';

    protected $fillable = [
        'theme_id',
        'name',
        'filename',
        'path',
        'url',
        'type',
        'mime_type',
        'size',
        'width',
        'height',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Get the theme that owns the asset
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get the full URL for the asset
     */
    public function getFullUrlAttribute(): string
    {
        return $this->url;
    }

    /**
     * Delete the asset file when the model is deleted
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (ThemeAsset $asset) {
            if (Storage::disk('public')->exists($asset->path)) {
                Storage::disk('public')->delete($asset->path);
            }
        });
    }
}
