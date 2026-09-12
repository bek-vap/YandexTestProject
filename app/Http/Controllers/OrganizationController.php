<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * Текущая сохранённая карточка пользователя (или null, если ещё не добавил).
     */
    public function show(Request $request): JsonResponse
    {
        $organization = $request->user()
            ->organizations()
            ->latest()
            ->first();

        return response()->json(['organization' => $organization]);
    }

    /**
     * Сохранить ссылку на организацию.
     * Валидация уже прошла в StoreOrganizationRequest — сюда попадают чистые данные.
     */
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // updateOrCreate: если такая ссылка у пользователя уже есть — обновим,
        // а не создадим дубль (идемпотентность). user_id проставится сам,
        // потому что идём через связь $request->user()->organizations().
        $organization = $request->user()->organizations()->updateOrCreate(
            ['yandex_url' => $data['yandex_url']],
            ['status' => 'pending'], // парсинг запустим позже (Фаза 4)
        );

        return response()->json(['organization' => $organization], 201);
    }

    /**
     * Отзывы организации постранично, по 50 на страницу.
     */
    public function reviews(Request $request): JsonResponse
    {
        $organization = $request->user()->organizations()->latest()->first();

        if (! $organization) {
            return response()->json(['message' => 'Организация не добавлена.'], 404);
        }

        // paginate сам берёт номер страницы из ?page= и считает всё остальное
        $reviews = $organization->reviews()
            ->orderByDesc('review_date')
            ->paginate(50);

        return response()->json($reviews);
    }
}
