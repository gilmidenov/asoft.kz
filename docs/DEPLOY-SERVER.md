# Деплой asoft.kz на сервер hoster.kz

## Сервер

| Параметр | Значение |
|----------|----------|
| Хостинг | hoster.kz Cloud 2-2-50 |
| IP | 109.235.117.178 |
| CPU / RAM | 2 vCPU / 2 GB |
| Диск | 50 GB NVMe |
| OS | Linux 5.15 |
| Docker | 29.6.0 |
| Docker Compose | v5.1.4 |
| certbot | установлен |

---

## Архитектура развёртывания

```
Internet
  │
  ├── :80 (HTTP) ──────────────► asoft_nginx (nginx:alpine)
  └── :443 (HTTPS, после SSL) ──► asoft_nginx
                                        │
                               fastcgi :9000
                                        │
                                  asoft_app (php:8.4-fpm)
                                        │
                               pgsql :5432
                                        │
                             asoft_postgres (postgres:16-alpine)
```

Все контейнеры в сети `asoft_network`. Данные PostgreSQL — в volume `postgres_data`.

---

## Что было сделано при первом деплое (22 июня 2026)

### 1. Настройка .env

Сгенерирован `APP_KEY`:
```
APP_KEY=base64:VMhC/Rpw8eNvcw7hneHZiXel7nzW7sGNuAhdvo8aAiA=
```

Ключевые параметры:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://asoft.kz
DB_HOST=postgres       # имя Docker-сервиса
DB_DATABASE=asoft_kz
DB_USERNAME=asoft_user
DB_PASSWORD=amanat1988!
```

### 2. Запуск контейнеров (HTTP-режим, до получения SSL)

```bash
cd /var/www/asoft.kz
docker compose -f docker-compose.prod.yml -f docker-compose.http-init.yml up -d
```

`docker-compose.http-init.yml` — override, который:
- Использует `docker/nginx/http-only.conf` (без SSL)
- Не монтирует `/etc/letsencrypt`

### 3. Миграции и сиды

```bash
docker exec asoft_app php artisan migrate --force
docker exec asoft_app php artisan db:seed --force
```

Созданы таблицы: users, categories, vendors, products, product_licenses,
product_images, cart_items, favorites, orders, order_items, personal_access_tokens.

### 4. Кеш Laravel

```bash
docker exec asoft_app php artisan config:cache
docker exec asoft_app php artisan route:cache
docker exec asoft_app php artisan view:cache
```

### 5. Проверка

```bash
curl -s -o /dev/null -w "%{http_code}" http://109.235.117.178/
# → 200
```

---

## Подключение домена asoft.kz (обязательный шаг)

### Шаг 1: Настройка DNS-записей в hoster.kz

Войти в [hoster.kz/cabinet/all-services](https://hoster.kz/cabinet/all-services/) → домен asoft.kz → управление DNS.

Добавить/обновить записи:

| Тип | Имя | Значение | TTL |
|-----|-----|----------|-----|
| A   | @   | 109.235.117.178 | 300 |
| A   | www | 109.235.117.178 | 300 |

### Шаг 2: Дождаться распространения DNS

Проверить командой (с локального компьютера или сервера):
```bash
dig +short asoft.kz
# должно вернуть: 109.235.117.178
```

Обычно занимает от 5 минут до 1 часа.

### Шаг 3: Получить SSL-сертификат (Let's Encrypt)

После того как DNS распространится — запустить скрипт **на сервере**:

```bash
cd /var/www/asoft.kz
bash scripts/enable-ssl.sh
```

Скрипт:
1. Проверяет, что DNS указывает на этот сервер
2. Останавливает nginx
3. Запускает `certbot certonly --standalone`
4. Запускает nginx с `docker/nginx/prod.conf` (HTTPS)
5. Добавляет cron для автообновления сертификата

---

## Обслуживание

### Перезапуск всего

```bash
cd /var/www/asoft.kz
docker compose -f docker-compose.prod.yml up -d
```

### Перезапуск только nginx (после изменения конфига)

```bash
docker compose -f docker-compose.prod.yml restart nginx
```

### Просмотр логов

```bash
docker logs asoft_nginx --tail=50
docker logs asoft_app --tail=50
docker logs asoft_postgres --tail=50
```

### Зайти в контейнер PHP

```bash
docker exec -it asoft_app bash
```

### Зайти в PostgreSQL

```bash
docker exec -it asoft_postgres psql -U asoft_user -d asoft_kz
```

### Обновление кода (деплой новой версии)

```bash
cd /var/www/asoft.kz
git pull
docker exec asoft_app php artisan migrate --force
docker exec asoft_app php artisan config:cache
docker exec asoft_app php artisan route:cache
docker exec asoft_app php artisan view:cache
npm run build   # если изменился frontend
```

### Бэкап базы данных

```bash
docker exec asoft_postgres pg_dump -U asoft_user asoft_kz > backup_$(date +%Y%m%d).sql
```

### Восстановление из бэкапа

```bash
docker exec -i asoft_postgres psql -U asoft_user asoft_kz < backup_20260622.sql
```

---

## Структура файлов Docker

```
/var/www/asoft.kz/
├── docker-compose.prod.yml       # основной compose (с SSL)
├── docker-compose.http-init.yml  # override для HTTP (до SSL)
├── Dockerfile                    # php:8.4-fpm образ
├── docker/
│   ├── nginx/
│   │   ├── prod.conf             # nginx с SSL (финальный)
│   │   └── http-only.conf        # nginx без SSL (до получения серта)
│   └── php/
│       └── prod.ini              # PHP настройки (upload, opcache, timezone)
└── scripts/
    ├── enable-ssl.sh             # получение SSL + переключение на HTTPS
    └── renew-ssl.sh              # автообновление сертификата (cron)
```

---

## Статус контейнеров

Проверить командой:
```bash
docker ps
```

Ожидаемый вывод:
```
asoft_nginx     nginx:alpine         Up   80/tcp, 443/tcp
asoft_app       asoftkz-app          Up   9000/tcp
asoft_postgres  postgres:16-alpine   Up (healthy)
```
