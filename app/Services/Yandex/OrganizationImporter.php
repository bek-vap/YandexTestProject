<?php

namespace App\Services\Yandex;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrganizationImporter
{
    /**
     * Сохраняет данные парсера в БД: организация + отзывы + снимок истории.
     */
    public function import(Organization $organization, array $data): void
    {
        DB::transaction(function () use ($organization, $data) {
            // обновляем саму карточку
            $organization->update([
                'name' => $data['name'] ?? null,
                'rating' => $data['rating'] ?? null,
                'ratings_count' => $data['ratingsCount'] ?? null,
                'reviews_count' => $data['reviewsCount'] ?? null,
                'status' => 'done',
                'last_error' => null,
                'parsed_at' => now(),
            ]);

            $this->saveReviews($organization, $data['reviews']);

            // снимок текущих цифр — чтобы потом видеть, что изменилось
            $organization->snapshots()->create([
                'payload' => [
                    'rating' => $data['rating'] ?? null,
                    'ratings_count' => $data['ratingsCount'] ?? null,
                    'reviews_count' => $data['reviewsCount'] ?? null,
                    'collected' => $data['collected'] ?? count($data['reviews']),
                ],
            ]);
        });
    }

    /**
     * Заливаем отзывы пачками. upsert обновляет по паре
     * (organization_id, external_id) — поэтому дублей не будет.
     */
    private function saveReviews(Organization $organization, array $reviews): void
    {
        $rows = [];
        foreach ($reviews as $r) {
            if (empty($r['externalId'])) {
                continue;
            }
            $rows[] = [
                'organization_id' => $organization->id,
                'external_id' => $r['externalId'],
                'author' => $r['author'] ?? null,
                'rating' => $r['rating'] ?? null,
                'text' => $r['text'] ?? '',
                'review_date' => isset($r['date']) ? Carbon::parse($r['date']) : null,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        // пачками по 200, чтобы не упереться в лимиты БД
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('reviews')->upsert(
                $chunk,
                ['organization_id', 'external_id'],   // по чему ищем дубли
                ['author', 'rating', 'text', 'review_date', 'updated_at'] // что обновляем
            );
        }
    }
}
