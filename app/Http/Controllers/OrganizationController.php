<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Jobs\ParseOrganizationJob;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['organization' => $this->currentOrganization($request)]);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // если ссылка уже есть — обновляем, а не создаём вторую (user_id проставится сам)
        $organization = $request->user()->organizations()->updateOrCreate(
            ['yandex_url' => $data['yandex_url']],
            ['status' => 'pending'],
        );

        // запоминаем, что человек сохранил последней, её и показываем
        if ($request->hasSession()) {
            $request->session()->put('current_organization_id', $organization->id);
        }

        // парсим в фоне, чтобы не держать пользователя
        ParseOrganizationJob::dispatch($organization->id);

        return response()->json(['organization' => $organization], 201);
    }

    public function reviews(Request $request): JsonResponse
    {
        $organization = $this->currentOrganization($request);

        if (! $organization) {
            return response()->json(['message' => 'Организация не добавлена.'], 404);
        }

        $reviews = $organization->reviews()
            ->orderByDesc('review_date')
            ->paginate(50);

        return response()->json($reviews);
    }

    // та, что сохранили последней в этой сессии, иначе та, что обновлялась последней
    private function currentOrganization(Request $request): ?Organization
    {
        $id = $request->hasSession() ? $request->session()->get('current_organization_id') : null;

        if ($id) {
            $organization = $request->user()->organizations()->find($id);
            if ($organization) {
                return $organization;
            }
        }

        return $request->user()->organizations()->latest('updated_at')->first();
    }
}
