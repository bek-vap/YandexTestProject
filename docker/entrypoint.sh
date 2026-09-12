#!/bin/sh
set -e
cd /app

# ждём, пока база поднимется
if [ -n "$DB_HOST" ]; then
    echo "Ждём базу данных ($DB_HOST)..."
    until mysqladmin ping -h "$DB_HOST" --silent 2>/dev/null; do
        sleep 2
    done
    echo "База готова."
fi

# запускаем команду, переданную контейнеру (serve или queue:work)
exec "$@"
