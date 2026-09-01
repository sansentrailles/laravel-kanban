# =============================================================================
# Laravel 13 + Docker Makefile
# PHP 8.5 | PostgreSQL | Redis | ImageMagick
# =============================================================================

# Переменные
DOCKER_COMPOSE = docker compose
APP_CONTAINER  = app
EXEC           = $(DOCKER_COMPOSE) exec $(APP_CONTAINER)

# Цвета для вывода
GREEN  = \033[0;32m
YELLOW = \033[1;33m
RED    = \033[0;31m
CYAN   = \033[0;36m
NC     = \033[0m

# =============================================================================
# СПРАВКА
# =============================================================================

.PHONY: help
help: ## 📖 Показать список всех команд
	@echo ""
	@echo "$(CYAN)╔══════════════════════════════════════════════════════════╗$(NC)"
	@echo "$(CYAN)║          Laravel 13 + Docker — Список команд           ║$(NC)"
	@echo "$(CYAN)╚══════════════════════════════════════════════════════════╝$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-22s$(NC) %s\n", $$1, $$2}'
	@echo ""

.PHONY: usage
usage: ## 💡 Показать примеры использования
	@echo ""
	@echo "$(GREEN)Примеры использования:$(NC)"
	@echo "  make init                                          # Первый запуск проекта"
	@echo "  make up                                            # Запустить контейнеры"
	@echo "  make bash                                          # Войти в контейнер"
	@echo "  make migrate                                       # Выполнить миграции"
	@echo "  make test                                          # Запустить тесты"
	@echo "  make composer-require PACKAGE=laravel/sanctum       # Установить пакет"
	@echo "  make fresh                                         # Полный сброс проекта"
	@echo "  make imagick-check                                 # Проверить ImageMagick"
	@echo ""

# =============================================================================
# DOCKER: УПРАВЛЕНИЕ КОНТЕЙНЕРАМИ
# =============================================================================

.PHONY: up
up: ## 🚀 Запустить все контейнеры в фоне
	$(DOCKER_COMPOSE) up -d

.PHONY: down
down: ## 🛑 Остановить все контейнеры
	$(DOCKER_COMPOSE) down

.PHONY: restart
restart: down up ## 🔄 Полный перезапуск контейнеров

.PHONY: build
build: ## 🔨 Собрать образы и запустить контейнеры
	$(DOCKER_COMPOSE) up -d --build

.PHONY: rebuild
rebuild: ## 🔥 Полная пересборка (с удалением volumes и данных)
	$(DOCKER_COMPOSE) down -v
	$(DOCKER_COMPOSE) up -d --build

.PHONY: logs
logs: ## 📜 Показать логи всех контейнеров
	$(DOCKER_COMPOSE) logs -f

.PHONY: logs-app
logs-app: ## 📜 Показать логи приложения
	$(DOCKER_COMPOSE) logs -f app

.PHONY: logs-queue
logs-queue: ## 📜 Показать логи очереди
	$(DOCKER_COMPOSE) logs -f queue

.PHONY: logs-db
logs-db: ## 📜 Показать логи базы данных
	$(DOCKER_COMPOSE) logs -f db

.PHONY: logs-nginx
logs-nginx: ## 📜 Показать логи Nginx
	$(DOCKER_COMPOSE) logs -f nginx

.PHONY: ps
ps: ## 📋 Показать статус контейнеров
	$(DOCKER_COMPOSE) ps

# =============================================================================
# ДОСТУП К КОНТЕЙНЕРАМ
# =============================================================================

.PHONY: bash
bash: ## 💻 Войти в bash контейнера приложения
	$(EXEC) bash

.PHONY: shell
shell: ## 💻 Войти в bash контейнера приложения (алиас)
	$(EXEC) bash

.PHONY: root
root: ## 💻 Войти в контейнер как root
	$(DOCKER_COMPOSE) exec -u root $(APP_CONTAINER) bash

.PHONY: db-shell
db-shell: ## 🗄 Войти в консоль PostgreSQL
	$(DOCKER_COMPOSE) exec db psql -U laravel -d laravel

.PHONY: redis-cli
redis-cli: ## 🔴 Войти в Redis CLI
	$(DOCKER_COMPOSE) exec redis redis-cli

# =============================================================================
# COMPOSER
# =============================================================================

.PHONY: composer-install
composer-install: ## 📦 Установить зависимости Composer
	$(EXEC) composer install

.PHONY: composer-update
composer-update: ## 📦 Обновить зависимости Composer
	$(EXEC) composer update

.PHONY: composer-dump
composer-dump: ## 📦 Пересоздать автозагрузчик Composer
	$(EXEC) composer dump-autoload

.PHONY: composer-require
composer-require: ## 📦 Установить пакет (make composer-require PACKAGE=vendor/name)
	$(EXEC) composer require $(PACKAGE)

.PHONY: composer-remove
composer-remove: ## 📦 Удалить пакет (make composer-remove PACKAGE=vendor/name)
	$(EXEC) composer remove $(PACKAGE)

.PHONY: composer-outdated
composer-outdated: ## 📦 Показать устаревшие пакеты
	$(EXEC) composer outdated

# =============================================================================
# LARAVEL ARTISAN: МИГРАЦИИ И БАЗА ДАННЫХ
# =============================================================================

.PHONY: migrate
migrate: ## 🗄 Выполнить миграции
	$(EXEC) php artisan migrate

.PHONY: migrate-fresh
migrate-fresh: ## 🗄 Сбросить БД и выполнить миграции заново
	$(EXEC) php artisan migrate:fresh

.PHONY: migrate-rollback
migrate-rollback: ## 🗄 Откатить последнюю миграцию
	$(EXEC) php artisan migrate:rollback

.PHONY: migrate-status
migrate-status: ## 🗄 Показать статус миграций
	$(EXEC) php artisan migrate:status

.PHONY: seed
seed: ## 🌱 Выполнить сидеры
	$(EXEC) php artisan db:seed

.PHONY: fresh-seed
fresh-seed: ## 🌱 Сбросить БД, миграции и сидеры
	$(EXEC) php artisan migrate:fresh --seed

# =============================================================================
# LARAVEL ARTISAN: КЕШ И КОНФИГУРАЦИЯ
# =============================================================================

.PHONY: cache-clear
cache-clear: ## 🧹 Очистить весь кеш приложения
	$(EXEC) php artisan cache:clear
	$(EXEC) php artisan config:clear
	$(EXEC) php artisan route:clear
	$(EXEC) php artisan view:clear
	$(EXEC) php artisan event:clear

.PHONY: cache-config
cache-config: ## ⚡ Кеш конфигурации (для production)
	$(EXEC) php artisan config:cache

.PHONY: cache-route
cache-route: ## ⚡ Кеш маршрутов (для production)
	$(EXEC) php artisan route:cache

.PHONY: cache-view
cache-view: ## ⚡ Кеш представлений (для production)
	$(EXEC) php artisan view:cache

.PHONY: optimize
optimize: ## ⚡ Оптимизировать приложение (для production)
	$(EXEC) php artisan optimize

.PHONY: optimize-clear
optimize-clear: ## 🧹 Снять всю оптимизацию
	$(EXEC) php artisan optimize:clear

# =============================================================================
# LARAVEL ARTISAN: РАЗРАБОТКА
# =============================================================================

.PHONY: key
key: ## 🔑 Сгенерировать APP_KEY
	$(EXEC) php artisan key:generate

.PHONY: tinker
tinker: ## 🔧 Запустить Tinker (интерактивная консоль)
	$(EXEC) php artisan tinker

.PHONY: routes
routes: ## 🛣 Показать список маршрутов
	$(EXEC) php artisan route:list

.PHONY: storage-link
storage-link: ## 🔗 Создать символическую ссылку storage
	$(EXEC) php artisan storage:link

.PHONY: serve
serve: ## 🌐 Запустить встроенный сервер Laravel
	$(EXEC) php artisan serve --host=0.0.0.0 --port=8000

.PHONY: make-model
make-model: ## 📝 Создать модель (make make-model NAME=User)
	$(EXEC) php artisan make:model $(NAME) -mcrf

.PHONY: make-controller
make-controller: ## 📝 Создать контроллер (make make-controller NAME=UserController)
	$(EXEC) php artisan make:controller $(NAME)

.PHONY: make-migration
make-migration: ## 📝 Создать миграцию (make make-migration NAME=create_posts_table)
	$(EXEC) php artisan make:migration $(NAME)

.PHONY: make-job
make-job: ## 📝 Создать Job (make make-job NAME=ProcessImage)
	$(EXEC) php artisan make:job $(NAME)

.PHONY: make-event
make-event: ## 📝 Создать Event (make make-event NAME=UserRegistered)
	$(EXEC) php artisan make:event $(NAME)

.PHONY: make-listener
make-listener: ## 📝 Создать Listener (make make-listener NAME=SendWelcomeEmail)
	$(EXEC) php artisan make:listener $(NAME)

.PHONY: make-command
make-command: ## 📝 Создать Artisan команду (make make-command NAME=SyncData)
	$(EXEC) php artisan make:command $(NAME)

.PHONY: make-test
make-test: ## 📝 Создать тест (make make-test NAME=UserTest)
	$(EXEC) php artisan make:test $(NAME)

# =============================================================================
# ОЧЕРЕДИ
# =============================================================================

.PHONY: queue-restart
queue-restart: ## 🔄 Перезапустить воркер очередей
	$(EXEC) php artisan queue:restart

.PHONY: queue-failed
queue-failed: ## ❌ Показать список проваленных задач
	$(EXEC) php artisan queue:failed

.PHONY: queue-retry
queue-retry: ## 🔁 Повторить все проваленные задачи
	$(EXEC) php artisan queue:retry all

.PHONY: queue-flush
queue-flush: ## 🗑 Очистить список проваленных задач
	$(EXEC) php artisan queue:flush

.PHONY: queue-clear
queue-clear: ## 🗑 Очистить все задачи в очереди
	$(EXEC) php artisan queue:clear

.PHONY: queue-work
queue-work: ## ⚙️ Запустить воркер очередей вручную (для отладки)
	$(EXEC) php artisan queue:work --tries=3 --timeout=90 -v

# =============================================================================
# ТЕСТИРОВАНИЕ И КАЧЕСТВО КОДА
# =============================================================================

.PHONY: test
test: ## 🧪 Запустить PHPUnit тесты
	$(EXEC) php artisan test

.PHONY: test-filter
test-filter: ## 🧪 Запустить тесты с фильтром (make test-filter FILTER=UserTest)
	$(EXEC) php artisan test --filter=$(FILTER)

.PHONY: pest
pest: ## 🧪 Запустить Pest тесты
	$(EXEC) ./vendor/bin/pest

.PHONY: pint
pint: ## 🎨 Исправить стиль кода с помощью Laravel Pint
	$(EXEC) ./vendor/bin/pint

.PHONY: pint-test
pint-test: ## 🎨 Проверить стиль кода без исправлений
	$(EXEC) ./vendor/bin/pint --test

.PHONY: phpstan
phpstan: ## 🔍 Запустить PHPStan анализ
	$(EXEC) ./vendor/bin/phpstan analyse

# =============================================================================
# ПРОВЕРКА НАСТРОЕК (PHP, IMAGICK, GD)
# =============================================================================

.PHONY: php-info
php-info: ## ℹ️ Показать ключевые настройки PHP
	@echo "$(GREEN)=== Настройки PHP ===$(NC)"
	$(EXEC) php -i | grep -E "^(memory_limit|upload_max_filesize|post_max_size|max_execution_time|imagick|gd)" || true

.PHONY: php-version
php-version: ## ℹ️ Показать версию PHP
	$(EXEC) php -v

.PHONY: php-ini
php-ini: ## 📜 Показать путь к загруженному php.ini
	$(EXEC) php --ini

.PHONY: php-modules
php-modules: ## 📜 Показать список установленных модулей PHP
	$(EXEC) php -m

.PHONY: imagick-check
imagick-check: ## 🖼 Проверить настройки ImageMagick
	@echo "$(GREEN)=== ImageMagick PHP Extension ===$(NC)"
	$(EXEC) php -r "echo 'Imagick version: ' . Imagick::getVersion()['versionString'] . PHP_EOL; echo 'Supported formats: ' . count(Imagick::queryFormats()) . PHP_EOL;"
	@echo ""
	@echo "$(GREEN)=== ImageMagick CLI ===$(NC)"
	$(EXEC) convert --version 2>/dev/null || $(EXEC) magick --version 2>/dev/null || echo "$(RED)ImageMagick CLI не найден$(NC)"

.PHONY: imagick-policy
imagick-policy: ## 📜 Показать текущий policy.xml ImageMagick
	$(EXEC) cat /etc/ImageMagick-6/policy.xml 2>/dev/null || $(EXEC) cat /etc/ImageMagick-7/policy.xml 2>/dev/null || echo "$(RED)policy.xml не найден$(NC)"

.PHONY: imagick-formats
imagick-formats: ## 🖼 Показать все поддерживаемые форматы ImageMagick
	$(EXEC) php -r "print_r(Imagick::queryFormats());"

.PHONY: imagick-test
imagick-test: ## 🖼 Тест создания изображения через ImageMagick
	$(EXEC) php -r "\$$im = new Imagick(); \$$im->newImage(100, 100, new ImagickPixel('red')); \$$im->setImageFormat('png'); \$$im->writeImage('/tmp/test.png'); echo 'OK: ' . filesize('/tmp/test.png') . ' bytes' . PHP_EOL;"

.PHONY: gd-check
gd-check: ## 🖼 Проверить настройки GD
	$(EXEC) php -r "print_r(gd_info());"

# =============================================================================
# ПОЛНАЯ ИНИЦИАЛИЗАЦИЯ ПРОЕКТА
# =============================================================================

.PHONY: init
init: env build composer-install key storage-link migrate ## 🎯 Полная инициализация проекта с нуля
	@echo ""
	@echo "$(GREEN)╔══════════════════════════════════════════════════════════╗$(NC)"
	@echo "$(GREEN)║       ✅ Проект успешно инициализирован!                ║$(NC)"
	@echo "$(GREEN)╚══════════════════════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo "$(YELLOW)  🌐 Откройте: http://localhost$(NC)"
	@echo "$(YELLOW)  📊 PostgreSQL: localhost:5432 (laravel / secret)$(NC)"
	@echo "$(YELLOW)  🔴 Redis:      localhost:6379$(NC)"
	@echo ""

.PHONY: fresh
fresh: rebuild composer-install key fresh-seed ## 🔥 Полный сброс проекта (с удалением данных)
	@echo ""
	@echo "$(GREEN)✅ Проект полностью сброшен и инициализирован заново!$(NC)"
	@echo "$(YELLOW)🌐 Откройте: http://localhost$(NC)"
	@echo ""

# =============================================================================
# ДОПОЛНИТЕЛЬНЫЕ КОМАНДЫ
# =============================================================================

.PHONY: env
env: ## 📄 Скопировать .env.example в .env (если не существует)
	@test -f .env && echo "$(YELLOW).env уже существует$(NC)" || (cp .env.example .env && echo "$(GREEN).env файл создан из .env.example$(NC)")

.PHONY: perms
perms: ## 🔐 Исправить права на storage и bootstrap/cache
	$(DOCKER_COMPOSE) exec -u root $(APP_CONTAINER) chown -R www-data:www-data storage bootstrap/cache
	$(DOCKER_COMPOSE) exec -u root $(APP_CONTAINER) chmod -R 775 storage bootstrap/cache
	@echo "$(GREEN)Права исправлены$(NC)"

.PHONY: artisan
artisan: ## 🛠 Выполнить произвольную artisan команду (make artisan CMD="route:list")
	$(EXEC) php artisan $(CMD)

.PHONY: composer
composer: ## 🛠 Выполнить произвольную composer команду (make composer CMD="show")
	$(EXEC) composer $(CMD)

.PHONY: prune
prune: ## 🧹 Очистить неиспользуемые Docker ресурсы
	docker system prune -f
	@echo "$(GREEN)Docker ресурсы очищены$(NC)"

.PHONY: prune-all
prune-all: ## 🧹 Полная очистка Docker (включая volumes!)
	docker system prune -a --volumes -f
	@echo "$(RED)Все Docker ресурсы удалены!$(NC)"
