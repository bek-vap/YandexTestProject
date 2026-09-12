<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // доступ к роуту уже закрыт middleware auth:sanctum
        return true;
    }

    public function rules(): array
    {
        return [
            'yandex_url' => [
                'required',
                'string',
                'max:500',
                'url',
                $this->yandexOrgUrl(), // проверка, что это карточка организации Яндекса
            ],
        ];
    }

    // ссылка должна вести на карточку организации в Яндекс.Картах, а не на любой сайт
    private function yandexOrgUrl(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $host = parse_url($value, PHP_URL_HOST) ?? '';
            $path = parse_url($value, PHP_URL_PATH) ?? '';

            $isYandex = (bool) preg_match('/(^|\.)(yandex|ya)\.[a-z.]+$/i', $host);
            $looksLikeOrg = str_contains($path, '/maps/')
                && (preg_match('#/org/#i', $path) || preg_match('#/maps/-/#i', $path));

            if (! $isYandex || ! $looksLikeOrg) {
                $fail('Ссылка должна вести на карточку организации в Яндекс.Картах.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'yandex_url.required' => 'Вставьте ссылку на организацию.',
            'yandex_url.url' => 'Это не похоже на ссылку.',
        ];
    }
}
