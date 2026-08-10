<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A product or module in the Pilot ecosystem (GARM, PTM, …). Courses belong to
 * one, and creators are made responsible for the training of one or more.
 */
class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /** The creators responsible for this product's training. */
    public function creators(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps()->orderBy('name');
    }
}
