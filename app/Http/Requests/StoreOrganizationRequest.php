<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Доступ к самому роуту уже ограничен middleware auth:sanctum,
     * поэтому здесь просто разрешаем (true).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации входящих данных.
     */
    public function rules(): array
    {
        return [
            'yandex_url' => [
                'required',
                'string',
                'max:500',
                'url',                 // должно быть валидным URL
                $this->yandexOrgUrl(),  // + наша проверка "это карточка организации Яндекса"
            ],
        ];
    }

    /**
     * Кастомное правило: ссылка должна вести на карточку организации
     * именно в Яндекс.Картах, а не на любой сайт.
     */
    private function yandexOrgUrl(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $host = parse_url($value, PHP_URL_HOST) ?? '';
            $path = parse_url($value, PHP_URL_PATH) ?? '';

            // Домен должен быть yandex.* (yandex.ru, yandex.com, ya.ru и т.п.)
            $isYandex = (bool) preg_match('/(^|\.)(yandex|ya)\.[a-z.]+$/i', $host);

            // Путь должен указывать на организацию: /maps/.../org/<id>
            // или короткую ссылку /maps/-/...
            $looksLikeOrg = str_contains($path, '/maps/')
                && (preg_match('#/org/#i', $path) || preg_match('#/maps/-/#i', $path));

            if (! $isYandex || ! $looksLikeOrg) {
                $fail('Ссылка должна вести на карточку организации в Яндекс.Картах.');
            }
        };
    }

    /**
     * Понятные сообщения об ошибках (по-русски, для пользователя).
     */
    public function messages(): array
    {
        return [
            'yandex_url.required' => 'Вставьте ссылку на организацию.',
            'yandex_url.url' => 'Это не похоже на ссылку.',
        ];
    }
}
