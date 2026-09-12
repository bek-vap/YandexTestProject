<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\Yandex\OrganizationImporter;
use App\Services\Yandex\YandexReviewsParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ParseOrganizationJob implements ShouldQueue
{
    use Queueable;

    // до 3 попыток, если что-то сорвалось
    public int $tries = 3;

    // даём задаче до 15 минут (браузеру нужно время листать отзывы)
    public int $timeout = 900;

    public function __construct(public int $organizationId) {}

    // паузы между попытками (сек): 10, 30, 60
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(YandexReviewsParser $parser, OrganizationImporter $importer): void
    {
        $organization = Organization::find($this->organizationId);
        if (! $organization) {
            return;
        }

        $organization->update(['status' => 'parsing']);

        $limit = config('services.yandex.review_limit');
        $data = $parser->fetch($organization->yandex_url, $limit ? (int) $limit : null);
        $importer->import($organization, $data);
    }

    // если все попытки провалились — помечаем как failed с текстом ошибки
    public function failed(Throwable $e): void
    {
        Organization::find($this->organizationId)?->update([
            'status' => 'failed',
            'last_error' => $e->getMessage(),
        ]);
    }
}
