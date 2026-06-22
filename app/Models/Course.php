<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'level',
        'audience',
        'thumbnail',
        'duration_minutes',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /** Who the course is for (used for the audience badge). */
    public const AUDIENCES = [
        'all' => 'Everyone',
        'sales' => 'Sales',
        'technical' => 'Technical',
        'support' => 'Support',
    ];

    public function getAudienceLabelAttribute(): ?string
    {
        return $this->audience ? (self::AUDIENCES[$this->audience] ?? $this->audience) : null;
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function publishedLessons(): HasMany
    {
        return $this->lessons()->where('is_published', true);
    }
}
