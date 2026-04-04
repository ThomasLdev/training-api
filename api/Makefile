.PHONY: help fixtures fixtures-reload migrate diff schema-validate cc

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

## —— Combo ———————————————————————————————————————

reset: ## Full reset: migrate + purge + fixtures
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction
