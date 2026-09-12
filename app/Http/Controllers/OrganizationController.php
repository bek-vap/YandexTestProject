<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Jobs\ParseOrganizationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $organization = $request->user()
            ->organizations()
            ->latest()
            ->first();

        return response()->json(['organization' => $organization]);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // если ссылка уже есть — обновляем, а не создаём вторую (user_id проставится сам)
        $organization = $request->user()->organizations()->updateOrCreate(
            ['yandex_url' => $data['yandex_url']],
            ['status' => 'pending'],
        );

        // парсим в фоне, чтобы не держать пользователя
        ParseOrganizationJob::dispatch($organization->id);

        return response()->json(['organization' => $organization], 201);
    }

    public function reviews(Request $request): JsonResponse
    {
        $organization = $request->user()->organizations()->latest()->first();

        if (! $organization) {
            return response()->json(['message' => 'Организация не добавлена.'], 404);
        }

        $reviews = $organization->reviews()
            ->orderByDesc('review_date')
            ->paginate(50);

        return response()->json($reviews);
    }
}
