# Деплой asoft.kz через Docker (2 vCPU / 2 GB RAM)

Стартовая точка: подключён по SSH как **root**, находишься в `/var`.

---

## Шаг 1 — Создать пользователя deploy

```bash
adduser deploy
usermod -aG sudo deploy

# Копируем SSH-ключ чтобы войти без пароля
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy
```

Работаем дальше от **root** — для деплоя достаточно.

---

## Шаг 2 — Установить Docker

```bash
apt update && apt upgrade -y

# Официальный скрипт установки Docker
curl -fsSL https://get.docker.com | sh

# Добавляем deploy в группу docker (чтобы запускал без sudo)
usermod -aG docker deploy

# Проверяем
docker --version
docker compose version
```

---

## Шаг 3 — Склонировать проект

```bash
mkdir -p /var/www/asoft.kz
chown deploy:deploy /var/www/asoft.kz

# Клонируем (замени URL на свой репозиторий)
git clone https://github.com/ВАШ_АККАУНТ/asoft.kz.git /var/www/asoft.kz

cd /var/www/asoft.kz
```

---

## Шаг 4 — Создать .env

```bash
cp .env.example .env
nano .env
```

Заполни эти значения (остальное не трогай):

```env
APP_NAME="Atlas Software"
APP_ENV=production
APP_KEY=                        # заполнится на шаге 6
APP_DEBUG=false
APP_URL=https://asoft.kz

LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=postgres                # имя сервиса в docker-compose — НЕ менять
DB_PORT=5432
DB_DATABASE=asoft_kz
DB_USERNAME=asoft_user
DB_PASSWORD=ПРИДУМАЙ_СИЛЬНЫЙ_ПАРОЛЬ_ЗДЕСЬ

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

SANCTUM_STATEFUL_DOMAINS=asoft.kz,www.asoft.kz
```

> **Важно:** пароль БД пиши только здесь, в docker-compose.prod.yml его нет — он читается из .env.

---

## Шаг 5 — Собрать фронтенд

Node.js на сервере устанавливать не нужно — запускаем через одноразовый контейнер:

```bash
cd /var/www/asoft.kz

docker run --rm \
  -v "$(pwd):/var/www" \
  -w /var/www \
  node:20-alpine \
  sh -c "npm ci && npm run build"
```

После этого появится папка `public/build/` — это и есть собранный Vue SPA.

---

## Шаг 6 — Установить Composer-зависимости

```bash
docker run --rm \
  -v "$(pwd):/var/www" \
  -w /var/www \
  composer:latest \
  composer install --no-dev --optimize-autoloader --no-interaction
```

---

## Шаг 7 — Получить SSL-сертификат

> Перед этим шагом DNS домена `asoft.kz` уже должен указывать на IP этого сервера.
> Проверь: `ping asoft.kz` — должен отвечать IP сервера.

Устанавливаем certbot и получаем сертификат в standalone-режиме
(nginx ещё не запущен, поэтому порт 80 свободен):

```bash
apt install -y certbot

certbot certonly --standalone \
  -d asoft.kz \
  -d www.asoft.kz \
  --email gilmidenov.ad@gmail.com \
  --agree-tos \
  --no-eff-email
```

Сертификаты сохранятся в `/etc/letsencrypt/live/asoft.kz/` —
docker-compose.prod.yml монтирует эту папку в nginx-контейнер.

---

## Шаг 8 — Запустить контейнеры

```bash
cd /var/www/asoft.kz

docker compose -f docker-compose.prod.yml up -d --build
```

Первый запуск занимает 3–5 минут (собирается PHP образ).

Проверить что всё поднялось:

```bash
docker compose -f docker-compose.prod.yml ps
```

Должны быть статусы `Up` у всех трёх контейнеров: `asoft_app`, `asoft_nginx`, `asoft_postgres`.

---

## Шаг 9 — Настроить Laravel

```bash
# Генерируем APP_KEY
docker exec asoft_app php artisan key:generate

# Применяем миграции
docker exec asoft_app php artisan migrate --force

# Заполняем начальные данные
docker exec asoft_app php artisan db:seed --force

# Кэш для продакшена
docker exec asoft_app php artisan config:cache
docker exec asoft_app php artisan route:cache
docker exec asoft_app php artisan view:cache
```

---

## Шаг 10 — Создать admin-пользователя

Зарегистрируй аккаунт через сайт, затем назначь роль:

```bash
docker exec asoft_app php artisan tinker
```

```php
\App\Models\User::where('email', 'admin@asoft.kz')->update(['role' => 'admin']);
exit
```

---

## Шаг 11 — Настроить автообновление SSL

```bash
# Проверяем что обновление работает
certbot renew --dry-run

# Добавляем cron для автообновления (каждый день в 3:00)
echo "0 3 * * * root certbot renew --quiet --post-hook 'docker exec asoft_nginx nginx -s reload'" \
  >> /etc/crontab
```

---

## Проверка работы

```bash
# Логи Laravel
docker exec asoft_app tail -f /var/www/storage/logs/laravel.log

# Логи Nginx
docker logs asoft_nginx

# Тест API
curl https://asoft.kz/api/categories
curl https://asoft.kz/api/products
```

---

## Обновление кода (следующие деплои)

```bash
cd /var/www/asoft.kz

docker exec asoft_app php artisan down

git pull origin main

# Зависимости (если изменились)
docker run --rm -v "$(pwd):/var/www" -w /var/www composer:latest \
  composer install --no-dev --optimize-autoloader --no-interaction

# Пересборка фронтенда (если изменился)
docker run --rm -v "$(pwd):/var/www" -w /var/www node:20-alpine \
  sh -c "npm ci && npm run build"

# Миграции
docker exec asoft_app php artisan migrate --force

# Обновить кэш
docker exec asoft_app php artisan config:cache
docker exec asoft_app php artisan route:cache
docker exec asoft_app php artisan view:cache

# Перезапустить app-контейнер (подхватит новый код)
docker compose -f docker-compose.prod.yml restart app

docker exec asoft_app php artisan up
```

---

## Использование RAM (итог)

| Контейнер        | ~RAM  |
|------------------|-------|
| asoft_app        | 80 MB |
| asoft_nginx      | 15 MB |
| asoft_postgres   | 60 MB |
| Docker + OS      | 400 MB |
| **Итого**        | **~555 MB** из 2 GB |

Остаётся ~1.5 GB запаса — достаточно для трафика и пиковых нагрузок.
