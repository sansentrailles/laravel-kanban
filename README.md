# 🚀 Laravel 13 + Docker

Полностью готовая среда разработки для Laravel 13 на базе Docker с использованием **PHP 8.5**, **PostgreSQL 16** и **Redis 7**.

## 📋 Содержание

- [Требования](#-требования)
- [Быстрый старт](#-быстрый-старт)
- [Структура проекта](#-структура-проекта)
- [Команды Make](#-команды-make)
  - [Справка](#справка)
  - [Инициализация и управление](#инициализация-и-управление)
  - [Доступ к контейнерам](#доступ-к-контейнерам)
  - [Composer](#composer)
  - [Миграции и база данных](#миграции-и-база-данных)
  - [Кэш и конфигурация](#кэш-и-конфигурация)
  - [Разработка](#разработка)
  - [Очереди](#очереди)
  - [Тестирование и качество кода](#тестирование-и-качество-кода)
  - [Дополнительные команды](#дополнительные-команды)
- [Docker команды (альтернатива)](#-docker-команды-альтернатива)
- [Переменные окружения](#-переменные-окружения)
- [Порты сервисов](#-порты-сервисов)
- [Решение проблем](#-решение-проблем)
- [Полезные советы](#-полезные-советы)

---

## 📦 Требования

Перед началом работы убедитесь, что у вас установлены:

- **Docker** (версия 20.10+)
- **Docker Compose** (версия 2.0+)
- **Make** (установлен по умолчанию на Linux/macOS)

### Проверка установки

```bash
docker --version
docker compose version
make --version
```

### Установка Make на Windows

Если вы используете Windows, установите Make через Chocolatey:

```bash
choco install make
```

Или используйте WSL (Windows Subsystem for Linux).

---

## 🎯 Быстрый старт

### Развертывание проекта одной командой

```bash
make init
```

Эта команда автоматически:

1. ✅ Создаст файл `.env` из `.env.example` (если его нет)
2. ✅ Соберет Docker образы
3. ✅ Запустит все контейнеры
4. ✅ Установит зависимости Composer
5. ✅ Исправит права на папки `storage` и `bootstrap/cache`
6. ✅ Сгенерирует `APP_KEY`
7. ✅ Создаст символическую ссылку `public/storage`
8. ✅ Выполнит миграции базы данных
9. ✅ Очистит все кэши

После завершения откройте браузер: **http://localhost**

---

## 📁 Структура проекта

```
your-project/
├── docker/
│   ├── php/
│   │   ├── Dockerfile              # Конфигурация PHP 8.5 + Redis
│   │   └── docker-entrypoint.sh    # Скрипт автозапуска миграций
│   └── nginx/
│       └── default.conf            # Конфигурация Nginx
├── docker-compose.yml              # Оркестрация контейнеров
├── Makefile                        # Команды для удобства
├── .env                            # Переменные окружения (создается автоматически)
└── ... (остальные файлы Laravel)
```

---

## 🛠 Команды Make

### Справка

```bash
make help    # Показать список всех команд
make usage   # Показать примеры использования
```

---

### Инициализация и управление

#### Полная инициализация проекта

```bash
make init
```

Запускает все необходимые шаги для первого развертывания проекта.

#### Полный сброс проекта

```bash
make fresh
```

Удаляет все данные (включая базу данных), пересобирает образы и заново инициализирует проект с сидерами.

#### Управление контейнерами

| Команда | Описание |
|---------|----------|
| `make up` | Запустить все контейнеры в фоне |
| `make down` | Остановить все контейнеры |
| `make restart` | Перезапустить все контейнеры |
| `make build` | Собрать образы и запустить контейнеры |
| `make rebuild` | Полная пересборка с удалением volumes и автомиграциями |
| `make clean` | Удалить все ресурсы проекта (контейнеры, образы, volumes) |

#### Просмотр логов

| Команда | Описание |
|---------|----------|
| `make logs` | Логи всех контейнеров (в реальном времени) |
| `make logs-app` | Логи PHP-приложения |
| `make logs-nginx` | Логи Nginx |
| `make logs-db` | Логи PostgreSQL |
| `make logs-queue` | Логи воркера очередей |

#### Статус контейнеров

```bash
make ps
```

---

### Доступ к контейнерам

#### Войти в консоль PHP-контейнера

```bash
make bash
# или
make shell
```

#### Войти как root

```bash
make root
```

#### Консоль PostgreSQL

```bash
make db-shell
```

После входа вы можете выполнять SQL-запросы:

```sql
SELECT * FROM users;
\dt  -- список всех таблиц
\q   -- выход
```

#### Консоль Redis

```bash
make redis-cli
```

После входа:

```redis
KEYS *
GET session:id
FLUSHDB  -- очистить текущую БД
```

---

### Composer

#### Установка зависимостей

```bash
make composer-install
```

#### Обновление зависимостей

```bash
make composer-update
```

#### Пересоздание автозагрузчика

```bash
make composer-dump
```

#### Установка пакета

```bash
make composer-require PACKAGE=laravel/sanctum
```

#### Удаление пакета

```bash
make composer-remove PACKAGE=laravel/sanctum
```

#### Просмотр устаревших пакетов

```bash
make composer-outdated
```

#### Произвольная Composer команда

```bash
make composer CMD="show laravel/framework"
```

---

### Миграции и база данных

#### Выполнить миграции

```bash
make migrate
```

#### Сбросить БД и выполнить миграции заново

```bash
make migrate-fresh
```

#### Откатить последнюю миграцию

```bash
make migrate-rollback
```

#### Показать статус миграций

```bash
make migrate-status
```

#### Выполнить сидеры

```bash
make seed
```

#### Сбросить БД, миграции и сидеры

```bash
make fresh-seed
```

#### Сделать дамп базы данных

```bash
make db-dump
```

Создает файл `dump.sql` в корне проекта.

#### Восстановить базу данных из дампа

```bash
make db-restore
```

Восстанавливает БД из файла `dump.sql`.

#### Выполнить SQL-запрос

```bash
make db-query QUERY="SELECT version();"
make db-query QUERY="SELECT * FROM users LIMIT 5;"
```

---

### Кэш и конфигурация

#### Очистить весь кэш

```bash
make cache-clear
```

Очищает кэш приложения, конфигурации, маршрутов, представлений и событий.

#### Кэш конфигурации (для production)

```bash
make cache-config
```

#### Кэш маршрутов (для production)

```bash
make cache-route
```

#### Кэш представлений (для production)

```bash
make cache-view
```

#### Оптимизировать приложение (для production)

```bash
make optimize
```

#### Снять всю оптимизацию

```bash
make optimize-clear
```

---

### Разработка

#### Сгенерировать APP_KEY

```bash
make key
```

#### Запустить Tinker (интерактивная консоль)

```bash
make tinker
```

Пример использования:

```php
>>> User::all();
>>> User::find(1);
>>> App\Models\Post::create(['title' => 'Test']);
```

#### Показать список маршрутов

```bash
make routes
```

#### Создать символическую ссылку storage

```bash
make storage-link
```

#### Запустить встроенный сервер Laravel

```bash
make serve
```

Откройте **http://localhost:8000**

#### Создать модель с миграцией, контроллером и фабрикой

```bash
make make-model NAME=Post
```

Создает:
- `app/Models/Post.php`
- `database/migrations/xxxx_create_posts_table.php`
- `app/Http/Controllers/PostController.php`
- `database/factories/PostFactory.php`

#### Создать контроллер

```bash
make make-controller NAME=UserController
```

#### Создать миграцию

```bash
make make-migration NAME=create_posts_table
```

#### Создать Job

```bash
make make-job NAME=ProcessImage
```

#### Создать Event

```bash
make make-event NAME=UserRegistered
```

#### Создать Listener

```bash
make make-listener NAME=SendWelcomeEmail
```

#### Создать Artisan команду

```bash
make make-command NAME=SyncData
```

#### Создать тест

```bash
make make-test NAME=UserTest
```

#### Произвольная Artisan команда

```bash
make artisan CMD="route:list"
make artisan CMD="config:show database"
```

---

### Очереди

#### Перезапустить воркер очередей

```bash
make queue-restart
```

#### Показать список проваленных задач

```bash
make queue-failed
```

#### Повторить все проваленные задачи

```bash
make queue-retry
```

#### Очистить список проваленных задач

```bash
make queue-flush
```

#### Очистить все задачи в очереди

```bash
make queue-clear
```

#### Запустить воркер очередей вручную (для отладки)

```bash
make queue-work
```

---

### Тестирование и качество кода

#### Запустить PHPUnit тесты

```bash
make test
```

#### Запустить тесты с фильтром

```bash
make test-filter FILTER=UserTest
```

#### Запустить Pest тесты

```bash
make pest
```

#### Исправить стиль кода с помощью Laravel Pint

```bash
make pint
```

#### Проверить стиль кода без исправлений

```bash
make pint-test
```

#### Запустить PHPStan анализ

```bash
make phpstan
```

---

### Дополнительные команды

#### Исправить права на storage и bootstrap/cache

```bash
make perms
```

Автоматически создает необходимые подпапки в `storage/framework/` и назначает корректного владельца (`www-data`).

---

## 🐳 Docker команды (альтернатива)

Если вы не хотите использовать Makefile, все команды можно выполнять напрямую через `docker compose`.

### Управление контейнерами

```bash
# Запустить контейнеры
docker compose up -d

# Остановить контейнеры
docker compose down

# Перезапустить контейнеры
docker compose restart

# Собрать образы и запустить
docker compose up -d --build

# Полная пересборка с удалением volumes
docker compose down -v
docker compose build --no-cache
docker compose up -d

# Удалить все ресурсы
docker compose down -v --rmi local --remove-orphans

# Показать статус
docker compose ps

# Логи всех контейнеров
docker compose logs -f

# Логи конкретного контейнера
docker compose logs -f app
docker compose logs -f web
docker compose logs -f db
docker compose logs -f redis
docker compose logs -f queue
```

### Выполнение команд внутри контейнеров

```bash
# Войти в bash контейнера app
docker compose exec app bash

# Войти как root
docker compose exec -u root app bash

# Выполнить Artisan команду
docker compose exec app php artisan migrate
docker compose exec app php artisan key:generate
docker compose exec app php artisan tinker

# Выполнить Composer команду
docker compose exec app composer install
docker compose exec app composer require laravel/sanctum

# Войти в консоль PostgreSQL
docker compose exec db psql -U laravel -d laravel

# Войти в консоль Redis
docker compose exec redis redis-cli
```

### Работа с базой данных

```bash
# Сделать дамп
docker compose exec db pg_dump -U laravel -d laravel > dump.sql

# Восстановить из дампа
docker compose exec -T db psql -U laravel -d laravel < dump.sql

# Выполнить SQL-запрос
docker compose exec db psql -U laravel -d laravel -c "SELECT version();"
```

### Исправление прав

```bash
docker compose exec -u root app sh -c "mkdir -p storage/framework/{sessions,views,cache} && chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache"
```

---

## 🔧 Переменные окружения

Файл `.env` создается автоматически при выполнении `make init`. Основные переменные:

### База данных

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

### Redis

```env
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### Кэш и сессии

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Приложение

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:...  # Генерируется автоматически
APP_DEBUG=true
APP_URL=http://localhost
```

> **Важно:** Значения `DB_HOST` и `REDIS_HOST` должны совпадать с именами сервисов в `docker-compose.yml` (`db` и `redis` соответственно), а не быть `localhost`.

---

## 📊 Порты сервисов

| Сервис | Порт | Описание |
|--------|------|----------|
| Nginx | 80 | Веб-сервер (http://localhost) |
| PostgreSQL | 5432 | База данных |
| Redis | 6379 | Кэш и очереди |

---

## 🐛 Решение проблем

### Ошибка: "Port 80 is already allocated"

**Причина:** Порт 80 занят другим процессом.

**Решение:** Измените порт в `docker-compose.yml`:

```yaml
web:
  ports:
    - "8080:80"  # Измените на 8080 или любой другой свободный порт
```

Затем откройте **http://localhost:8080**

---

### Ошибка: "Port 6379 is already allocated"

**Причина:** Локально запущен Redis или другой контейнер использует этот порт.

**Решение 1:** Измените порт в `docker-compose.yml`:

```yaml
redis:
  ports:
    - "6380:6379"  # Измените на 6380
```

**Решение 2:** Остановите локальный Redis:

```bash
sudo systemctl stop redis
sudo systemctl disable redis
```

---

### Ошибка: "tempnam(): file created in the system's temporary directory"

**Причина:** Неправильные права на папки `storage` и `bootstrap/cache`.

**Решение:**

```bash
make perms
make restart
```

---

### Ошибка: "SQLSTATE[42P01]: Undefined table"

**Причина:** База данных пуста, миграции не выполнены.

**Решение:**

```bash
make migrate
```

> **Примечание:** При использовании `make rebuild` миграции выполняются автоматически благодаря `docker-entrypoint.sh`.

---

### Ошибка: "detected dubious ownership in repository"

**Причина:** Git обнаружил несоответствие владельца папки.

**Решение:** Эта проблема уже решена в `Dockerfile` (добавлена настройка `safe.directory`). Если ошибка все равно появляется, пересоберите образ:

```bash
docker compose build --no-cache app
make restart
```

---

### Ошибка: "The [public/storage] link already exists"

**Причина:** Символическая ссылка уже существует.

**Решение:** Эта проблема уже решена в `Makefile` (команда `storage-link` идемпотентна). Если ошибка все равно появляется, выполните:

```bash
docker compose exec app rm -f public/storage
make storage-link
```

---

### Ошибка сборки: "fatal error: ext/standard/php_smart_string.h"

**Причина:** Версия расширения Redis с PECL несовместима с PHP 8.5.

**Решение:** В `Dockerfile` используется сборка `phpredis` из GitHub master, где эта проблема уже исправлена. Если ошибка все равно появляется, пересоберите образ:

```bash
docker compose build --no-cache app
```

---

### Контейнеры не запускаются

**Решение 1:** Проверьте логи:

```bash
make logs
```

**Решение 2:** Полностью пересоберите проект:

```bash
make clean
make init
```

---

### Медленная работа приложения

**Решение 1:** Оптимизируйте кэш (для production):

```bash
make optimize
```

**Решение 2:** Убедитесь, что используете Redis для кэша и сессий в `.env`:

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
```

---

## 💡 Полезные советы

### 1. Используйте Makefile

Команды `make` намного короче и удобнее, чем прямые `docker compose` команды. Всегда используйте `make help` для просмотра доступных команд.

### 2. Очищайте кэш при изменениях

Если вы изменили конфигурацию или маршруты, не забудьте очистить кэш:

```bash
make cache-clear
```

### 3. Используйте Tinker для отладки

Tinker — мощный инструмент для интерактивной работы с Laravel:

```bash
make tinker
```

### 4. Следите за логами

При разработке полезно держать открытыми логи:

```bash
make logs-app
```

### 5. Регулярно обновляйте зависимости

```bash
make composer-update
```

### 6. Автоматические миграции

Благодаря `docker/php/docker-entrypoint.sh` миграции выполняются автоматически при каждом запуске контейнера `app`. Скрипт также ожидает готовности PostgreSQL перед выполнением миграций, что исключает ошибки подключения.

### 7. Идемпотентные команды

Все команды в `Makefile` спроектированы так, чтобы их можно было запускать многократно без побочных эффектов.

---

## 📝 Лицензия

Этот проект создан для образовательных целей. Используйте свободно!

---

## 🤝 Поддержка

Если у вас возникли вопросы или проблемы:

1. Проверьте раздел [Решение проблем](#-решение-проблем)
2. Выполните `make help` для просмотра всех доступных команд
3. Проверьте логи: `make logs`

