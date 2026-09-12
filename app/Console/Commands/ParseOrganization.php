<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Yandex\OrganizationImporter;
use App\Services\Yandex\YandexParserException;
use App\Services\Yandex\YandexReviewsParser;
use Illuminate\Console\Command;

class ParseOrganization extends Command
{
    protected $signature = 'yandex:parse {organization? : ID организации} {--limit= : сколько отзывов максимум}';

    protected $description = 'Спарсить отзывы организации и сохранить в БД';

    public function handle(YandexReviewsParser $parser, OrganizationImporter $importer): int
    {
        $organization = $this->argument('organization')
            ? Organization::find($this->argument('organization'))
            : Organization::first();

        if (! $organization) {
            $this->error('Организация не найдена.');

            return self::FAILURE;
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info("Парсим: {$organization->yandex_url}");
        $organization->update(['status' => 'parsing']);

        try {
            $data = $parser->fetch($organization->yandex_url, $limit);
            $importer->import($organization, $data);
        } catch (YandexParserException $e) {
            $organization->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
            $this->error('Ошибка: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Готово. Название: {$data['name']}, рейтинг: {$data['rating']}, ".
            "оценок: {$data['ratingsCount']}, отзывов: {$data['reviewsCount']}, собрано: {$data['collected']}");

        return self::SUCCESS;
    }
}
