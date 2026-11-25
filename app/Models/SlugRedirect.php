<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An old slug of a course or lesson, and the record it now belongs to. See RemembersOldSlugs. */
class SlugRedirect extends Model
{
    protected $fillable = ['model_type', 'model_id', 'slug'];
}
