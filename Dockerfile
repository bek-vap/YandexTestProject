# Образ приложения: PHP + Node + Chromium (для парсера).
FROM php:8.4-cli-bookworm

# системные библиотеки и расширения PHP
RUN apt-get update && apt-get install -y \
        git unzip curl ca-certificates gnupg \
        libonig-dev libzip-dev libxml2-dev \
        default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring bcmath zip pcntl \
    && rm -rf /var/lib/apt/lists/*

# Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

# PHP-зависимости (без dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# готовим .env и ключ приложения (общий для app и worker — образ один)
RUN cp .env.example .env && php artisan key:generate

# фронтенд: ставим пакеты и собираем статику
RUN npm ci && npm run build

# ставим браузер Chromium для Playwright + его системные зависимости
RUN npx playwright install --with-deps chromium

# entrypoint готовит окружение и запускает переданную команду
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
