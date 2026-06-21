# Часть 2 (Docker): Среда разработки через Docker

> Эта часть **заменяет** раздел "Подготовка окружения" из GUIDE_PART1.md.
> Вместо установки PHP, PostgreSQL и Node.js на компьютер — запускаем всё в Docker-контейнерах.

---

## Что такое Docker и зачем он нужен

**Docker** — инструмент, который запускает приложения в изолированных **контейнерах**.

Контейнер — это как лёгкая виртуальная машина, но без полноценной ОС. Внутри контейнера есть только то, что нужно конкретному сервису: PHP, PostgreSQL или Nginx.

### Проблема без Docker

```
Разработчик А: PHP 8.1, PostgreSQL 14, Node 18
Разработчик Б: PHP 8.3, PostgreSQL 16, Node 20
Продакшен:     PHP 8.2, PostgreSQL 15, Node 20

→ "У меня работает, у тебя нет" — классическая проблема
```

### Решение с Docker

```
docker-compose.yml описывает нужные версии
→ Все работают в одинаковом окружении
→ На любом компьютере достаточно одной команды: docker compose up -d
```

---

## Архитектура нашего Docker-окружения

```
Браузер
  │
  ├─── localhost:5173 ──→ [node контейнер]   Vite dev server (Vue.js, HMR)
  │                            │
  └─── localhost:8000 ──→ [nginx контейнер]  Веб-сервер
                               │
                        /api/* │  PHP-файлы
                               ↓
                        [app контейнер]      PHP 8.2-FPM (Laravel)
                               │
                               ↓
                        [postgres контейнер] PostgreSQL 16

Все 4 контейнера в одной сети asoft_network
Общаются по именам: app:9000, postgres:5432
```

---

## 2.1 Установка Docker Desktop

### Windows

1. Скачай **Docker Desktop** с [docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop/)
2. Запусти установщик `Docker Desktop Installer.exe`
3. При установке оставь включённым **WSL 2** (Windows Subsystem for Linux 2)

> **Почему WSL 2?** Docker на Windows работает через Linux-ядро. WSL 2 даёт настоящее Linux-ядро прямо в Windows, что делает Docker быстрее и стабильнее, чем через Hyper-V.

4. После установки **перезагрузи компьютер**
5. Запусти Docker Desktop — в трее появится иконка кита 🐳

### Проверка установки

Открой PowerShell или Terminal и выполни:

```powershell
docker --version
# Docker version 27.x.x

docker compose version
# Docker Compose version v2.x.x
```

Если видишь версии — Docker установлен.

> **Важно:** Docker Desktop должен быть **запущен** перед каждой работой с проектом. Следи за иконкой в трее.

---

## 2.2 Структура Docker-файлов проекта

После создания проекта Laravel у тебя уже есть эти файлы (мы создали их):

```
asoft.kz/
├── Dockerfile                  # Образ PHP-приложения
├── docker-compose.yml          # Описание всех сервисов
├── .dockerignore               # Что не копировать в образ
└── docker/
    ├── nginx/
    │   └── default.conf        # Конфиг Nginx
    └── php/
        └── local.ini           # Настройки PHP
```

---

## 2.3 Файл Dockerfile — разбор по строкам

`Dockerfile` описывает, как собрать Docker-образ для PHP-приложения.

```
FROM php:8.2-fpm
```
Берём официальный образ PHP 8.2 с FPM. FPM (FastCGI Process Manager) — менеджер процессов PHP, работает в связке с Nginx.

```
RUN apt-get install -y libpq-dev ...
```
Устанавливаем системные библиотеки. `libpq-dev` — клиентские библиотеки PostgreSQL, без них не скомпилируется `pdo_pgsql`.

```
RUN docker-php-ext-install pdo pdo_pgsql ...
```
Устанавливаем PHP-расширения. `docker-php-ext-install` — утилита из базового образа, компилирует расширения под нашу версию PHP.

```
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```
Копируем Composer из официального образа. Это `multi-stage build` — не скачиваем Composer вручную, берём готовый бинарник.

---

## 2.4 Файл docker-compose.yml — разбор по секциям

### Сервис `app` (PHP-FPM)

```yaml
app:
  build:
    context: .
    dockerfile: Dockerfile
  volumes:
    - .:/var/www
```

