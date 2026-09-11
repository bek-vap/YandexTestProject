<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSnapshot extends Model
{
    protected $fillable = [
        'organization_id',
        'payload',
    ];

    protected $casts = [
        // 'array' — payload хранится в БД как JSON-строка,
        // а в коде мы получаем/пишем обычный PHP-массив. Laravel сам конвертирует.
        'payload' => 'array',
    ];

    /** Снимок принадлежит одной организации. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
