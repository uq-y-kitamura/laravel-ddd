# ローカルに PHP が無い環境向けのショートカット。すべて Docker 経由で実行する。
DOCKER_PHP = docker run --rm -e COMPOSER_HOME=/tmp/composer -v "$(CURDIR)":/app -w /app composer:2

.PHONY: install migrate test lint serve fresh

install: ## 依存関係のインストールとオートローダ生成
	$(DOCKER_PHP) composer install

dump: ## オートローダの再生成
	$(DOCKER_PHP) composer dump-autoload

migrate: ## マイグレーション実行
	$(DOCKER_PHP) php artisan migrate --force

fresh: ## DB を作り直してマイグレーション
	$(DOCKER_PHP) php artisan migrate:fresh --force

test: ## テスト実行
	$(DOCKER_PHP) php artisan test

lint: ## Laravel Pint によるコード整形
	$(DOCKER_PHP) ./vendor/bin/pint

serve: ## 開発サーバ起動 (http://localhost:8000)
	docker compose up
