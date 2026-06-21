# Деплой asoft.kz на VPS (Ubuntu 22.04)

Пошаговая инструкция для сервера **2 vCPU / 2 GB RAM / 50 GB NVMe** (hoster.kz Cloud 2-2-50).

---

## 1. Первый вход на сервер

```bash
# Подключаемся по SSH (IP выдаётся хостером)
ssh root@<IP_СЕРВЕРА>

# Обновляем пакеты
apt update && apt upgrade -y

# Создаём пользователя (не работаем под root)
adduser deploy
usermod -aG sudo deploy

# Копируем SSH-ключ чтобы войти без пароля
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy

# Переходим на нового пользователя
su - deploy
```

---

## 2. Установка PHP 8.3

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y \
  php8.3-fpm \
  php8.3-pgsql \
  php8.3-mbstring \
  php8.3-xml \
  php8.3-curl \
  php8.3-zip \
  php8.3-bcmath \
  php8.3-intl \
  php8.3-redis

# Проверяем
php -v
```

---

## 3. Установка Nginx

```bash
sudo apt install -y nginx

# Запуск и автозагрузка
sudo systemctl enable nginx
sudo systemctl start nginx
```

---

## 4. Установка PostgreSQL 16

```bash
# Добавляем официальный репозиторий
sudo apt install -y curl ca-certificates
sudo install -d /usr/share/postgresql-common/pgdg
curl -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc --fail https://www.postgresql.org/media/keys/ACCC4CF8.asc

sudo sh -c 'echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'

sudo apt update
sudo apt install -y postgresql-16

sudo systemctl enable postgresql
sudo systemctl start postgresql

# Создаём базу и пользователя
sudo -u postgres psql <<EOF
CREATE USER asoft_user WITH PASSWORD 'ПРИДУМАЙ_СИЛЬНЫЙ_ПАРОЛЬ';
CREATE DATABASE asoft_kz OWNER asoft_user;
GRANT ALL PRIVILEGES ON DATABASE asoft_kz TO asoft_user;
EOF
```

---

## 5. Установка Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## 6. Установка Node.js (только для сборки)

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v  # должно быть v20.x
```

---

## 7. Загрузка кода на сервер

### Вариант А — через Git (рекомендуется)

```bash
# Устанавливаем git
sudo apt install -y git

# Клонируем репозиторий
sudo mkdir -p /var/www/asoft.kz
sudo chown deploy:deploy /var/www/asoft.kz

git clone https://github.com/ВАШ_АККАУНТ/asoft.kz.git /var/www/asoft.kz
cd /var/www/asoft.kz
```

### Вариант Б — через SCP (с локального компьютера)

```bash
# Выполнять на СВОЁМ компьютере (не на сервере)
# Копируем весь проект, исключая лишнее
rsync -avz --exclude='node_modules' --exclude='.git' --exclude='vendor' \
  C:/Users/gilmi/PhpstormProjects/asoft.kz/ \
  deploy@<IP_СЕРВЕРА>:/var/www/asoft.kz/
```

---

## 8. Настройка Laravel (.env)

```bash
cd /var/www/asoft.kz

# Копируем шаблон
cp .env.example .env

# Открываем редактор
nano .env
```

Заполняем `.env` для продакшена:

```env
APP_NAME="Atlas Software"
APP_ENV=production
APP_KEY=                          # заполнится командой ниже
APP_DEBUG=false
APP_URL=https://asoft.kz         # ваш домен

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=asoft_kz
DB_USERNAME=asoft_user
DB_PASSWORD=ПРИДУМАЙ_СИЛЬНЫЙ_ПАРОЛЬ   # тот же что в шаге 4

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

SANCTUM_STATEFUL_DOMAINS=asoft.kz
```

---

## 9. Установка зависимостей и сборка

```bash
cd /var/www/asoft.kz

# PHP зависимости (без dev-пакетов)
composer install --no-dev --optimize-autoloader

# Генерируем ключ приложения
php artisan key:generate

# Миграции и начальные данные
php artisan migrate --force
php artisan db:seed --force

# Кэш для продакшена (ускоряет запросы)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend — Node нужен только здесь
npm ci
npm run build

# После сборки Node больше не нужен в работе
```

---

## 10. Права доступа

