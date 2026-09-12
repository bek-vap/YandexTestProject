<?php

namespace App\Services\Yandex;

use RuntimeException;

// Своя ошибка парсера, чтобы отличать её от обычных и показывать понятный текст.
class YandexParserException extends RuntimeException
{
}
