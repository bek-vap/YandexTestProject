<?php

namespace App\Services\Yandex;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class YandexReviewsParser
{
    // сколько ждём браузер максимум (секунды)
    private int $timeout = 600;

    /**
     * Запускает браузерный парсер и возвращает данные по организации.
     * $limit — ограничить число отзывов (для тестов). null — тянуть все.
     */
    public function fetch(string $url, ?int $limit = null): array
    {
        $script = base_path('scripts/yandex-parser/parse.mjs');

        $command = ['node', $script, $url];
        if ($limit !== null) {
            $command[] = (string) $limit;
        }

        $process = new Process($command);
        $process->setTimeout($this->timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            throw new YandexParserException('Парсер не успел за отведённое время.');
        }

        if (! $process->isSuccessful()) {
            throw new YandexParserException('Не удалось запустить парсер: '.$process->getErrorOutput());
        }

        $data = json_decode($process->getOutput(), true);

        // если пришёл не JSON — значит скрипт сломался
        if (! is_array($data)) {
            throw new YandexParserException('Парсер вернул не то, что ожидали.');
        }

        // скрипт сам сообщил об ошибке (капча, пусто и т.д.)
        if (($data['ok'] ?? false) !== true) {
            throw new YandexParserException($this->explainError($data['error'] ?? 'unknown'));
        }

        // проверяем, что структура та, что ждём (защита от смены разметки)
        if (! isset($data['reviews']) || ! is_array($data['reviews'])) {
            throw new YandexParserException('Структура данных изменилась — отзывов нет в ответе.');
        }

        return $data;
    }

    // переводим коды ошибок скрипта в понятный текст
    private function explainError(string $code): string
    {
        return match ($code) {
            'captcha' => 'Яндекс показал капчу — попробуйте позже.',
            'reviews_not_found' => 'Не нашли отзывы на странице (возможно, изменилась разметка).',
            'page_load_failed' => 'Не удалось загрузить страницу организации.',
            'no_url' => 'Не передана ссылка.',
            default => 'Парсер завершился с ошибкой: '.$code,
        };
    }
}
