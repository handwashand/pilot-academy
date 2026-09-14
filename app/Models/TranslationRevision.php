<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationRevision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'translation_id',
        'old_value',
        'new_value',
        'changed_by',
        'created_at',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(Translation::class);
    }
}
