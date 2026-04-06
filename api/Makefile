.PHONY: help fixtures fixtures-reload migrate diff schema-validate cc phpstan phpcs rector rector-dry lint

DC = docker compose
PHP = $(DC) exec -e XDEBUG_MODE=off php
CONSOLE = $(PHP) php -d memory_limit=1G bin/console

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

## —— Database ————————————————————————————————————

diff: ## Generate a migration from entity diff
	$(CONSOLE) doctrine:migrations:diff

migrate: ## Run pending migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

schema-validate: ## Validate Doctrine mapping vs database
	$(CONSOLE) doctrine:schema:validate

## —— Fixtures ————————————————————————————————————

fixtures: ## Load fixtures (append to existing data)
	$(CONSOLE) doctrine:fixtures:load --no-interaction --append

fixtures-reload: ## Purge database + load fixtures from scratch
	$(CONSOLE) doctrine:fixtures:load --no-interaction

## —— Cache ———————————————————————————————————————

cc: ## Clear Symfony cache
	$(CONSOLE) cache:clear

## —— Redis ——————————————————————————————————————

redis-cli: ## Open a Redis CLI session
	$(DC) exec redis redis-cli

redis-keys: ## List all Redis keys
	$(DC) exec redis redis-cli KEYS '*'

redis-get: ## Get a Redis key value (usage: make redis-get KEY=mykey)
	$(DC) exec redis redis-cli GET $(KEY)

redis-flush: ## Flush all Redis data
	$(DC) exec redis redis-cli FLUSHALL

redis-monitor: ## Watch Redis commands in real-time
	$(DC) exec redis redis-cli MONITOR

## —— Quality —————————————————————————————————————

phpstan: ## Run PHPStan (level max)
	$(PHP) php -d memory_limit=512M vendor/bin/phpstan analyse

phpcs: ## Run PHP_CodeSniffer (PSR-12)
	$(PHP) vendor/bin/phpcs

phpcs-fix: ## Auto-fix PHP_CodeSniffer violations
	$(PHP) vendor/bin/phpcbf

rector: ## Run Rector (apply fixes)
	$(PHP) vendor/bin/rector process

rector-dry: ## Run Rector in dry-run mode
	$(PHP) vendor/bin/rector process --dry-run

lint: phpcs phpstan ## Run all linters (phpcs + phpstan)

## —— Combo ———————————————————————————————————————

reset: ## Full reset: migrate + purge + fixtures
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction
