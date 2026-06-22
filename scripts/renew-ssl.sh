#!/bin/bash
# Автообновление SSL-сертификата (запускается cron'ом каждый день в 3:00)

set -e
PROJECT_DIR="/var/www/asoft.kz"

echo "[$(date)] Обновление SSL-сертификата..."

# Останавливаем nginx для standalone-renewal
cd "$PROJECT_DIR"
docker compose -f docker-compose.prod.yml stop nginx

# Обновляем сертификат
certbot renew --standalone --quiet

# Запускаем nginx обратно
docker compose -f docker-compose.prod.yml up -d nginx

echo "[$(date)] Готово"
