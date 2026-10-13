export UID := $(shell id -u)
export GID := $(shell id -g)

DC  = docker compose
PHP = $(DC) run --rm php

.PHONY: install build vendor db assets watch start stop logs ps test cs cs-fix stan rector usecases exec

## Setup
install: build vendor db assets

build:
	@echo "Docker/Building images..."
	@$(DC) build

vendor:
	@echo "Composer/Installing..."
	@$(PHP) composer install

db:
	@echo "Database/Creating and loading fixtures..."
	@$(PHP) sh -c 'bin/console doctrine:schema:drop --force --full-database && bin/console doctrine:schema:create && bin/console doctrine:fixtures:load --no-interaction'

assets:
	@echo "Assets/Building..."
	@$(PHP) sh -c 'bin/console importmap:install && bin/console sass:build'

watch:
	@$(PHP) bin/console sass:build --watch

## Stack
start:
	@$(DC) up -d
	@xdg-open https://local.prospecter.io >/dev/null 2>&1 || true

stop:
	@$(DC) stop

logs:
	@$(DC) logs -f

ps:
	@$(DC) ps

## Quality
test:
	@$(PHP) bin/phpunit

cs:
	@$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix:
	@$(PHP) vendor/bin/php-cs-fixer fix

stan:
	@$(PHP) vendor/bin/phpstan analyse

rector:
	@$(PHP) vendor/bin/rector process --dry-run

## Tools
usecases:
	@$(PHP) bin/console app:use-cases

exec:
	@$(PHP) bash
