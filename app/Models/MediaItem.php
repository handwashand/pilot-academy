<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'path',
    ];

    /**
     * A deleted image stops being anyone's cover, as it did when the database
     * cleared the reference itself — see Product::booted() for why this is
     * done by hand now. Restoring the image puts it back in the library, not
     * back on the lessons that used it.
     */
    protected static function booted(): void
    {
        static::deleted(function (MediaItem $item): void {
            Lesson::withTrashed()->where('media_item_id', $item->id)->update(['media_item_id' => null]);
            CaseStudy::withTrashed()->where('cover_media_item_id', $item->id)->update(['cover_media_item_id' => null]);
            CaseStudy::withTrashed()->where('diagram_media_item_id', $item->id)->update(['diagram_media_item_id' => null]);
            Webinar::withTrashed()->where('cover_media_item_id', $item->id)->update(['cover_media_item_id' => null]);
        });
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }
}