```bash
cd /var/www/asoft.kz

# PHP-FPM работает под www-data
sudo chown -R deploy:www-data /var/www/asoft.kz
sudo chmod -R 755 /var/www/asoft.kz
sudo chmod -R 775 storage bootstrap/cache

# Nginx читает public/
sudo chown -R www-data:www-data /var/www/asoft.kz/public/build
```

---

## 11. Конфигурация Nginx

```bash
sudo nano /etc/nginx/sites-available/asoft.kz
```

Вставляем конфиг:

```nginx
server {
    listen 80;
    server_name asoft.kz www.asoft.kz;
    root /var/www/asoft.kz/public;

    index index.php;
    charset utf-8;

    # Логи
    access_log /var/log/nginx/asoft.kz-access.log;
    error_log  /var/log/nginx/asoft.kz-error.log;

    # Максимальный размер загружаемых файлов
    client_max_body_size 10M;

    # SPA — все маршруты обрабатывает Vue Router
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    # Статика — кэшируем надолго
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Скрываем .htaccess и другие точечные файлы
    location ~ /\. {
        deny all;
    }
}
```

```bash
# Активируем сайт
sudo ln -s /etc/nginx/sites-available/asoft.kz /etc/nginx/sites-enabled/

# Удаляем дефолтный сайт
sudo rm -f /etc/nginx/sites-enabled/default

# Проверяем синтаксис
sudo nginx -t

# Перезапускаем
sudo systemctl reload nginx
```

---

## 12. SSL-сертификат (HTTPS) — бесплатно через Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx

# Получаем сертификат (нужно чтобы домен уже указывал на IP сервера)
sudo certbot --nginx -d asoft.kz -d www.asoft.kz

# Certbot сам обновит конфиг Nginx под HTTPS
# Автообновление сертификата уже настроено через systemd timer
sudo certbot renew --dry-run   # проверяем что обновление работает
```

---

## 13. Настройка PHP-FPM

```bash
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

Находим и меняем значения:

```ini
pm = dynamic
pm.max_children = 10
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 500
```

```bash
sudo systemctl restart php8.3-fpm
```

---

## 14. Настройка домена

На регистраторе домена (или у хостера) создаём DNS-записи:

| Тип | Имя | Значение |
|-----|-----|----------|
| A   | @   | IP_СЕРВЕРА |
| A   | www | IP_СЕРВЕРА |

DNS обновляется до 24 часов. Проверить: `ping asoft.kz`

---

## 15. Проверка работы

```bash
# Статус сервисов
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status postgresql

# Логи Laravel
tail -f /var/www/asoft.kz/storage/logs/laravel.log

# Логи Nginx
tail -f /var/log/nginx/asoft.kz-error.log

# Тест API
curl https://asoft.kz/api/categories
curl https://asoft.kz/api/products
```

---

## 16. Обновление кода (деплой новых версий)

```bash
cd /var/www/asoft.kz

# Включаем режим обслуживания
php artisan down

# Получаем изменения (если через Git)
git pull origin main

# Обновляем зависимости
composer install --no-dev --optimize-autoloader

# Применяем миграции
php artisan migrate --force

# Пересобираем фронтенд
npm ci && npm run build

# Обновляем кэш
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Отключаем режим обслуживания
php artisan up
```

---

## Шпаргалка — управление паролями через Tinker

Если нужно проверить или сбросить пароль любого пользователя:

```bash
cd /var/www/asoft.kz
php artisan tinker
```

```php
// Найти пользователя
$user = \App\Models\User::where('email', 'admin@asoft.kz')->first();
echo $user->role;      // должно быть 'admin'
echo $user->password;  // хеш bcrypt

// Проверить пароль вручную
\Illuminate\Support\Facades\Hash::check('admin123', $user->password); // true/false

// Сбросить пароль
$user->update(['password' => \Illuminate\Support\Facades\Hash::make('НОВЫЙ_ПАРОЛЬ')]);
echo "Пароль обновлён";

exit
```

---

## Итоговая схема сервера

```
Интернет → Nginx (:443 HTTPS)
                ↓
    ┌───────────────────────┐
    │ /api/*   → PHP-FPM    │ → PostgreSQL
    │ /*       → public/    │
    │           index.php   │
    │           (Vue SPA)   │
    └───────────────────────┘
```

После деплоя Vue Router обрабатывает все маршруты на клиенте — Nginx всегда отдаёт `index.php`, а уже там Vue Router разбирается с URL.
