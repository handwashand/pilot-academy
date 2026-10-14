<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A product or module in the Pilot ecosystem (GARM, PTM, …). Courses belong to
 * one, and creators are made responsible for the training of one or more.
 */
class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Deleting a product has always left its content with no product — the
     * database did it when the row went. The row stays now, so this does it:
     * otherwise every form naming the product would refuse to save, pointing
     * at a product nobody can choose. Restoring the product brings it back,
     * not the filing of what was under it.
     */
    protected static function booted(): void
    {
        static::deleted(function (Product $product): void {
            foreach ([Course::class, CaseStudy::class, Tutorial::class, Webinar::class] as $model) {
                $model::withTrashed()->where('product_id', $product->id)->update(['product_id' => null]);
            }
        });
    }

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