- `build: context: .` — собираем образ из нашего `Dockerfile`
- `volumes: .:/var/www` — **ключевое**: папка проекта монтируется в контейнер. Меняешь файл в PhpStorm → сразу видно в контейнере. Не нужно пересобирать образ!

### Сервис `nginx`

```yaml
nginx:
  image: nginx:alpine
  ports:
    - "8000:80"
```

- Используем готовый образ, не строим свой
- `"8000:80"` — твой порт 8000 → порт 80 внутри Nginx

### Сервис `postgres`

```yaml
postgres:
  environment:
    POSTGRES_DB: asoft_kz
    POSTGRES_USER: asoft_user
    POSTGRES_PASSWORD: secret_password
  volumes:
    - postgres_data:/var/lib/postgresql/data
```

- Переменные окружения автоматически создают БД и пользователя при первом запуске
- `postgres_data` — **Named Volume**: данные хранятся на диске, не исчезают при перезапуске контейнера

### Сервис `node`

```yaml
node:
  command: sh -c "npm install && npm run dev -- --host 0.0.0.0"
  ports:
    - "5173:5173"
```

- `--host 0.0.0.0` — Vite слушает на всех интерфейсах, иначе снаружи контейнера не достучаться
- Том `/var/www/node_modules` — анонимный том для `node_modules`. На Windows без этого `npm install` работает в 10 раз медленнее из-за проблем с ntfs → linux fs mapping

---

## 2.5 Первый запуск

### Шаг 1: Создать проект Laravel

Перед запуском Docker нам нужен сам проект. Запускаем через Docker, не устанавливая Composer локально:

```powershell
# Создаём проект через временный PHP-контейнер
docker run --rm -v "${PWD}:/var/www" -w /var/www composer:latest composer create-project laravel/laravel . --prefer-dist
```

**Что происходит:**
- `docker run --rm` — запустить контейнер и удалить его после завершения
- `-v "${PWD}:/var/www"` — монтируем текущую папку в `/var/www`
- `-w /var/www` — рабочая директория внутри
- `composer:latest` — образ с Composer
- `composer create-project laravel/laravel . --prefer-dist` — создаём проект в текущей папке (`.`)

> Если проект уже создан (папка не пустая) — пропусти этот шаг.

### Шаг 2: Настроить .env для Docker

Скопируй `.env.example` в `.env`:

```powershell
copy .env.example .env
```

Открой `.env` и **измени настройки БД**:

```dotenv
APP_NAME="Atlas Software"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Важно: DB_HOST должен совпадать с именем сервиса в docker-compose.yml
# Laravel внутри контейнера 'app' обращается к 'postgres' по имени сервиса
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=asoft_kz
DB_USERNAME=asoft_user
DB_PASSWORD=secret_password
```

> **Ключевой момент:** `DB_HOST=postgres` — не `localhost`, а имя Docker-сервиса. Внутри Docker-сети `postgres` резолвится в IP контейнера с PostgreSQL.

### Шаг 3: Собрать и запустить контейнеры

```powershell
docker compose up -d --build
```

**Что происходит:**
- `up` — запустить все сервисы из `docker-compose.yml`
- `-d` (detached) — в фоновом режиме (не занимает терминал)
- `--build` — пересобрать образы (нужно при первом запуске или при изменении `Dockerfile`)

Первый запуск займёт 3-7 минут (скачивает образы, устанавливает зависимости).

### Шаг 4: Проверить что всё запущено

```powershell
docker compose ps
```

Должно быть:

```
NAME              IMAGE              STATUS          PORTS
asoft_app         asoft.kz-app       Up              9000/tcp
asoft_nginx       nginx:alpine        Up              0.0.0.0:8000->80/tcp
asoft_postgres    postgres:16-alpine  Up (healthy)    0.0.0.0:5432->5432/tcp
asoft_node        node:20-alpine      Up              0.0.0.0:5173->5173/tcp
```

Все должны быть `Up`. Postgres должен быть `Up (healthy)`.

### Шаг 5: Сгенерировать ключ приложения

```powershell
docker compose exec app php artisan key:generate
```

**Что происходит:**
- `docker compose exec app` — выполнить команду внутри контейнера `app`
- `php artisan key:generate` — генерирует APP_KEY в `.env`

### Шаг 6: Применить миграции

```powershell
docker compose exec app php artisan migrate
```

### Шаг 7: Заполнить базу тестовыми данными

```powershell
docker compose exec app php artisan db:seed
```

### Шаг 8: Открыть сайт

