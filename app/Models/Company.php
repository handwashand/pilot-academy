<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Company extends Model
{
    protected $fillable = [
        'name',
        'region',
        'industry',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Partner staff in this company — learners only, never admins or creators. */
    public function students(): HasMany
    {
        return $this->users()->learners();
    }

    /** Certificates earned by this company's students. */
    public function certificates(): HasManyThrough
    {
        return $this->hasManyThrough(Certificate::class, User::class);
    }
}
