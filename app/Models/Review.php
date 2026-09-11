<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'organization_id',
        'external_id',
        'author',
        'rating',
        'text',
        'review_date',
    ];

    protected $casts = [
        'rating' => 'integer',
        'review_date' => 'datetime',
    ];

    /** Отзыв принадлежит одной организации (обратная сторона hasMany). */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