- **Frontend (Vue.js):** [http://localhost:5173](http://localhost:5173)
- **Backend API:** [http://localhost:8000/api/products](http://localhost:8000/api/products)

---

## 2.6 Ежедневная работа с Docker

### Запустить (утром, начало работы)

```powershell
docker compose up -d
```

### Остановить (вечером, конец работы)

```powershell
docker compose stop
```

> `stop` — останавливает контейнеры, данные сохраняются.
> `down` — останавливает И удаляет контейнеры (данные в volumes остаются).
> `down -v` — удаляет всё включая volumes (данные БД пропадут!).

### Выполнить команды Artisan

```powershell
# Создать миграцию
docker compose exec app php artisan make:migration create_products_table

# Применить миграции
docker compose exec app php artisan migrate

# Запустить тесты
docker compose exec app php artisan test

# Открыть Tinker (интерактивная консоль)
docker compose exec app php artisan tinker
```

### Выполнить команды npm

```powershell
# Установить новый пакет npm
docker compose exec node npm install vue-toastification

# Запустить тесты Vue
docker compose exec node npm run test:vue

# Собрать для продакшена
docker compose exec node npm run build
```

### Просмотреть логи

```powershell
# Логи всех контейнеров
docker compose logs

# Логи конкретного сервиса (следить в реальном времени)
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f postgres
```

### Зайти внутрь контейнера (для отладки)

```powershell
# Shell внутри PHP-контейнера
docker compose exec app bash

# Shell внутри PostgreSQL
docker compose exec postgres psql -U asoft_user -d asoft_kz
```

---

## 2.7 Установка Composer-зависимостей внутри Docker

Когда добавляешь новый PHP-пакет (`composer require something`):

```powershell
# Вариант 1: через контейнер
docker compose exec app composer require laravel/sanctum

# Вариант 2: через временный контейнер (если app не запущен)
docker run --rm -v "${PWD}:/var/www" -w /var/www composer:latest composer require laravel/sanctum
```

---

## 2.8 Подключение к PostgreSQL через pgAdmin

Если хочешь визуально работать с базой через [pgAdmin](https://www.pgadmin.org/):

1. Скачай и установи pgAdmin
2. При добавлении сервера укажи:
   - **Host:** `localhost`
   - **Port:** `5432`
   - **Username:** `asoft_user`
   - **Password:** `secret_password`
   - **Database:** `asoft_kz`

Порт 5432 проброшен наружу в `docker-compose.yml`, поэтому подключение работает.

---

## 2.9 Как работает Hot Module Replacement (HMR)

Когда ты меняешь `.vue` файл:

```
Ты меняешь ProductCard.vue
    ↓
Vite (node контейнер) замечает изменение через inotify
    ↓
Vite по WebSocket отправляет обновление в браузер
    ↓
Браузер обновляет только изменённый компонент
    ↓
Без перезагрузки страницы! State сохраняется.
```

Это называется **HMR — Hot Module Replacement**. Очень ускоряет разработку.

---

## 2.10 Пересборка после изменения Dockerfile

Если изменил `Dockerfile` (добавил PHP-расширение и т.д.):

```powershell
# Пересобрать только сервис app
docker compose build app

# Пересобрать и перезапустить
docker compose up -d --build app
```

---

## 2.11 Полный сброс (если что-то сломалось)

```powershell
# Остановить и удалить контейнеры (volumes НЕ трогаем — данные БД сохранятся)
docker compose down

# Пересобрать образы с нуля (без кэша)
docker compose build --no-cache

# Запустить снова
docker compose up -d
```

Если нужен полный сброс включая данные БД:

```powershell
docker compose down -v  # -v удаляет volumes
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

---

## Шпаргалка команд Docker

| Что сделать | Команда |
|-------------|---------|
| Запустить всё | `docker compose up -d` |
| Остановить всё | `docker compose stop` |
| Статус контейнеров | `docker compose ps` |
| Логи | `docker compose logs -f` |
| Artisan команда | `docker compose exec app php artisan <команда>` |
| npm команда | `docker compose exec node npm <команда>` |
| Bash в PHP | `docker compose exec app bash` |
| psql в PostgreSQL | `docker compose exec postgres psql -U asoft_user -d asoft_kz` |
| Пересобрать образы | `docker compose build --no-cache` |
| Полный сброс с данными | `docker compose down -v && docker compose up -d --build` |
