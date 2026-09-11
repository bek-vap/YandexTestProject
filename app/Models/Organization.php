<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /**
     * Поля, которые можно заполнять массово (Organization::create([...])).
     * Всё, чего тут нет, защищено от случайной записи из запроса.
     */
    protected $fillable = [
        'user_id',
        'yandex_url',
        'name',
        'rating',
        'ratings_count',
        'reviews_count',
        'status',
        'last_error',
        'parsed_at',
    ];

    /**
     * Приведение типов: из БД придёт строка, а мы получим удобный тип.
     */
    protected $casts = [
        'rating' => 'decimal:2',
        'ratings_count' => 'integer',
        'reviews_count' => 'integer',
        'parsed_at' => 'datetime',
    ];

    /** Владелец карточки (обратная сторона: организация принадлежит юзеру). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** У организации много отзывов (one-to-many). */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** У организации много снимков истории (one-to-many). */
    public function snapshots(): HasMany
    {
        return $this->hasMany(OrganizationSnapshot::class);
    }
}
