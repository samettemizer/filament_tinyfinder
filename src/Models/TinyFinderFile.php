<?php

namespace Stemizer\FilamentTinyFinder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TinyFinderFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tinyfinder_files';

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'basename',
        'extension',
        'mime_type',
        'size',
        'width',
        'height',
        'has_thumbnails',
        'is_private',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'has_thumbnails' => 'boolean',
        'is_private' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'url',
        'thumbnail_url',
        'formatted_size',
        'icon',
    ];

    /**
     * Get the user that owns the file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Get the file URL
     */
    public function getUrlAttribute(): string
    {
        $disk = config('filament-tinyfinder.storage.disk');
        $path = config('filament-tinyfinder.storage.path');

        return Storage::disk($disk)->url("{$path}/{$this->type}s/{$this->basename}");
    }

    /**
     * Get the thumbnail URL
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->type !== 'image') {
            return null;
        }

        if (!$this->has_thumbnails) {
            return $this->url;
        }

        $disk = config('filament-tinyfinder.storage.disk');
        $path = config('filament-tinyfinder.storage.path');

        return Storage::disk($disk)->url("{$path}/images/thumbs/medium_{$this->basename}");
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes === null) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $i = 0;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file icon based on extension
     */
    public function getIconAttribute(): string
    {
        return match ($this->extension) {
            'pdf' => 'heroicon-o-document-text',
            'doc', 'docx' => 'heroicon-o-document',
            'xls', 'xlsx' => 'heroicon-o-table-cells',
            'ppt', 'pptx' => 'heroicon-o-presentation-chart-bar',
            'zip', 'rar', 'gz', '7z' => 'heroicon-o-archive-box',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'heroicon-o-photo',
            'mp3' => 'heroicon-o-musical-note',
            'mp4', 'avi', 'mov' => 'heroicon-o-film',
            'txt', 'csv' => 'heroicon-o-document-text',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Check if user can access this file
     */
    public function canAccess(?int $userId = null): bool
    {
        if (!$this->is_private) {
            return true;
        }

        $userId = $userId ?? auth()->id();

        return $this->user_id === $userId;
    }

    /**
     * Scope to get only images
     */
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    /**
     * Scope to get only files
     */
    public function scopeFiles($query)
    {
        return $query->where('type', 'file');
    }

    /**
     * Scope to get only private files
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    /**
     * Scope to get only public files
     */
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    /**
     * Scope to get user's files
     */
    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function (self $file) {
            // Delete physical file
            $disk = config('filament-tinyfinder.storage.disk');
            $path = config('filament-tinyfinder.storage.path');

            Storage::disk($disk)->delete("{$path}/{$file->type}s/{$file->basename}");

            // Delete thumbnails if image
            if ($file->has_thumbnails && $file->type === 'image') {
                foreach (['small', 'medium', 'large'] as $size) {
                    Storage::disk($disk)->delete("{$path}/images/thumbs/{$size}_{$file->basename}");
                }
            }
        });
    }
}
