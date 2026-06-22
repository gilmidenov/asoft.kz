#!/bin/bash
# Запускать после того, как DNS asoft.kz указывает на этот сервер (109.235.117.178)
# Скрипт получает SSL-сертификат и переключает nginx на HTTPS

set -e

DOMAIN="asoft.kz"
EMAIL="gilmidenov.ad@gmail.com"
PROJECT_DIR="/var/www/asoft.kz"

echo "==> Проверяю DNS..."
IP=$(dig +short "$DOMAIN" | tail -1)
SERVER_IP=$(curl -4s ifconfig.me)

if [ "$IP" != "$SERVER_IP" ]; then
    echo "ОШИБКА: $DOMAIN указывает на $IP, но сервер: $SERVER_IP"
    echo "Настройте DNS-записи и повторите."
    exit 1
fi
echo "DNS OK: $DOMAIN -> $IP"

echo "==> Останавливаю nginx для получения сертификата..."
cd "$PROJECT_DIR"
docker compose -f docker-compose.prod.yml -f docker-compose.http-init.yml stop nginx

echo "==> Получаю SSL-сертификат (certbot standalone)..."
certbot certonly \
    --standalone \
    --non-interactive \
    --agree-tos \
    --email "$EMAIL" \
    -d "$DOMAIN" \
    -d "www.$DOMAIN"

echo "==> Запускаю nginx с полной SSL-конфигурацией..."
docker compose -f docker-compose.prod.yml up -d nginx

echo "==> Настраиваю автообновление сертификата..."
# Добавляем cron для обновления (если ещё нет)
CRON_LINE="0 3 * * * /var/www/asoft.kz/scripts/renew-ssl.sh >> /var/log/certbot-renew.log 2>&1"
(crontab -l 2>/dev/null | grep -v renew-ssl; echo "$CRON_LINE") | crontab -

echo "==> Готово! Сайт доступен на https://$DOMAIN"
echo ""
echo "Проверьте: curl -I https://$DOMAIN"
